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
            $todaysReceipts = (float) AcBalanceReceive::query()->whereDate('receive_date', $today)->sum('amount');
            $todaysExpenses = (float) AcExpense::query()->whereDate('expense_date', $today)->sum('total_amount');
            $monthReceipts = (float) AcBalanceReceive::query()->whereDate('receive_date', '>=', $monthStart)->sum('amount');
            $monthExpenses = (float) AcExpense::query()->whereDate('expense_date', '>=', $monthStart)->sum('total_amount');

            $pendingIous = AcExpenseIou::query()->pending();
            $pendingIouCount = (clone $pendingIous)->count();
            $pendingIouAmount = (float) (clone $pendingIous)->sum('amount');

            $adjustedThisMonth = AcExpenseIou::query()->adjusted()->whereDate('adjust_date', '>=', $monthStart);
            $adjustedIouCount = (clone $adjustedThisMonth)->count();
            $adjustedIouAmount = (float) (clone $adjustedThisMonth)->sum('amount');

            // ── Last 30 days cash flow (receipts vs payments) ──
            $last30 = collect();
            for ($i = 29; $i >= 0; $i--) {
                $date = $today->copy()->subDays($i);
                $receipts = (float) AcBalanceReceive::query()->whereDate('receive_date', $date)->sum('amount');
                $payments = (float) AcExpense::query()->whereDate('expense_date', $date)->sum('total_amount');
                $last30->push([
                    'date' => $date->toDateString(),
                    'label' => $date->format('d M'),
                    'receipts' => $receipts,
                    'payments' => $payments,
                ]);
            }

            // ── Last 6 months cash flow trend ──
            $monthlyTrend = collect();
            for ($i = 5; $i >= 0; $i--) {
                $month = $today->copy()->subMonthsNoOverflow($i);
                $receipts = (float) AcBalanceReceive::query()
                    ->whereYear('receive_date', $month->year)->whereMonth('receive_date', $month->month)
                    ->sum('amount');
                $payments = (float) AcExpense::query()
                    ->whereYear('expense_date', $month->year)->whereMonth('expense_date', $month->month)
                    ->sum('total_amount');
                $monthlyTrend->push([
                    'label' => $month->format('M Y'),
                    'receipts' => $receipts,
                    'payments' => $payments,
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
                ->selectRaw('ac_master_particulars.name as name, SUM(ac_expense_details.amount) as total')
                ->groupBy('ac_master_particulars.name')
                ->orderByDesc('total')
                ->limit(6)
                ->get()
                ->map(fn ($row) => ['name' => $row->name, 'amount' => (float) $row->total]);

            $recentTransactions = AcTransaction::query()
                ->with(['branch', 'account'])
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
                'totalCashOnHand', 'todaysReceipts', 'todaysExpenses', 'monthReceipts', 'monthExpenses',
                'pendingIouCount', 'pendingIouAmount', 'adjustedIouCount', 'adjustedIouAmount',
                'last30', 'monthlyTrend', 'branchBalances', 'topExpenseCategories',
                'recentTransactions', 'recentBalanceReceives', 'recentExpenses', 'accountBalances',
            );
        } catch (\Throwable $e) {
            return [
                'totalCashOnHand' => 0, 'todaysReceipts' => 0, 'todaysExpenses' => 0,
                'monthReceipts' => 0, 'monthExpenses' => 0,
                'pendingIouCount' => 0, 'pendingIouAmount' => 0, 'adjustedIouCount' => 0, 'adjustedIouAmount' => 0,
                'last30' => collect(), 'monthlyTrend' => collect(), 'branchBalances' => collect(), 'topExpenseCategories' => collect(),
                'recentTransactions' => collect(), 'recentBalanceReceives' => collect(), 'recentExpenses' => collect(), 'accountBalances' => collect(),
            ];
        }
    }
}
