<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identifies an HR employee (ME\Hr\Models\HrEmployee, table hr_employees), not a system
 * login user - mirrors ac_expense_ious.employee_id (see
 * 2026_07_28_000001_drop_employee_fk_on_ac_expense_ious_table.php). No FK constraint since
 * the HR package is an optional integration for this module, not a hard dependency.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ac_expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable()->after('receiver_mobile');
        });
    }

    public function down(): void
    {
        Schema::table('ac_expenses', function (Blueprint $table) {
            $table->dropColumn('employee_id');
        });
    }
};
