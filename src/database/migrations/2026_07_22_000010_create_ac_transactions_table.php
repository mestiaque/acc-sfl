<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ac_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->string('transaction_type');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->foreignId('branch_id')->constrained('ac_branches')->restrictOnDelete();
            $table->foreignId('account_id')->constrained('ac_accounts')->restrictOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('ac_payment_methods')->nullOnDelete();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
            $table->index(['account_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ac_transactions');
    }
};
