<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\View\View;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcBalanceReceive;
use ME\AccSfl\Models\AcExpense;
use ME\AccSfl\Models\AcExpenseIou;
use ME\AccSfl\Models\AcTransaction;

class DashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('ac_dashboard.view');

        $today = Carbon::today();

        $totalCashOnHand = (float) AcAccount::query()->active()->sum('current_balance');
        $todaysReceipts = (float) AcBalanceReceive::query()->whereDate('receive_date', $today)->sum('amount');
        $todaysExpenses = (float) AcExpense::query()->whereDate('expense_date', $today)->sum('total_amount');
        $pendingIous = AcExpenseIou::query()->pending();
        $pendingIouCount = (clone $pendingIous)->count();
        $pendingIouAmount = (float) (clone $pendingIous)->sum('amount');

        $recentTransactions = AcTransaction::query()
            ->with(['branch', 'account'])
            ->latest('id')
            ->limit(10)
            ->get();

        $accountBalances = AcAccount::query()->active()->with('branch')->orderBy('name')->get();

        return view('acc-sfl::admin.dashboard', compact(
            'totalCashOnHand',
            'todaysReceipts',
            'todaysExpenses',
            'pendingIouCount',
            'pendingIouAmount',
            'recentTransactions',
            'accountBalances',
        ));
    }
}
