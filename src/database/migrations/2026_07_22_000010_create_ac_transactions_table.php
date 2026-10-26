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
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->decimal('debit', 15, 2)->default(0.00);
            $table->decimal('credit', 15, 2)->default(0.00);
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['account_id', 'transaction_date']);
            $table->index(['reference_type', 'reference_id']);

            $table->foreign('account_id')->references('id')->on('ac_accounts')->restrictOnDelete();
            $table->foreign('branch_id')->references('id')->on('ac_branches')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('payment_method_id')->references('id')->on('ac_payment_methods')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ac_transactions');
    }
};
