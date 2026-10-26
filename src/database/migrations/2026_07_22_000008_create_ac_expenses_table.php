<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ac_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_no');
            $table->date('expense_date');
            $table->date('billing_date')->nullable();
            $table->unsignedBigInteger('payment_method_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('account_id');
            $table->string('company_name')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_mobile')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('iou_id')->nullable();
            $table->unsignedBigInteger('hr_other_transaction_id')->nullable();
            $table->string('invoice')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->text('description')->nullable();
            $table->string('attachment')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['expense_date', 'branch_id']);
            $table->unique('expense_no');
            $table->index('status');

            $table->foreign('account_id')->references('id')->on('ac_accounts')->restrictOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('branch_id')->references('id')->on('ac_branches')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('iou_id')->references('id')->on('ac_expense_ious')->nullOnDelete();
            $table->foreign('payment_method_id')->references('id')->on('ac_payment_methods')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ac_expenses');
    }
};
