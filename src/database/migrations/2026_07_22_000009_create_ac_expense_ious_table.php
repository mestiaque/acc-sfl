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
            $table->string('iou_no')->unique();
            $table->foreignId('account_id')->constrained('ac_accounts')->restrictOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('payment_method_id')->constrained('ac_payment_methods')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('ac_branches')->restrictOnDelete();
            $table->date('issue_date');
            $table->date('adjust_date')->nullable();
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_mobile')->nullable();
            $table->enum('status', ['Pending', 'Adjusted'])->default('Pending');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['issue_date', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ac_expense_ious');
    }
};
