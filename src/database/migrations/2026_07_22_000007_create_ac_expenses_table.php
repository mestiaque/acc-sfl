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
            $table->string('expense_no')->unique();
            $table->date('expense_date');
            $table->foreignId('payment_method_id')->constrained('ac_payment_methods')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('ac_branches')->restrictOnDelete();
            $table->foreignId('account_id')->constrained('ac_accounts')->restrictOnDelete();
            $table->string('company_name')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_mobile')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('attachment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['expense_date', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ac_expenses');
    }
};
