<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * billing_date is the date on the bill itself (which day's bill it is). It is informational
 * only - expense_date stays the date the expense is booked/shown on, and everything that
 * touches the ledger or reports keeps using expense_date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ac_expenses', function (Blueprint $table) {
            $table->date('billing_date')->nullable()->after('expense_date');
        });
    }

    public function down(): void
    {
        Schema::table('ac_expenses', function (Blueprint $table) {
            $table->dropColumn('billing_date');
        });
    }
};
