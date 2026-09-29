<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * is_salary_advance marks a particular (e.g. 3002 - SALARY-ADVANCE) whose expense lines are
 * pushed to the employee's HR Earnings & Deductions (Advance/IOU) once the expense is approved.
 * hr_other_transaction_id links the expense to that HR row (hr_employee_other_transactions) so a
 * force delete can remove it again. No FK since the HR package is an optional integration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ac_particulars', function (Blueprint $table) {
            $table->boolean('is_salary_advance')->default(false)->after('description');
        });

        Schema::table('ac_expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('hr_other_transaction_id')->nullable()->after('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('ac_particulars', function (Blueprint $table) {
            $table->dropColumn('is_salary_advance');
        });

        Schema::table('ac_expenses', function (Blueprint $table) {
            $table->dropColumn('hr_other_transaction_id');
        });
    }
};
