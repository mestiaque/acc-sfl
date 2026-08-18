<?php

namespace ME\AccSfl\Http\Controllers;

use App\Models\Approval;
use App\Services\ApprovalService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\AccSfl\Exports\ExpenseExport;
use ME\AccSfl\Http\Requests\ExpenseRequest;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcBranch;
use ME\AccSfl\Models\AcExpense;
use ME\AccSfl\Models\AcMasterParticular;
use ME\AccSfl\Models\AcPaymentMethod;
use ME\AccSfl\Services\NumberToWordsService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('ac_expense.list');

        $expenses = $this->filteredQuery($request)->latest('id')->paginate(20)->withQueryString();
       

        return view('acc-sfl::admin.expenses.index', array_merge(compact('expenses'), $this->formOptions()));
    }

    public function create(): View
    {
        $this->authorize('ac_expense.add');

        return view('acc-sfl::admin.expenses.create', $this->formOptions());
    }

    public function edit(AcExpense $expense): View
    {
        $this->authorize('ac_expense.edit');

        abort_if($expense->status !== AcExpense::STATUS_PENDING, 403, 'Only pending expenses can be fully edited.');

        $expense->load('details', 'attachments');

        return view('acc-sfl::admin.expenses.edit', array_merge(['expense' => $expense], $this->formOptions()));
    }

    private function formOptions(): array
    {
        $branches = AcBranch::query()->active()->orderBy('name')->get();
        $accounts = AcAccount::query()->active()->visibleToCurrentUser()->orderBy('name')->get();
        $paymentMethods = AcPaymentMethod::query()->active()->orderBy('name')->get();
        $allowedParticularIds = AcAccount::currentUserAllowedParticularIds();
        $particulars = AcMasterParticular::query()->credit()->active()
            ->with(['particulars' => fn ($q) => $q->active()->orderBy('code')
                ->when($allowedParticularIds !== null, fn ($q2) => $q2->whereIn('id', $allowedParticularIds))])
            ->get();
        $employees = $this->activeEmployees();

        return compact('branches', 'accounts', 'paymentMethods', 'particulars', 'employees');
    }

    /**
     * HR is an optional integration for this module (see AcExpense::employee()), so
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

    public function print(Request $request): View
    {
        $this->authorize('ac_expense.list');

        $expenses = $this->filteredQuery($request)->latest('id')->get();

        return view('acc-sfl::admin.expenses.print', compact('expenses'));
    }

    public function slip(AcExpense $expense, NumberToWordsService $numberToWords): View
    {
        $this->authorize('ac_expense.view');

        $expense->load(['branch', 'account', 'paymentMethod', 'creator', 'employee', 'details.particular']);

        $amountInWords = $numberToWords->taka((float) $expense->total_amount);

        return view('acc-sfl::admin.expenses.slip', compact('expense', 'amountInWords'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_expense.list');

        $expenses = $this->filteredQuery($request)->latest('id')->get();

        return Excel::download(new ExpenseExport($expenses), 'expenses.xlsx');
    }

    private function filteredQuery(Request $request): Builder
    {
        return AcExpense::query()
            ->with(['branch', 'account', 'paymentMethod', 'creator', 'employee', 'details.particular.masterParticular'])
            ->when(AcAccount::currentUserTiedAccountIds(), fn ($q, $tiedIds) => $q->whereIn('account_id', $tiedIds))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('expense_no', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('receiver_name', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('account_id'), fn ($q) => $q->where('account_id', $request->integer('account_id')))
            ->when($request->filled('payment_method_id'), fn ($q) => $q->where('payment_method_id', $request->integer('payment_method_id')))
            ->when($request->filled('master_particular_id'), fn ($q) => $q->whereHas(
                'details.particular',
                fn ($p) => $p->where('master_particular_id', $request->integer('master_particular_id'))
            ))
            ->when($request->filled('particular_id'), fn ($q) => $q->whereHas(
                'details',
                fn ($d) => $d->where('particular_id', $request->integer('particular_id'))
            ))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('expense_date', '>=', $request->date('from_date')))
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('expense_date', '<=', $request->date('to_date')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));
    }

    public function show(AcExpense $expense): View
    {
        $this->authorize('ac_expense.view');

        $expense->load(['branch', 'account', 'paymentMethod', 'creator', 'employee', 'details.particular.masterParticular', 'attachments']);

        return view('acc-sfl::admin.expenses.show', compact('expense'));
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        $expense = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $items = $data['items'];
            unset($data['items']);

            $data['created_by'] = $request->user()?->id;
            $data['total_amount'] = collect($items)->sum(fn ($item) => $this->resolveItemAmount($item));

            if ($request->hasFile('attachment')) {
                $data['attachment'] = $request->file('attachment')->store('accounts/expense', 'public');
            }

            $expense = AcExpense::create($data);

            $this->syncDetails($expense, $items);
            $this->storeAttachments($expense, $request);

            return $expense;
        });

        app(ApprovalService::class)->request([
            'module' => 'accounts.expense',
            'approvable' => $expense,
            'title' => "Expense Approval - {$expense->expense_no}",
            'description' => "{$expense->creator?->name} recorded an expense of {$expense->total_amount}"
                .($expense->company_name ? " for {$expense->company_name}" : '').'.',
            'route_name' => 'acc-sfl.expenses.index',
            'requested_by' => $request->user()?->id,
        ]);

        return redirect()->route('acc-sfl.expenses.index')->with('success', 'Expense recorded successfully and sent for approval.');
    }

    public function update(ExpenseRequest $request, AcExpense $expense): RedirectResponse
    {
        DB::transaction(function () use ($request, $expense) {
            $data = $request->validated();

            if ($request->hasFile('attachment')) {
                if ($expense->attachment) {
                    Storage::disk('public')->delete($expense->attachment);
                }
                $data['attachment'] = $request->file('attachment')->store('accounts/expense', 'public');
            }

            $this->storeAttachments($expense, $request);

            if ($expense->status === AcExpense::STATUS_PENDING && isset($data['items'])) {
                $items = $data['items'];
                unset($data['items']);

                $data['total_amount'] = collect($items)->sum(fn ($item) => $this->resolveItemAmount($item));

                $expense->update($data);

                $expense->details()->delete();
                $this->syncDetails($expense, $items);
            } else {
                $expense->update($data);
            }
        });

        return redirect()->route('acc-sfl.expenses.index')->with('success', 'Expense updated successfully.');
    }

    private function storeAttachments(AcExpense $expense, Request $request): void
    {
        foreach ($request->file('attachments', []) as $uploadedFile) {
            if (!$uploadedFile) {
                continue;
            }

            $path = $uploadedFile->store('accounts/expense', 'public');

            $expense->attachments()->create([
                'fileable_type' => AcExpense::class,
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

    public function destroyAttachment(AcExpense $expense, \App\Models\File $file): RedirectResponse
    {
        $this->authorize('ac_expense.edit');

        abort_unless($expense->attachments()->whereKey($file->id)->exists(), 404);

        if ($file->file_path) {
            Storage::disk($file->disk ?: 'public')->delete($file->file_path);
        }
        $file->delete();

        return redirect()->back()->with('success', 'Attachment removed successfully.');
    }

    private function syncDetails(AcExpense $expense, array $items): void
    {
        foreach ($items as $item) {
            $expense->details()->create([
                'particular_id' => $item['particular_id'],
                'qty' => $item['qty'] ?? 0,
                'uom' => $item['uom'] ?? null,
                'rate' => $item['rate'] ?? 0,
                'amount' => $this->resolveItemAmount($item),
            ]);
        }
    }

    private function resolveItemAmount(array $item): float
    {
        if (isset($item['amount']) && $item['amount'] !== '') {
            return (float) $item['amount'];
        }

        return (float) ($item['qty'] ?? 0) * (float) ($item['rate'] ?? 0);
    }

    public function approve(AcExpense $expense, Request $request): RedirectResponse
    {
        $this->authorize('ac_expense.approve');

        abort_if($expense->status !== AcExpense::STATUS_PENDING, 403, 'Only pending expenses can be approved.');

        $approval = $this->findPendingApproval($expense);

        if (!$approval) {
            return back()->with('error', 'No pending approval request found for this expense.');
        }

        app(ApprovalService::class)->approve($approval, $request->user(), $request->input('remarks'));

        return back()->with('success', 'Expense approved successfully.');
    }

    public function reject(AcExpense $expense, Request $request): RedirectResponse
    {
        $this->authorize('ac_expense.approve');

        abort_if($expense->status !== AcExpense::STATUS_PENDING, 403, 'Only pending expenses can be rejected.');

        $request->validate(['remarks' => ['required', 'string', 'max:1000']]);

        $approval = $this->findPendingApproval($expense);

        if (!$approval) {
            return back()->with('error', 'No pending approval request found for this expense.');
        }

        app(ApprovalService::class)->reject($approval, $request->user(), $request->input('remarks'));

        return back()->with('success', 'Expense rejected.');
    }

    private function findPendingApproval(AcExpense $expense): ?Approval
    {
        return Approval::where('module', 'accounts.expense')
            ->where('approvable_type', AcExpense::class)
            ->where('approvable_id', $expense->id)
            ->where('status', 'pending')
            ->first();
    }

    public function destroy(AcExpense $expense): RedirectResponse
    {
        $this->authorize('ac_expense.delete');

        if ($expense->isReferenced()) {
            return back()->with('error', 'This expense cannot be deleted because it already has a posted transaction.');
        }

        $expense->delete();

        return back()->with('success', 'Expense deleted successfully.');
    }
}
