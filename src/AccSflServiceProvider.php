<?php

namespace ME\AccSfl;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcBalanceReceive;
use ME\AccSfl\Models\AcExpense;
use ME\AccSfl\Models\AcExpenseIou;
use ME\AccSfl\Observers\AcAccountObserver;
use ME\AccSfl\Observers\AcBalanceReceiveObserver;
use ME\AccSfl\Observers\AcExpenseIouObserver;
use ME\AccSfl\Observers\AcExpenseObserver;
use ME\AccSfl\Services\CashFlowReportService;
use ME\AccSfl\Services\TransactionService;
use ME\AccSfl\Services\VoucherNumberService;

class AccSflServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'acc-sfl');
        $this->loadTranslationsFrom(__DIR__.'/resources/lang', 'acc-sfl');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->publishes([__DIR__.'/public' => public_path('/')], 'acc-sfl-assets');

        // Sidebar entries are a numeric array — must array_merge, not mergeConfigFrom
        if (file_exists($sidebar = __DIR__.'/Config/sidebar.php')) {
            Config::set('sidebar', array_merge(
                config('sidebar', []),
                require $sidebar
            ));
        }

        $this->mergeAccPermissions();
        $this->registerMorphMap();
        $this->registerObservers();
    }

    public function register(): void
    {
        if (file_exists(__DIR__.'/Config/config.php')) {
            $this->mergeConfigFrom(__DIR__.'/Config/config.php', 'acc-sfl');
        }

        $this->app->singleton(VoucherNumberService::class);
        $this->app->singleton(TransactionService::class);
        $this->app->singleton(CashFlowReportService::class);
    }

    private function mergeAccPermissions(): void
    {
        if (! file_exists($file = __DIR__.'/Config/permission.php')) {
            return;
        }

        $accPermissions = require $file;
        $main = config('permission', []);
        $main['modules'] = $main['modules'] ?? [];

        // The host's config/permission.php nests every group under a top-level
        // 'modules' key (config('permission.modules.<Group>')) — both the Roles Setup
        // screen and hasChildPermission()/hasPermission() read it from there, so merging
        // onto the bare top level (as this used to do) left the group invisible everywhere.
        foreach ($accPermissions as $group => $modules) {
            $main['modules'][$group] = $modules;
        }

        Config::set('permission', $main);
    }

    private function registerMorphMap(): void
    {
        Relation::morphMap([
            'ac_balance_receive' => AcBalanceReceive::class,
            'ac_expense' => AcExpense::class,
            'ac_expense_iou' => AcExpenseIou::class,
            'ac_account' => AcAccount::class,
        ]);
    }

    private function registerObservers(): void
    {
        AcAccount::observe(AcAccountObserver::class);
        AcBalanceReceive::observe(AcBalanceReceiveObserver::class);
        AcExpense::observe(AcExpenseObserver::class);
        AcExpenseIou::observe(AcExpenseIouObserver::class);
    }
}
