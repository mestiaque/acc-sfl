<?php

use Illuminate\Support\Facades\Route;
use ME\AccSfl\Http\Controllers\AccountController;
use ME\AccSfl\Http\Controllers\BalanceReceiveController;
use ME\AccSfl\Http\Controllers\BalanceReceiveImportController;
use ME\AccSfl\Http\Controllers\BalanceReceiveReportController;
use ME\AccSfl\Http\Controllers\BranchController;
use ME\AccSfl\Http\Controllers\DashboardController;
use ME\AccSfl\Http\Controllers\ExpenseController;
use ME\AccSfl\Http\Controllers\ExpenseImportController;
use ME\AccSfl\Http\Controllers\ExpenseIouController;
use ME\AccSfl\Http\Controllers\ExpenseIouReportController;
use ME\AccSfl\Http\Controllers\ExpenseReportController;
use ME\AccSfl\Http\Controllers\FiscalYearController;
use ME\AccSfl\Http\Controllers\ImportTemplateController;
use ME\AccSfl\Http\Controllers\MasterParticularController;
use ME\AccSfl\Http\Controllers\ParticularController;
use ME\AccSfl\Http\Controllers\PaymentMethodController;
use ME\AccSfl\Http\Controllers\ReportController;
use ME\AccSfl\Http\Controllers\StatementReportController;
use ME\AccSfl\Http\Controllers\TransactionController;
use ME\AccSfl\Http\Controllers\TransactionReportController;

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

        Route::resource('fiscal-years', FiscalYearController::class)
            ->parameters(['fiscal-years' => 'fiscal_year'])
            ->only(['index', 'store', 'update', 'destroy']);
        Route::get('fiscal-years/print', [FiscalYearController::class, 'print'])->name('fiscal-years.print');
        Route::get('fiscal-years/export', [FiscalYearController::class, 'export'])->name('fiscal-years.export');

        Route::resource('accounts', AccountController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('accounts/print', [AccountController::class, 'print'])->name('accounts.print');
        Route::get('accounts/export', [AccountController::class, 'export'])->name('accounts.export');

        Route::resource('master-particulars', MasterParticularController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('master-particulars/print', [MasterParticularController::class, 'print'])->name('master-particulars.print');
        Route::get('master-particulars/export', [MasterParticularController::class, 'export'])->name('master-particulars.export');

        Route::resource('particulars', ParticularController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('particulars/print', [ParticularController::class, 'print'])->name('particulars.print');
        Route::get('particulars/export', [ParticularController::class, 'export'])->name('particulars.export');

        Route::get('import-template', [ImportTemplateController::class, 'download'])->name('import-template.download');

        // ->parameters() override needed: Str::singular('balance-receives') incorrectly
        // yields 'balance-receife', which would break the {balance_receive} route model
        // binding used by BalanceReceiveController and BalanceReceiveRequest.
        Route::resource('balance-receives', BalanceReceiveController::class)
            ->parameters(['balance-receives' => 'balance_receive'])
            ->only(['index', 'store', 'update', 'destroy']);
        Route::get('balance-receives/print', [BalanceReceiveController::class, 'print'])->name('balance-receives.print');
        Route::get('balance-receives/export', [BalanceReceiveController::class, 'export'])->name('balance-receives.export');
        Route::get('balance-receives/import', [BalanceReceiveImportController::class, 'create'])->name('balance-receives.import');
        Route::post('balance-receives/import/preview', [BalanceReceiveImportController::class, 'preview'])->name('balance-receives.import.preview');
        Route::post('balance-receives/import/save', [BalanceReceiveImportController::class, 'save'])->name('balance-receives.import.save');
        Route::post('balance-receives/import/recheck', [BalanceReceiveImportController::class, 'recheck'])->name('balance-receives.import.recheck');

        // Registered before the resource's GET {expense} show route so the literal
        // "import" segment isn't swallowed as an expense id.
        Route::get('expenses/import', [ExpenseImportController::class, 'create'])->name('expenses.import');
        Route::resource('expenses', ExpenseController::class)->only(['index', 'create', 'edit', 'show', 'store', 'update', 'destroy']);
        Route::get('expenses/print', [ExpenseController::class, 'print'])->name('expenses.print');
        Route::get('expenses/{expense}/slip', [ExpenseController::class, 'slip'])->name('expenses.slip');
        Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve');
        Route::post('expenses/{expense}/reject', [ExpenseController::class, 'reject'])->name('expenses.reject');
        Route::delete('expenses/{expense}/attachments/{file}', [ExpenseController::class, 'destroyAttachment'])->name('expenses.attachments.destroy');
        Route::delete('expenses/{expense}/force-delete', [ExpenseController::class, 'forceDestroy'])->name('expenses.force-delete');
        Route::get('expenses/export', [ExpenseController::class, 'export'])->name('expenses.export');
        Route::post('expenses/import/preview', [ExpenseImportController::class, 'preview'])->name('expenses.import.preview');
        Route::post('expenses/import/save', [ExpenseImportController::class, 'save'])->name('expenses.import.save');
        Route::post('expenses/import/recheck', [ExpenseImportController::class, 'recheck'])->name('expenses.import.recheck');

        Route::resource('expense-ious', ExpenseIouController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('expense-ious/print', [ExpenseIouController::class, 'print'])->name('expense-ious.print');
        Route::get('expense-ious/export', [ExpenseIouController::class, 'export'])->name('expense-ious.export');
        Route::post('expense-ious/{expense_iou}/adjust', [ExpenseIouController::class, 'adjust'])->name('expense-ious.adjust');
        Route::delete('expense-ious/{expense_iou}/attachments/{file}', [ExpenseIouController::class, 'destroyAttachment'])->name('expense-ious.attachments.destroy');
        Route::delete('expense-ious/{expense_iou}/force-delete', [ExpenseIouController::class, 'forceDestroy'])->name('expense-ious.force-delete');

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

        Route::prefix('reports/balance-receive')->name('reports.balance-receive.')->group(function () {
            Route::get('/', [BalanceReceiveReportController::class, 'index'])->name('index');
            Route::get('print', [BalanceReceiveReportController::class, 'print'])->name('print');
            Route::get('export', [BalanceReceiveReportController::class, 'export'])->name('export');
        });

        Route::prefix('reports/expense')->name('reports.expense.')->group(function () {
            Route::get('/', [ExpenseReportController::class, 'index'])->name('index');
            Route::get('print', [ExpenseReportController::class, 'print'])->name('print');
            Route::get('export', [ExpenseReportController::class, 'export'])->name('export');
        });

        Route::prefix('reports/expense-iou')->name('reports.expense-iou.')->group(function () {
            Route::get('/', [ExpenseIouReportController::class, 'index'])->name('index');
            Route::get('print', [ExpenseIouReportController::class, 'print'])->name('print');
            Route::get('export', [ExpenseIouReportController::class, 'export'])->name('export');
        });

        Route::prefix('reports/transaction')->name('reports.transaction.')->group(function () {
            Route::get('/', [TransactionReportController::class, 'index'])->name('index');
            Route::get('print', [TransactionReportController::class, 'print'])->name('print');
            Route::get('export', [TransactionReportController::class, 'export'])->name('export');
        });

        Route::prefix('reports/statement')->name('reports.statement.')->group(function () {
            Route::get('/', [StatementReportController::class, 'index'])->name('index');
            Route::get('print', [StatementReportController::class, 'print'])->name('print');
            Route::get('export', [StatementReportController::class, 'export'])->name('export');
        });
    });
