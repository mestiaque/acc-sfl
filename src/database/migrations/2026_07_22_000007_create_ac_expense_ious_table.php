<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ac_expense_ious', function (Blueprint $table) {
            $table->id();
            $table->string('iou_no');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('payment_method_id');
            $table->unsignedBigInteger('branch_id');
            $table->date('issue_date');
            $table->date('adjust_date')->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('settlement_amount', 15, 2)->nullable();
            $table->unsignedBigInteger('settlement_payment_method_id')->nullable();
            $table->text('description')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_mobile')->nullable();
            $table->enum('status', ['Pending', 'Adjusted'])->default('Pending');
            $table->string('approval_status')->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('approval_status');
            $table->index('employee_id', 'ac_expense_ious_employee_id_foreign');
            $table->unique('iou_no');
            $table->index(['issue_date', 'branch_id']);

            $table->foreign('account_id')->references('id')->on('ac_accounts')->restrictOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('branch_id')->references('id')->on('ac_branches')->restrictOnDelete();
            $table->foreign('payment_method_id')->references('id')->on('ac_payment_methods')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ac_expense_ious');
    }
};
