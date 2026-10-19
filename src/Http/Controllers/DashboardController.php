<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\View\View;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcBalanceReceive;
use ME\AccSfl\Models\AcBranch;
use ME\AccSfl\Models\AcExpense;
use ME\AccSfl\Models\AcExpenseDetail;
use ME\AccSfl\Models\AcExpenseIou;
use ME\AccSfl\Models\AcTransaction;

class DashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('ac_dashboard.view');

        return view('acc-sfl::admin.dashboard');
    }

    /**
     * Feeds both this module's own full-page dashboard and the shared "dashboard-widget"
     * partial included on the host app's main admin dashboard (mirrors the
     * mestiaque/hr package's HrDashboardController::stats() convention). Wrapped in a
     * try/catch, since a failure here must never break the shared host dashboard for
     * every other module - an empty/zeroed widget is far better than a fatal error.
     */
    public static function stats(): array
    {
        try {
            $today = Carbon::today();
            $monthStart = $today->copy()->startOfMonth();

            $totalCashOnHand = (float) AcAccount::query()->active()->sum('current_balance');
            // Only approved entries touch an account — pending / rejected receives and expenses are left out.
            $todaysReceipts = (float) AcBalanceReceive::query()->approved()->whereDate('receive_date', $today)->sum('amount');
            $todaysExpenses = (float) AcExpense::query()->approved()->whereDate('expense_date', $today)->sum('total_amount');
            $monthReceipts = (float) AcBalanceReceive::query()->approved()->whereDate('receive_date', '>=', $monthStart)->sum('amount');
            $monthExpenses = (float) AcExpense::query()->approved()->whereDate('expense_date', '>=', $monthStart)->sum('total_amount');

            // Cash in / out as posted to the ledger (posted only on approval): receives, expenses,
            // IOU issue / settlement — the opening balance is not a movement of the period.
            $ledger = fn () => AcTransaction::query()->where('transaction_type', '!=', AcTransaction::TYPE_OPENING_BALANCE);
            $monthNetCash = (float) $ledger()->whereDate('transaction_date', '>=', $monthStart)->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) net')->value('net');

            $pendingIous = AcExpenseIou::query()->pending();
            $pendingIouCount = (clone $pendingIous)->count();
            $pendingIouAmount = (float) (clone $pendingIous)->sum('amount');

            $adjustedThisMonth = AcExpenseIou::query()->adjusted()->whereDate('adjust_date', '>=', $monthStart);
            $adjustedIouCount = (clone $adjustedThisMonth)->count();
            $adjustedIouAmount = (float) (clone $adjustedThisMonth)->sum('amount');

            // ── Last 30 days cash flow (receipts vs payments) ──
            $byDay = $ledger()->whereDate('transaction_date', '>=', $today->copy()->subDays(29))
                ->selectRaw('DATE(transaction_date) d, SUM(debit) receipts, SUM(credit) payments')->groupByRaw('DATE(transaction_date)')->get()->keyBy('d');
            $last30 = collect();
            for ($i = 29; $i >= 0; $i--) {
                $date = $today->copy()->subDays($i);
                $last30->push([
                    'date' => $date->toDateString(),
                    'label' => $date->format('d M'),
                    'receipts' => (float) ($byDay[$date->toDateString()]->receipts ?? 0),
                    'payments' => (float) ($byDay[$date->toDateString()]->payments ?? 0),
                ]);
            }

            // ── Last 6 months cash flow trend ──
            $monthlyTrend = collect();
            for ($i = 5; $i >= 0; $i--) {
                $month = $today->copy()->subMonthsNoOverflow($i);
                $row = $ledger()->whereYear('transaction_date', $month->year)->whereMonth('transaction_date', $month->month)
                    ->selectRaw('COALESCE(SUM(debit), 0) receipts, COALESCE(SUM(credit), 0) payments')->first();
                $monthlyTrend->push([
                    'label' => $month->format('M Y'),
                    'receipts' => (float) $row->receipts,
                    'payments' => (float) $row->payments,
                ]);
            }

            // ── Cash on hand by branch ──
            $branchBalances = AcBranch::query()->active()
                ->withSum(['accounts as balance' => fn ($q) => $q->active()], 'current_balance')
                ->orderByDesc('balance')
                ->limit(8)
                ->get(['id', 'name'])
                ->map(fn ($branch) => ['name' => $branch->name, 'balance' => (float) ($branch->balance ?? 0)]);

            // ── Top expense categories this month (by master particular) ──
            $topExpenseCategories = AcExpenseDetail::query()
                ->join('ac_expenses', 'ac_expenses.id', '=', 'ac_expense_details.expense_id')
                ->join('ac_particulars', 'ac_particulars.id', '=', 'ac_expense_details.particular_id')
                ->join('ac_master_particulars', 'ac_master_particulars.id', '=', 'ac_particulars.master_particular_id')
                ->whereDate('ac_expenses.expense_date', '>=', $monthStart)
                ->where('ac_expenses.status', AcExpense::STATUS_APPROVED)
                ->selectRaw('ac_master_particulars.name as name, SUM(ac_expense_details.amount) as total')
                ->groupBy('ac_master_particulars.name')
                ->orderByDesc('total')
                ->limit(6)
                ->get()
                ->map(fn ($row) => ['name' => $row->name, 'amount' => (float) $row->total]);

            $recentTransactions = AcTransaction::query()
                ->with(['branch', 'account'])
                ->withRunningBalance()
                ->latest('id')
                ->limit(8)
                ->get();

            $recentBalanceReceives = AcBalanceReceive::query()
                ->with(['branch', 'particular'])
                ->latest('id')
                ->limit(6)
                ->get();

            $recentExpenses = AcExpense::query()
                ->with(['branch', 'details.particular'])
                ->latest('id')
                ->limit(6)
                ->get();

            $accountBalances = AcAccount::query()->active()->with('branch')->orderByDesc('current_balance')->get();

            return compact(
                'totalCashOnHand', 'todaysReceipts', 'todaysExpenses', 'monthReceipts', 'monthExpenses', 'monthNetCash',
                'pendingIouCount', 'pendingIouAmount', 'adjustedIouCount', 'adjustedIouAmount',
                'last30', 'monthlyTrend', 'branchBalances', 'topExpenseCategories',
                'recentTransactions', 'recentBalanceReceives', 'recentExpenses', 'accountBalances',
            );
        } catch (\Throwable $e) {
            return [
                'totalCashOnHand' => 0, 'todaysReceipts' => 0, 'todaysExpenses' => 0,
                'monthReceipts' => 0, 'monthExpenses' => 0, 'monthNetCash' => 0,
                'pendingIouCount' => 0, 'pendingIouAmount' => 0, 'adjustedIouCount' => 0, 'adjustedIouAmount' => 0,
                'last30' => collect(), 'monthlyTrend' => collect(), 'branchBalances' => collect(), 'topExpenseCategories' => collect(),
                'recentTransactions' => collect(), 'recentBalanceReceives' => collect(), 'recentExpenses' => collect(), 'accountBalances' => collect(),
            ];
        }
    }
}
