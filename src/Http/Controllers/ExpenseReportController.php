<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\AccSfl\Exports\ExpenseReportExport;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcBranch;
use ME\AccSfl\Models\AcExpense;
use ME\AccSfl\Models\AcMasterParticular;
use ME\AccSfl\Models\AcPaymentMethod;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExpenseReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('ac_report.view');

        $expenses = $this->filteredQuery($request)->latest('expense_date')->latest('id')->paginate(20)->withQueryString();
        $rows = $this->buildRows($expenses->getCollection());
        $totals = $this->totals($request);

        return view('acc-sfl::admin.reports.expense-report', array_merge(
            compact('expenses', 'rows', 'totals'),
            $this->filterOptions(),
        ));
    }

    public function print(Request $request): View
    {
        $this->authorize('ac_report.export');

        $expenses = $this->filteredQuery($request)->latest('expense_date')->latest('id')->get();
        $rows = $this->buildRows($expenses);
        $totals = $this->totals($request);

        return view('acc-sfl::admin.reports.expense-report-print', compact('rows', 'totals'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_report.export');

        $expenses = $this->filteredQuery($request)->latest('expense_date')->latest('id')->get();
        $rows = $this->buildRows($expenses);
        $totals = $this->totals($request);

        return Excel::download(new ExpenseReportExport($rows, $totals), 'expense-report.xlsx');
    }

    /**
     * One ac_transactions row is posted per Expense (its total_amount, only once approved),
     * so this explodes each expense into one report row per line item, splitting the
     * transaction's balance backwards across those lines (in entry order) so every row shows
     * a real running balance instead of all lines repeating the expense total's balance.
     * Mirrors StatementReportService's identical need for the Account Statement report.
     */
    private function buildRows(Collection $expenses): Collection
    {
        return $expenses->flatMap(function (AcExpense $expense) {
            $transaction = $expense->transactions->first();
            $running = $transaction ? (float) $transaction->balance + (float) $transaction->credit : null;

            return $expense->details->map(function ($detail) use ($expense, &$running) {
                if ($running !== null) {
                    $running -= (float) $detail->amount;
                }

                return [
                    'date' => $expense->expense_date,
                    'particular' => $detail->particular?->name,
                    'description' => $detail->description,
                    'ac_code' => $detail->particular?->code,
                    'invoice' => $detail->invoice ?: $expense->invoice,
                    'receiver' => $expense->receiver_name,
                    'qty' => $detail->qty > 0 ? (float) $detail->qty : null,
                    'uom' => $detail->uom,
                    'rate' => $detail->rate > 0 ? (float) $detail->rate : null,
                    'expense' => (float) $detail->amount,
                    'balance' => $running,
                    'remarks' => null,
                ];
            });
        });
    }

    private function filteredQuery(Request $request): Builder
    {
        return AcExpense::query()
            ->with(['branch', 'account', 'paymentMethod', 'creator', 'employee', 'transactions', 'details.particular.masterParticular'])
            ->when(AcAccount::currentUserTiedAccountIds(), fn ($q, $tiedIds) => $q->whereIn('account_id', $tiedIds))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(function ($query) use ($search) {
                    $query->where('expense_no', 'like', "%{$search}%")
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
            ->when($request->filled('min_amount'), fn ($q) => $q->where('total_amount', '>=', $request->float('min_amount')))
            ->when($request->filled('max_amount'), fn ($q) => $q->where('total_amount', '<=', $request->float('max_amount')))
            ->when(
                $request->filled('status') && $request->input('status') !== 'all',
                fn ($q) => $q->where('status', $request->input('status')),
                // Unapproved expenses haven't posted to the ledger and shouldn't appear in
                // reports by default; pass ?status=pending/rejected to see them, or ?status=all for everything.
                fn ($q) => $q->when(
                    !$request->filled('status'),
                    fn ($q2) => $q2->where('status', AcExpense::STATUS_APPROVED)
                )
            );
    }

    private function totals(Request $request): array
    {
        $query = $this->filteredQuery($request);

        return [
            'count' => (clone $query)->count(),
            'amount' => (float) (clone $query)->sum('total_amount'),
        ];
    }

    private function filterOptions(): array
    {
        return [
            'branches' => AcBranch::query()->active()->orderBy('name')->get(),
            'accounts' => AcAccount::query()->active()->visibleToCurrentUser()->orderBy('name')->get(),
            'paymentMethods' => AcPaymentMethod::query()->active()->orderBy('name')->get(),
            'masterParticulars' => AcMasterParticular::query()->credit()->active()
                ->with(['particulars' => fn ($q) => $q->active()->orderBy('code')])
                ->orderBy('id')
                ->get(),
            'employees' => $this->activeEmployees(),
        ];
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
            ->orderBy('employee_id')
            ->get(['id', 'employee_id', 'name']);
    }
}
