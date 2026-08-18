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
        $allRows = $this->buildRows($this->filteredQuery($request)->get(), $request);
        $rows = $this->buildRows($expenses->getCollection(), $request);
        $grouped = $this->groupByParticularCode($rows);
        $totals = $this->totals($allRows);

        return view('acc-sfl::admin.reports.expense-report', array_merge(
            compact('expenses', 'grouped', 'totals'),
            $this->filterOptions(),
        ));
    }

    public function print(Request $request): View
    {
        $this->authorize('ac_report.export');

        $rows = $this->buildRows($this->filteredQuery($request)->get(), $request);
        $grouped = $this->groupByParticularCode($rows);
        $totals = $this->totals($rows);

        return view('acc-sfl::admin.reports.expense-report-print', compact('grouped', 'totals'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_report.export');

        $rows = $this->buildRows($this->filteredQuery($request)->get(), $request);
        $grouped = $this->groupByParticularCode($rows);
        $totals = $this->totals($rows);

        return Excel::download(new ExpenseReportExport($grouped, $totals), 'expense-report.xlsx');
    }

    /**
     * Groups by particular code (not master particular - AcMasterParticular has no code
     * column, see AcParticular::code) so each group's header/subtotal lines up with the
     * A/C Code column already shown per row. Rows are sorted chronologically within each
     * group since the base query orders by expense_date desc for pagination purposes only.
     */
    private function groupByParticularCode(Collection $rows): Collection
    {
        return $rows->sortBy('date')->values()
            ->groupBy(fn ($row) => $row['ac_code'] ?? 'N/A')
            ->sortKeys();
    }

    /**
     * One ac_transactions row is posted per Expense (its total_amount, only once approved),
     * so this explodes each expense into one report row per line item, splitting the
     * transaction's balance backwards across those lines (in entry order) so every row shows
     * a real running balance instead of all lines repeating the expense total's balance.
     * Mirrors StatementReportService's identical need for the Account Statement report.
     *
     * The particular filter is applied per line here (not just per expense in
     * filteredQuery()) so a multi-particular selection doesn't drag in an expense's other,
     * non-matching line items - but every line still counts against the running balance,
     * whether or not it's emitted, since the balance reflects the expense's full total.
     */
    private function buildRows(Collection $expenses, Request $request): Collection
    {
        $particularIds = $request->filled('particular_id') ? (array) $request->input('particular_id') : null;

        return $expenses->flatMap(function (AcExpense $expense) use ($particularIds) {
            $transaction = $expense->transactions->first();
            $running = $transaction ? (float) $transaction->balance + (float) $transaction->credit : null;
            $rows = [];

            foreach ($expense->details as $detail) {
                if ($running !== null) {
                    $running -= (float) $detail->amount;
                }

                if ($particularIds !== null && ! in_array($detail->particular_id, $particularIds, false)) {
                    continue;
                }

                $rows[] = [
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
            }

            return $rows;
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
                fn ($d) => $d->whereIn('particular_id', (array) $request->input('particular_id'))
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

    /**
     * Summed from the exploded per-line rows (not a plain AcExpense total_amount sum) so the
     * badge matches what's actually displayed once a particular filter narrows rows down to
     * specific line items within otherwise-multi-line expenses.
     */
    private function totals(Collection $rows): array
    {
        return [
            'count' => $rows->count(),
            'amount' => (float) $rows->sum('expense'),
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
