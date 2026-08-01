<?php

namespace ME\AccSfl\Http\Controllers;

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

        $branches = AcBranch::query()->active()->orderBy('name')->get();
        $accounts = AcAccount::query()->active()->visibleToCurrentUser()->orderBy('name')->get();
        $paymentMethods = AcPaymentMethod::query()->active()->orderBy('name')->get();
        $allowedParticularIds = AcAccount::currentUserAllowedParticularIds();
        $particulars = AcMasterParticular::query()->credit()->active()
            ->with(['particulars' => fn ($q) => $q->active()->orderBy('code')
                ->when($allowedParticularIds !== null, fn ($q2) => $q2->whereIn('id', $allowedParticularIds))])
            ->get();

        return view('acc-sfl::admin.expenses.index', compact('expenses', 'branches', 'accounts', 'paymentMethods', 'particulars'));
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

        $expense->load(['branch', 'account', 'paymentMethod', 'creator', 'details.particular']);

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
            ->with(['branch', 'account', 'paymentMethod', 'creator', 'details.particular.masterParticular'])
            ->when(AcAccount::currentUserTiedAccount(), fn ($q, $tied) => $q->where('account_id', $tied->id))
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
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('expense_date', '>=', $request->date('from_date')))
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('expense_date', '<=', $request->date('to_date')));
    }

    public function show(AcExpense $expense): View
    {
        $this->authorize('ac_expense.view');

        $expense->load(['branch', 'account', 'paymentMethod', 'creator', 'details.particular.masterParticular']);

        return view('acc-sfl::admin.expenses.show', compact('expense'));
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();
            $items = $data['items'];
            unset($data['items']);

            $data['created_by'] = $request->user()?->id;
            $data['total_amount'] = collect($items)->sum(fn ($item) => $item['qty'] * $item['rate']);

            if ($request->hasFile('attachment')) {
                $data['attachment'] = $request->file('attachment')->store('acc-sfl/expenses', 'public');
            }

            $expense = AcExpense::create($data);

            foreach ($items as $item) {
                $expense->details()->create([
                    'particular_id' => $item['particular_id'],
                    'invoice' => $item['invoice'] ?? null,
                    'qty' => $item['qty'],
                    'uom' => $item['uom'] ?? null,
                    'rate' => $item['rate'],
                    'amount' => $item['qty'] * $item['rate'],
                    'description' => $item['description'] ?? null,
                ]);
            }
        });

        return back()->with('success', 'Expense recorded successfully.');
    }

    public function update(ExpenseRequest $request, AcExpense $expense): RedirectResponse
    {
        DB::transaction(function () use ($request, $expense) {
            $data = $request->validated();

            if ($request->hasFile('attachment')) {
                if ($expense->attachment) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($expense->attachment);
                }
                $data['attachment'] = $request->file('attachment')->store('acc-sfl/expenses', 'public');
            }

            $expense->update($data);
        });

        return back()->with('success', 'Expense updated successfully.');
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
