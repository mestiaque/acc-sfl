<?php

namespace ME\AccSfl\Http\Controllers;

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
use ME\AccSfl\Models\AcExpenseIou;
use ME\AccSfl\Models\AcPaymentMethod;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExpenseIouController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('ac_expense_iou.list');

        $expenseIous = $this->filteredQuery($request)->latest('id')->paginate(20)->withQueryString();

        $branches = AcBranch::query()->active()->orderBy('name')->get();
        $accounts = AcAccount::query()->active()->visibleToCurrentUser()->orderBy('name')->get();
        $paymentMethods = AcPaymentMethod::query()->active()->orderBy('name')->get();
        $employees = $this->activeEmployees();

        return view('acc-sfl::admin.expense-ious.index', compact('expenseIous', 'branches', 'accounts', 'paymentMethods', 'employees'));
    }

    public function print(Request $request): View
    {
        $this->authorize('ac_expense_iou.list');

        $expenseIous = $this->filteredQuery($request)->latest('id')->get();

        return view('acc-sfl::admin.expense-ious.print', compact('expenseIous'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_expense_iou.list');

        $expenseIous = $this->filteredQuery($request)->latest('id')->get();

        return Excel::download(new ExpenseIouExport($expenseIous), 'expense-ious.xlsx');
    }

    private function filteredQuery(Request $request): Builder
    {
        return AcExpenseIou::query()
            ->with(['branch', 'account', 'employee', 'paymentMethod', 'attachments'])
            ->when(AcAccount::currentUserTiedAccountIds(), fn ($q, $tiedIds) => $q->whereIn('account_id', $tiedIds))
            ->when($request->filled('search'), fn ($q) => $q->where('iou_no', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('account_id'), fn ($q) => $q->where('account_id', $request->integer('account_id')))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('issue_date', '>=', $request->date('from_date')))
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('issue_date', '<=', $request->date('to_date')));
    }

    public function store(ExpenseIouRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();
            unset($data['attachments']);
            $data['status'] = AcExpenseIou::STATUS_PENDING;

            $expenseIou = AcExpenseIou::create($data);

            $this->storeAttachments($expenseIou, $request);
        });

        return back()->with('success', 'Expense IOU issued successfully.');
    }

    public function update(ExpenseIouRequest $request, AcExpenseIou $expenseIou, \ME\AccSfl\Services\TransactionService $transactions): RedirectResponse
    {
        DB::transaction(function () use ($request, $expenseIou, $transactions) {
            $data = $request->validated();
            unset($data['attachments']);

            $oldAmount = (float) $expenseIou->amount;

            $expenseIou->update($data);

            if (array_key_exists('amount', $data)) {
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

    public function adjust(Request $request, AcExpenseIou $expenseIou): RedirectResponse
    {
        $this->authorize('ac_expense_iou.edit');

        if ($expenseIou->status === AcExpenseIou::STATUS_ADJUSTED) {
            return back()->with('error', 'This IOU has already been adjusted.');
        }

        $validated = $request->validate([
            'adjust_date' => ['required', 'date', 'after_or_equal:'.$expenseIou->issue_date->toDateString()],
        ]);

        DB::transaction(fn () => $expenseIou->update([
            'adjust_date' => $validated['adjust_date'],
            'status' => AcExpenseIou::STATUS_ADJUSTED,
        ]));

        return back()->with('success', 'Expense IOU adjusted successfully.');
    }

    public function destroy(AcExpenseIou $expenseIou): RedirectResponse
    {
        $this->authorize('ac_expense_iou.delete');

        if ($expenseIou->isReferenced()) {
            return back()->with('error', 'This IOU cannot be deleted because it already has a posted transaction.');
        }

        $expenseIou->delete();

        return back()->with('success', 'Expense IOU deleted successfully.');
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
