<?php

namespace ME\AccSfl\Http\Controllers;

use App\Models\Approval;
use App\Services\ApprovalService;
use ME\AccSfl\Http\Controllers\Concerns\SortsListing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\AccSfl\Exports\ExpenseIouExport;
use ME\AccSfl\Http\Requests\ExpenseIouRequest;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcBranch;
use ME\AccSfl\Models\AcExpense;
use ME\AccSfl\Models\AcExpenseIou;
use ME\AccSfl\Models\AcPaymentMethod;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExpenseIouController extends Controller
{
    use SortsListing;

    public function index(Request $request): View
    {
        $this->authorize('ac_expense_iou.list');

        $expenseIous = $this->applyListSort($this->filteredQuery($request), $request, 'issue_date', 'iou_no')->paginate(20)->withQueryString();

        $branches = AcBranch::query()->active()->orderBy('name')->get();
        $accounts = AcAccount::query()->active()->visibleToCurrentUser()->orderBy('name')->get();
        $paymentMethods = AcPaymentMethod::query()->active()->orderBy('name')->get();
        $employees = $this->activeEmployees();

        return view('acc-sfl::admin.expense-ious.index', compact('expenseIous', 'branches', 'accounts', 'paymentMethods', 'employees'));
    }

    public function print(Request $request): View
    {
        $this->authorize('ac_expense_iou.list');

        $expenseIous = $this->applyListSort($this->filteredQuery($request), $request, 'issue_date', 'iou_no')->get();

        return view('acc-sfl::admin.expense-ious.print', compact('expenseIous'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_expense_iou.list');

        $expenseIous = $this->applyListSort($this->filteredQuery($request), $request, 'issue_date', 'iou_no')->get();

        return Excel::download(new ExpenseIouExport($expenseIous), 'expense-ious.xlsx');
    }

    private function filteredQuery(Request $request): Builder
    {
        return AcExpenseIou::query()
            ->with(['branch', 'account', 'employee', 'paymentMethod', 'attachments'])
            ->withSum(['expenses as approved_expense_total' => fn ($q) => $q->where('status', AcExpense::STATUS_APPROVED)], 'total_amount')
            ->withCount(['expenses as pending_expense_count' => fn ($q) => $q->where('status', AcExpense::STATUS_PENDING)])
            ->when(AcAccount::currentUserTiedAccountIds(), fn ($q, $tiedIds) => $q->whereIn('account_id', $tiedIds))
            ->when($request->filled('search'), fn ($q) => $q->where('iou_no', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('account_id'), fn ($q) => $q->where('account_id', $request->integer('account_id')))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('status'), fn ($q) => in_array($request->string('status')->value(), [AcExpenseIou::APPROVAL_PENDING, AcExpenseIou::APPROVAL_REJECTED], true)
                ? $q->where('approval_status', $request->string('status'))
                : $q->where('status', $request->string('status'))->approved())
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('issue_date', '>=', $request->date('from_date')))
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('issue_date', '<=', $request->date('to_date')));
    }

    public function store(ExpenseIouRequest $request): RedirectResponse
    {
        $expenseIou = DB::transaction(function () use ($request) {
            $data = $request->validated();
            unset($data['attachments']);
            $data['status'] = AcExpenseIou::STATUS_PENDING;
            $data['approval_status'] = AcExpenseIou::APPROVAL_PENDING;

            $expenseIou = AcExpenseIou::create($data);

            $this->storeAttachments($expenseIou, $request);

            return $expenseIou;
        });

        // Like expenses: nothing leaves the account until approved; approvers get the mail.
        app(ApprovalService::class)->request([
            'module' => AcExpenseIou::APPROVAL_MODULE,
            'approvable' => $expenseIou,
            'title' => "Expense IOU Approval - {$expenseIou->iou_no}",
            'description' => ($request->user()?->name ?? 'Someone').' issued an IOU of '.number_format((float) $expenseIou->amount, 2)
                .($expenseIou->employee?->name ? " to {$expenseIou->employee->name}" : '').'.',
            'route_name' => 'acc-sfl.expense-ious.index',
            'requested_by' => $request->user()?->id,
        ]);

        return back()->with('success', "Expense IOU {$expenseIou->iou_no} saved and sent for approval — the cash leaves the account once it is approved.");
    }

    public function update(ExpenseIouRequest $request, AcExpenseIou $expenseIou, \ME\AccSfl\Services\TransactionService $transactions): RedirectResponse
    {
        if ($request->filled('employee_id') && (int) $request->input('employee_id') !== (int) $expenseIou->employee_id
            && $expenseIou->expenses()->exists()) {
            return back()->with('error', 'The employee cannot be changed — expenses are already recorded against this IOU.');
        }

        DB::transaction(function () use ($request, $expenseIou, $transactions) {
            $data = $request->validated();
            unset($data['attachments']);

            $oldAmount = (float) $expenseIou->amount;

            $expenseIou->update($data);

            // Not approved yet: nothing was posted, so there is nothing to correct.
            if (array_key_exists('amount', $data) && $expenseIou->isApproved()) {
                $transactions->correctIouAmount($expenseIou, $oldAmount, (float) $expenseIou->amount);
            }

            $this->storeAttachments($expenseIou, $request);
        });

        return back()->with('success', 'Expense IOU updated successfully.');
    }

    private function storeAttachments(AcExpenseIou $expenseIou, Request $request): void
    {
        foreach ($request->file('attachments', []) as $uploadedFile) {
            if (!$uploadedFile) {
                continue;
            }

            $path = $uploadedFile->store('accounts/iou', 'public');

            $expenseIou->attachments()->create([
                'fileable_type' => AcExpenseIou::class,
                'use_case' => 'attachment',
                'file_name' => (string) \Illuminate\Support\Str::uuid(),
                'original_name' => $uploadedFile->getClientOriginalName(),
                'file_path' => $path,
                'file_full_path' => asset('storage/'.$path),
                'disk' => 'public',
                'extension' => $uploadedFile->getClientOriginalExtension(),
                'size' => $uploadedFile->getSize(),
                'file_type' => $uploadedFile->getMimeType(),
                'addedby_id' => $request->user()?->id,
            ]);
        }
    }

    public function destroyAttachment(AcExpenseIou $expenseIou, \App\Models\File $file): RedirectResponse
    {
        $this->authorize('ac_expense_iou.edit');

        abort_unless($expenseIou->attachments()->whereKey($file->id)->exists(), 404);

        if ($file->file_path) {
            \Illuminate\Support\Facades\Storage::disk($file->disk ?: 'public')->delete($file->file_path);
        }
        $file->delete();

        return redirect()->back()->with('success', 'Attachment removed successfully.');
    }

    /**
     * Settles the IOU against the approved expenses bought with it: a debit clearing the full
     * IOU amount plus a credit for each expense (see TransactionService::postIouAdjustment(),
     * triggered by AcExpenseIouObserver). The net is the unspent cash returned, or the extra paid.
     */
    public function adjust(Request $request, AcExpenseIou $expenseIou): RedirectResponse
    {
        $this->authorize('ac_expense_iou.edit');

        if ($expenseIou->status === AcExpenseIou::STATUS_ADJUSTED) {
            return back()->with('error', 'This IOU has already been adjusted.');
        }

        if (! $expenseIou->isApproved()) {
            return back()->with('error', 'This IOU is not approved yet — it can be adjusted only after approval.');
        }

        if ($expenseIou->hasPendingExpenses()) {
            return back()->with('error', 'This IOU still has expenses waiting for approval — approve or reject them before adjusting.');
        }

        $validated = $request->validate([
            'adjust_date' => ['required', 'date', 'after_or_equal:'.$expenseIou->issue_date->toDateString()],
            'settlement_payment_method_id' => ['nullable', 'integer', 'exists:ac_payment_methods,id'],
        ]);

        $settlement = round((float) $expenseIou->amount - $expenseIou->approvedExpenseTotal(), 2);

        DB::transaction(fn () => $expenseIou->update([
            'adjust_date' => $validated['adjust_date'],
            'settlement_amount' => $settlement,
            'settlement_payment_method_id' => $validated['settlement_payment_method_id'] ?? $expenseIou->payment_method_id,
            'status' => AcExpenseIou::STATUS_ADJUSTED,
        ]));

        $spent = (float) $expenseIou->amount - $settlement;
        $message = 'Expense IOU adjusted — debit '.number_format((float) $expenseIou->amount, 2).' (advance cleared)'
            .($spent > 0 ? ', credit '.number_format($spent, 2).' (expenses)' : '')
            .match (true) {
                $settlement > 0 => ', net '.number_format($settlement, 2).' returned to the account.',
                $settlement < 0 => ', net '.number_format(abs($settlement), 2).' extra paid from the account.',
                default => ', fully spent.',
            };

        return back()->with('success', $message);
    }

    public function approve(AcExpenseIou $expenseIou, Request $request): RedirectResponse
    {
        $this->authorize('ac_expense_iou.approve');

        abort_if($expenseIou->approval_status !== AcExpenseIou::APPROVAL_PENDING, 403, 'Only IOUs waiting for approval can be approved.');

        if (! $approval = $this->findPendingApproval($expenseIou)) {
            return back()->with('error', 'No pending approval request found for this IOU.');
        }

        app(ApprovalService::class)->approve($approval, $request->user(), $request->input('remarks'));

        return back()->with('success', "Expense IOU {$expenseIou->iou_no} approved — the advance is posted to the account.");
    }

    public function reject(AcExpenseIou $expenseIou, Request $request): RedirectResponse
    {
        $this->authorize('ac_expense_iou.approve');

        abort_if($expenseIou->approval_status !== AcExpenseIou::APPROVAL_PENDING, 403, 'Only IOUs waiting for approval can be rejected.');

        $request->validate(['remarks' => ['required', 'string', 'max:1000']]);

        if (! $approval = $this->findPendingApproval($expenseIou)) {
            return back()->with('error', 'No pending approval request found for this IOU.');
        }

        app(ApprovalService::class)->reject($approval, $request->user(), $request->input('remarks'));

        return back()->with('success', "Expense IOU {$expenseIou->iou_no} rejected.");
    }

    private function findPendingApproval(AcExpenseIou $expenseIou): ?Approval
    {
        return Approval::where('module', AcExpenseIou::APPROVAL_MODULE)
            ->where('approvable_type', AcExpenseIou::class)
            ->where('approvable_id', $expenseIou->id)
            ->where('status', 'pending')
            ->first();
    }

    public function destroy(AcExpenseIou $expenseIou, \ME\AccSfl\Services\TransactionService $transactions): RedirectResponse
    {
        $this->authorize('ac_expense_iou.delete');

        if ($expenseIou->status === AcExpenseIou::STATUS_ADJUSTED) {
            return back()->with('error', 'Adjusted IOUs cannot be deleted normally — use Force Delete.');
        }

        if ($expenseIou->expenses()->where('status', '!=', AcExpense::STATUS_REJECTED)->exists()) {
            return back()->with('error', 'This IOU has expenses recorded against it — delete them or remove the IOU from them first.');
        }

        DB::transaction(function () use ($expenseIou, $transactions) {
            $existing = $expenseIou->transactions()->get();

            if ($existing->isNotEmpty()) {
                $transactions->reverseTransactions($expenseIou->account, $existing);
            }

            $expenseIou->delete();
        });

        return back()->with('success', 'Expense IOU deleted successfully.');
    }

    /**
     * Reverses all posted ledger transactions for this IOU (Issue, any amount
     * Corrections, and its zero-value Adjustment audit entry) and permanently removes
     * it - the only way to remove an already-Adjusted IOU, since normal delete() is
     * blocked once it's settled.
     *
     * Its approved expenses were posted as Expense entries when the IOU was adjusted; those
     * stay (they're real purchases), and the expenses are unlinked so they stand on their own,
     * as if paid straight from the account.
     */
    public function forceDestroy(AcExpenseIou $expenseIou, \ME\AccSfl\Services\TransactionService $transactions): RedirectResponse
    {
        $this->authorize('ac_expense_iou.force_delete');

        if ($expenseIou->status !== AcExpenseIou::STATUS_ADJUSTED) {
            return back()->with('error', 'Only adjusted IOUs need Force Delete — use the normal Delete action instead.');
        }

        DB::transaction(function () use ($expenseIou, $transactions) {
            $existing = $expenseIou->transactions()->get();

            if ($existing->isNotEmpty()) {
                $transactions->reverseTransactions($expenseIou->account, $existing);
            }

            foreach ($expenseIou->expenses()->get() as $expense) {
                $expense->update(['iou_id' => null]);

                // Already posted at adjust (dated the adjust date); only post the ones that weren't.
                if ($expense->status === AcExpense::STATUS_APPROVED && ! $expense->transactions()->exists()) {
                    $transactions->postExpense($expense);
                }
            }

            foreach ($expenseIou->attachments as $file) {
                if ($file->file_path) {
                    \Illuminate\Support\Facades\Storage::disk($file->disk ?: 'public')->delete($file->file_path);
                }
                $file->delete();
            }

            $expenseIou->forceDelete();
        });

        return back()->with('success', 'Expense IOU permanently deleted and its ledger entries reversed. Any expenses recorded against it are now posted on their own.');
    }

    /**
     * HR is an optional integration for this module (see AcExpenseIou::employee()), so
     * this is guarded rather than a hard dependency - installs without the HR package
     * simply get an empty employee dropdown instead of a fatal error.
     */
    private function activeEmployees(): \Illuminate\Support\Collection
    {
        if (! class_exists(\ME\Hr\Models\HrEmployee::class)) {
            return collect();
        }

        return \ME\Hr\Models\HrEmployee::query()
            ->whereNull('exited_at')
            ->where(fn ($q) => $q->whereNull('employment_status')->orWhereIn('employment_status', ['', 'regular', 'active']))
            ->with(['department:id,name', 'designation:id,name'])
            ->orderBy('employee_id')
            ->get(['id', 'employee_id', 'name', 'department_id', 'designation_id']);
    }
}
