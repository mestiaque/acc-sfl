<?php

namespace ME\AccSfl\Services;

use Illuminate\Support\Carbon;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcBalanceReceive;
use ME\AccSfl\Models\AcExpenseDetail;
use ME\AccSfl\Models\AcMasterParticular;
use ME\AccSfl\Models\AcParticular;

/**
 * Builds the cash-flow-by-particular report (Beginning Balance, itemised Receipts,
 * itemised Payments split by master particular, Ending Balance carried forward).
 *
 * Deliberately derived from ac_balance_receives / ac_expense_details rather than the
 * per-account ac_transactions ledger: this report is a consolidated company/branch cash
 * position across all accounts, while ac_transactions tracks per-account running balances
 * for the Accounts/Transactions modules. Cash-on-hand at any date = sum of account opening
 * balances + all receipts to date - all payment lines to date, scoped by branch if given.
 */
class CashFlowReportService
{
    public function monthly(
        int $year,
        int $month,
        ?int $branchId = null,
        ?int $accountId = null,
        ?int $masterParticularId = null,
        ?array $particularIds = null,
    ): array {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $daysInMonth = (int) $start->daysInMonth;

        $receiptsByDay = $this->groupReceipts($start, $start->copy()->endOfMonth(), $branchId, $accountId, $masterParticularId, $particularIds);
        $paymentsByDay = $this->groupPayments($start, $start->copy()->endOfMonth(), $branchId, $accountId, $masterParticularId, $particularIds);

        $running = $this->cashBalanceThrough($start->copy()->subDay(), $branchId, $accountId);
        $beginningOfMonth = $running;

        $days = [];
        $totalsByParticular = [];
        $grandReceipts = 0.0;
        $grandPayments = 0.0;

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = $start->copy()->day($d)->toDateString();
            $dayReceipts = $receiptsByDay[$date] ?? [];
            $dayPayments = $paymentsByDay[$date] ?? [];
            $totalReceipts = array_sum($dayReceipts);
            $totalPayments = array_sum($dayPayments);

            $days[$d] = [
                'date' => $date,
                'beginning_balance' => $running,
                'receipts' => $dayReceipts,
                'total_receipts' => $totalReceipts,
                'payments' => $dayPayments,
                'total_payments' => $totalPayments,
                'ending_balance' => $running + $totalReceipts - $totalPayments,
            ];

            $this->accumulate($totalsByParticular, $dayReceipts);
            $this->accumulate($totalsByParticular, $dayPayments);

            $grandReceipts += $totalReceipts;
            $grandPayments += $totalPayments;
            $running = $days[$d]['ending_balance'];
        }

        return [
            'year' => $year,
            'month' => $month,
            'label' => $start->format('F-y'),
            'days_in_month' => $daysInMonth,
            'taxonomy' => $this->taxonomy($masterParticularId, $particularIds),
            'days' => $days,
            'totals_by_particular' => $totalsByParticular,
            'beginning_balance' => $beginningOfMonth,
            'ending_balance' => $running,
            'total_receipts' => $grandReceipts,
            'total_payments' => $grandPayments,
        ];
    }

    /**
     * Builds the report for the 12 months starting at $start (whatever month that is -
     * the AcFiscalYear master record decides that, not this service), grouped into 4
     * sequential fiscal quarters, with a single fiscal-year total column at the end.
     */
    public function yearly(
        Carbon $start,
        ?int $branchId = null,
        ?int $accountId = null,
        ?int $masterParticularId = null,
        ?array $particularIds = null,
    ): array {
        $start = $start->copy()->startOfMonth();
        $fiscalYearStartYear = $start->year;
        $fiscalYearEndYear = $start->copy()->addMonthsNoOverflow(11)->year;
        $fiscalYearLabel = $fiscalYearStartYear === $fiscalYearEndYear
            ? (string) $fiscalYearStartYear
            : $fiscalYearStartYear.'-'.substr((string) $fiscalYearEndYear, -2);
        $totalMonths = 12;

        $running = $this->cashBalanceThrough($start->copy()->subDay(), $branchId, $accountId);
        $beginningOfYear = $running;

        $months = [];
        $totalsByParticular = [];
        $grandReceipts = 0.0;
        $grandPayments = 0.0;

        for ($m = 0; $m < $totalMonths; $m++) {
            $monthStart = $start->copy()->addMonthsNoOverflow($m)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            $monthReceiptTotals = $this->flattenByParticular($this->groupReceipts($monthStart, $monthEnd, $branchId, $accountId, $masterParticularId, $particularIds));
            $monthPaymentTotals = $this->flattenByParticular($this->groupPayments($monthStart, $monthEnd, $branchId, $accountId, $masterParticularId, $particularIds));

            $totalReceipts = array_sum($monthReceiptTotals);
            $totalPayments = array_sum($monthPaymentTotals);
            $netChange = $totalReceipts - $totalPayments;
            $endingBalance = $running + $netChange;

            $months[$m] = [
                'label' => $monthStart->format('M-y'),
                'fiscal_quarter' => intdiv($m, 3) + 1,
                'calendar_year' => (int) $monthStart->format('y'),
                'beginning_balance' => $running,
                'receipts' => $monthReceiptTotals,
                'total_receipts' => $totalReceipts,
                'payments' => $monthPaymentTotals,
                'total_payments' => $totalPayments,
                'net_change' => $netChange,
                'ending_balance' => $endingBalance,
            ];

            $this->accumulate($totalsByParticular, $monthReceiptTotals);
            $this->accumulate($totalsByParticular, $monthPaymentTotals);

            $grandReceipts += $totalReceipts;
            $grandPayments += $totalPayments;
            $running = $endingBalance;
        }

        $quarters = [];
        foreach (array_chunk($months, 3) as $qi => $chunk) {
            $first = $chunk[array_key_first($chunk)];
            $last = $chunk[array_key_last($chunk)];
            $quarters[$qi] = [
                'label' => 'QUARTER '.$first['fiscal_quarter'].' TOTALS-'.$first['calendar_year'],
                'total_receipts' => array_sum(array_column($chunk, 'total_receipts')),
                'total_payments' => array_sum(array_column($chunk, 'total_payments')),
                'net_change' => array_sum(array_column($chunk, 'net_change')),
                'beginning_balance' => $first['beginning_balance'],
                'ending_balance' => $last['ending_balance'],
            ];
        }

        $grandTotal = [
            'label' => 'FISCAL YEAR TOTALS '.$fiscalYearLabel,
            'total_receipts' => $grandReceipts,
            'total_payments' => $grandPayments,
            'net_change' => $grandReceipts - $grandPayments,
            'beginning_balance' => $beginningOfYear,
            'ending_balance' => $running,
        ];

        return [
            'fiscal_year_start' => $fiscalYearStartYear,
            'label' => 'FY '.$fiscalYearLabel,
            'taxonomy' => $this->taxonomy($masterParticularId, $particularIds),
            'months' => $months,
            'quarters' => $quarters,
            'grand_total' => $grandTotal,
            'totals_by_particular' => $totalsByParticular,
            'beginning_balance' => $beginningOfYear,
            'ending_balance' => $running,
            'total_receipts' => $grandReceipts,
            'total_payments' => $grandPayments,
        ];
    }

    /**
     * When a master particular / particular filter is active, only that subset is
     * returned - Beginning/Ending Balance (a real account cash position) stays
     * unaffected by these two filters (see cashBalanceThrough), so this narrows only
     * which rows/totals the receipts & payments breakdown shows.
     */
    private function taxonomy(?int $masterParticularId = null, ?array $particularIds = null): array
    {
        $masters = AcMasterParticular::query()
            ->active()
            ->when($masterParticularId, fn ($q) => $q->where('id', $masterParticularId))
            ->with(['particulars' => fn ($q) => $q->active()->orderBy('code')
                ->when($particularIds, fn ($q2) => $q2->whereIn('id', $particularIds))])
            ->orderBy('id')
            ->get();

        // With a particular filter active, drop master groups left with no matching
        // particulars rather than rendering an empty header row for them.
        if ($particularIds) {
            $masters = $masters->filter(fn ($master) => $master->particulars->isNotEmpty())->values();
        }

        return [
            'receipts' => $masters->where('type', AcMasterParticular::TYPE_DEBIT)->values(),
            'payments' => $masters->where('type', AcMasterParticular::TYPE_CREDIT)->values(),
        ];
    }

    private function groupReceipts(
        Carbon $start,
        Carbon $end,
        ?int $branchId,
        ?int $accountId = null,
        ?int $masterParticularId = null,
        ?array $particularIds = null,
    ): array {
        $rows = AcBalanceReceive::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->when($particularIds, fn ($q) => $q->whereIn('particular_id', $particularIds))
            ->when($masterParticularId, fn ($q) => $q->whereIn('particular_id', AcParticular::query()
                ->where('master_particular_id', $masterParticularId)->pluck('id')))
            ->whereBetween('receive_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('particular_id, receive_date, SUM(amount) as total')
            ->groupBy('particular_id', 'receive_date')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $date = Carbon::parse($row->receive_date)->toDateString();
            $grouped[$date][$row->particular_id] = (float) $row->total;
        }

        return $grouped;
    }

    private function groupPayments(
        Carbon $start,
        Carbon $end,
        ?int $branchId,
        ?int $accountId = null,
        ?int $masterParticularId = null,
        ?array $particularIds = null,
    ): array {
        $rows = AcExpenseDetail::query()
            ->join('ac_expenses', 'ac_expenses.id', '=', 'ac_expense_details.expense_id')
            ->when($branchId, fn ($q) => $q->where('ac_expenses.branch_id', $branchId))
            ->when($accountId, fn ($q) => $q->where('ac_expenses.account_id', $accountId))
            ->when($particularIds, fn ($q) => $q->whereIn('ac_expense_details.particular_id', $particularIds))
            ->when($masterParticularId, fn ($q) => $q->whereIn('ac_expense_details.particular_id', AcParticular::query()
                ->where('master_particular_id', $masterParticularId)->pluck('id')))
            ->whereBetween('ac_expenses.expense_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('ac_expense_details.particular_id as particular_id, ac_expenses.expense_date as expense_date, SUM(ac_expense_details.amount) as total')
            ->groupBy('ac_expense_details.particular_id', 'ac_expenses.expense_date')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $date = Carbon::parse($row->expense_date)->toDateString();
            $grouped[$date][$row->particular_id] = (float) $row->total;
        }

        return $grouped;
    }

    private function cashBalanceThrough(Carbon $date, ?int $branchId, ?int $accountId = null): float
    {
        $openingAccounts = (float) AcAccount::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($accountId, fn ($q) => $q->where('id', $accountId))
            ->sum('opening_balance');

        $receipts = (float) AcBalanceReceive::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->where('receive_date', '<=', $date->toDateString())
            ->sum('amount');

        $payments = (float) AcExpenseDetail::query()
            ->join('ac_expenses', 'ac_expenses.id', '=', 'ac_expense_details.expense_id')
            ->when($branchId, fn ($q) => $q->where('ac_expenses.branch_id', $branchId))
            ->when($accountId, fn ($q) => $q->where('ac_expenses.account_id', $accountId))
            ->where('ac_expenses.expense_date', '<=', $date->toDateString())
            ->sum('ac_expense_details.amount');

        return $openingAccounts + $receipts - $payments;
    }

    private function flattenByParticular(array $byDay): array
    {
        $totals = [];

        foreach ($byDay as $dayTotals) {
            $this->accumulate($totals, $dayTotals);
        }

        return $totals;
    }

    private function accumulate(array &$totals, array $amounts): void
    {
        foreach ($amounts as $particularId => $amount) {
            $totals[$particularId] = ($totals[$particularId] ?? 0) + $amount;
        }
    }
}
