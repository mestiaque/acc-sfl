<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * expense_ious.employee_id now identifies an HR employee (ME\Hr\Models\HrEmployee, table
 * hr_employees) rather than a system login user - the people who actually take cash
 * advances are factory/office employees, most of whom have no `users` row at all. The FK
 * to `users` is dropped rather than re-pointed at `hr_employees`, since the HR package is
 * an optional integration for this module, not a hard dependency: a hard FK to a table
 * that might not exist would break installs without HR present. Existence is instead
 * checked at the application layer (see ExpenseIouRequest, AcExpenseIou::employee()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ac_expense_ious', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });
    }

    public function down(): void
    {
        Schema::table('ac_expense_ious', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
