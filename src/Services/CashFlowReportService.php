<?php

namespace ME\AccSfl\Services;

use Illuminate\Support\Carbon;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcBalanceReceive;
use ME\AccSfl\Models\AcExpenseDetail;
use ME\AccSfl\Models\AcMasterParticular;

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
    public function monthly(int $year, int $month, ?int $branchId = null): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $daysInMonth = (int) $start->daysInMonth;

        $receiptsByDay = $this->groupReceipts($start, $start->copy()->endOfMonth(), $branchId);
        $paymentsByDay = $this->groupPayments($start, $start->copy()->endOfMonth(), $branchId);

        $running = $this->cashBalanceThrough($start->copy()->subDay(), $branchId);
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
            'taxonomy' => $this->taxonomy(),
            'days' => $days,
            'totals_by_particular' => $totalsByParticular,
            'beginning_balance' => $beginningOfMonth,
            'ending_balance' => $running,
            'total_receipts' => $grandReceipts,
            'total_payments' => $grandPayments,
        ];
    }

    /**
     * Fiscal year is treated as July -> June (common for BD RMG), with four sequential
     * quarters of 3 months each starting from the fiscal year start month.
     */
    public function yearly(int $fiscalYearStartYear, ?int $branchId = null): array
    {
        $fyStart = Carbon::create($fiscalYearStartYear, 7, 1)->startOfMonth();

        $running = $this->cashBalanceThrough($fyStart->copy()->subDay(), $branchId);
        $beginningOfYear = $running;

        $months = [];
        $totalsByParticular = [];
        $grandReceipts = 0.0;
        $grandPayments = 0.0;

        for ($m = 0; $m < 12; $m++) {
            $monthStart = $fyStart->copy()->addMonthsNoOverflow($m)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            $monthReceiptTotals = $this->flattenByParticular($this->groupReceipts($monthStart, $monthEnd, $branchId));
            $monthPaymentTotals = $this->flattenByParticular($this->groupPayments($monthStart, $monthEnd, $branchId));

            $totalReceipts = array_sum($monthReceiptTotals);
            $totalPayments = array_sum($monthPaymentTotals);

            $months[$m] = [
                'label' => $monthStart->format('M-y'),
                'quarter' => intdiv($m, 3) + 1,
                'beginning_balance' => $running,
                'receipts' => $monthReceiptTotals,
                'total_receipts' => $totalReceipts,
                'payments' => $monthPaymentTotals,
                'total_payments' => $totalPayments,
                'ending_balance' => $running + $totalReceipts - $totalPayments,
            ];

            $this->accumulate($totalsByParticular, $monthReceiptTotals);
            $this->accumulate($totalsByParticular, $monthPaymentTotals);

            $grandReceipts += $totalReceipts;
            $grandPayments += $totalPayments;
            $running = $months[$m]['ending_balance'];
        }

        $quarters = [];
        foreach (array_chunk($months, 3) as $qi => $chunk) {
            $quarters[$qi + 1] = [
                'total_receipts' => array_sum(array_column($chunk, 'total_receipts')),
                'total_payments' => array_sum(array_column($chunk, 'total_payments')),
                'beginning_balance' => $chunk[array_key_first($chunk)]['beginning_balance'],
                'ending_balance' => $chunk[array_key_last($chunk)]['ending_balance'],
            ];
        }

        return [
            'fiscal_year_start' => $fiscalYearStartYear,
            'label' => "FY {$fiscalYearStartYear}-".substr((string) ($fiscalYearStartYear + 1), -2),
            'taxonomy' => $this->taxonomy(),
            'months' => $months,
            'quarters' => $quarters,
            'totals_by_particular' => $totalsByParticular,
            'beginning_balance' => $beginningOfYear,
            'ending_balance' => $running,
            'total_receipts' => $grandReceipts,
            'total_payments' => $grandPayments,
        ];
    }

    private function taxonomy(): array
    {
        $masters = AcMasterParticular::query()
            ->active()
            ->with(['particulars' => fn ($q) => $q->active()->orderBy('id')])
            ->orderBy('id')
            ->get();

        return [
            'receipts' => $masters->where('type', AcMasterParticular::TYPE_DEBIT)->values(),
            'payments' => $masters->where('type', AcMasterParticular::TYPE_CREDIT)->values(),
        ];
    }

    private function groupReceipts(Carbon $start, Carbon $end, ?int $branchId): array
    {
        $rows = AcBalanceReceive::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
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

    private function groupPayments(Carbon $start, Carbon $end, ?int $branchId): array
    {
        $rows = AcExpenseDetail::query()
            ->join('ac_expenses', 'ac_expenses.id', '=', 'ac_expense_details.expense_id')
            ->when($branchId, fn ($q) => $q->where('ac_expenses.branch_id', $branchId))
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

    private function cashBalanceThrough(Carbon $date, ?int $branchId): float
    {
        $openingAccounts = (float) AcAccount::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('opening_balance');

        $receipts = (float) AcBalanceReceive::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('receive_date', '<=', $date->toDateString())
            ->sum('amount');

        $payments = (float) AcExpenseDetail::query()
            ->join('ac_expenses', 'ac_expenses.id', '=', 'ac_expense_details.expense_id')
            ->when($branchId, fn ($q) => $q->where('ac_expenses.branch_id', $branchId))
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
