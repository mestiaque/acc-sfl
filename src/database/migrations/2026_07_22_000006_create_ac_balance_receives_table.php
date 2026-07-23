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
            $table->string('receive_no')->unique();
            $table->date('receive_date');
            $table->foreignId('branch_id')->constrained('ac_branches')->restrictOnDelete();
            $table->foreignId('account_id')->constrained('ac_accounts')->restrictOnDelete();
            $table->foreignId('particular_id')->constrained('ac_particulars')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('attachment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['receive_date', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ac_balance_receives');
    }
};
