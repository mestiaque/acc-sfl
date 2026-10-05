<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An IOU is cash handed to an employee to buy something; what they actually bought is then
 * recorded as one or more Expenses against it (ac_expenses.iou_id). Those expenses post no
 * cash of their own - the cash already left at IOU issue. On adjust, the difference between
 * the IOU amount and its approved expenses is settled in cash (settlement_amount: positive =
 * returned by the employee, negative = extra paid to them), using
 * settlement_payment_method_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ac_expenses', function (Blueprint $table) {
            $table->foreignId('iou_id')->nullable()->after('employee_id')
                ->constrained('ac_expense_ious')->nullOnDelete();
        });

        Schema::table('ac_expense_ious', function (Blueprint $table) {
            $table->decimal('settlement_amount', 15, 2)->nullable()->after('amount');
            $table->unsignedBigInteger('settlement_payment_method_id')->nullable()->after('settlement_amount');
        });
    }

    public function down(): void
    {
        Schema::table('ac_expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('iou_id');
        });

        Schema::table('ac_expense_ious', function (Blueprint $table) {
            $table->dropColumn(['settlement_amount', 'settlement_payment_method_id']);
        });
    }
};
