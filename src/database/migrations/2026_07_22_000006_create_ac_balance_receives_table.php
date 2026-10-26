<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ac_balance_receives', function (Blueprint $table) {
            $table->id();
            $table->string('receive_no');
            $table->date('receive_date');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('particular_id');
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->text('description')->nullable();
            $table->string('attachment')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['receive_date', 'branch_id']);
            $table->unique('receive_no');
            $table->index('status');

            $table->foreign('account_id')->references('id')->on('ac_accounts')->restrictOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('branch_id')->references('id')->on('ac_branches')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('particular_id')->references('id')->on('ac_particulars')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ac_balance_receives');
    }
};
