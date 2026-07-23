<?php

use Illuminate\Support\Facades\Route;
use ME\AccSfl\Http\Controllers\AccountController;
use ME\AccSfl\Http\Controllers\BalanceReceiveController;
use ME\AccSfl\Http\Controllers\BranchController;
use ME\AccSfl\Http\Controllers\DashboardController;
use ME\AccSfl\Http\Controllers\ExpenseController;
use ME\AccSfl\Http\Controllers\ExpenseIouController;
use ME\AccSfl\Http\Controllers\MasterParticularController;
use ME\AccSfl\Http\Controllers\ParticularController;
use ME\AccSfl\Http\Controllers\PaymentMethodController;
use ME\AccSfl\Http\Controllers\ReportController;
use ME\AccSfl\Http\Controllers\TransactionController;

$route = config('acc-sfl.route');

Route::middleware($route['middleware'] ?? ['web', 'auth'])
    ->prefix($route['prefix'] ?? 'admin/acc-sfl')
    ->name($route['as'] ?? 'acc-sfl.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('branches', BranchController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('branches/print', [BranchController::class, 'print'])->name('branches.print');
        Route::get('branches/export', [BranchController::class, 'export'])->name('branches.export');

        Route::resource('payment-methods', PaymentMethodController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('payment-methods/print', [PaymentMethodController::class, 'print'])->name('payment-methods.print');
        Route::get('payment-methods/export', [PaymentMethodController::class, 'export'])->name('payment-methods.export');

        Route::resource('accounts', AccountController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('accounts/print', [AccountController::class, 'print'])->name('accounts.print');
        Route::get('accounts/export', [AccountController::class, 'export'])->name('accounts.export');

        Route::resource('master-particulars', MasterParticularController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('master-particulars/print', [MasterParticularController::class, 'print'])->name('master-particulars.print');
        Route::get('master-particulars/export', [MasterParticularController::class, 'export'])->name('master-particulars.export');

        Route::resource('particulars', ParticularController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('particulars/print', [ParticularController::class, 'print'])->name('particulars.print');
        Route::get('particulars/export', [ParticularController::class, 'export'])->name('particulars.export');

        // ->parameters() override needed: Str::singular('balance-receives') incorrectly
        // yields 'balance-receife', which would break the {balance_receive} route model
        // binding used by BalanceReceiveController and BalanceReceiveRequest.
        Route::resource('balance-receives', BalanceReceiveController::class)
            ->parameters(['balance-receives' => 'balance_receive'])
            ->only(['index', 'store', 'update', 'destroy']);
        Route::get('balance-receives/print', [BalanceReceiveController::class, 'print'])->name('balance-receives.print');
        Route::get('balance-receives/export', [BalanceReceiveController::class, 'export'])->name('balance-receives.export');

        Route::resource('expenses', ExpenseController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::get('expenses/print', [ExpenseController::class, 'print'])->name('expenses.print');
        Route::get('expenses/export', [ExpenseController::class, 'export'])->name('expenses.export');

        Route::resource('expense-ious', ExpenseIouController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('expense-ious/print', [ExpenseIouController::class, 'print'])->name('expense-ious.print');
        Route::get('expense-ious/export', [ExpenseIouController::class, 'export'])->name('expense-ious.export');
        Route::post('expense-ious/{expense_iou}/adjust', [ExpenseIouController::class, 'adjust'])->name('expense-ious.adjust');

        Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('transactions/print', [TransactionController::class, 'print'])->name('transactions.print');
        Route::get('transactions/export', [TransactionController::class, 'export'])->name('transactions.export');

        Route::prefix('reports/cash-flow')->name('reports.cash-flow.')->group(function () {
            Route::get('monthly', [ReportController::class, 'monthly'])->name('monthly');
            Route::get('monthly/print', [ReportController::class, 'monthlyPrint'])->name('monthly.print');
            Route::get('monthly/export', [ReportController::class, 'monthlyExport'])->name('monthly.export');
            Route::get('yearly', [ReportController::class, 'yearly'])->name('yearly');
            Route::get('yearly/print', [ReportController::class, 'yearlyPrint'])->name('yearly.print');
            Route::get('yearly/export', [ReportController::class, 'yearlyExport'])->name('yearly.export');
        });
    });
