<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ac_expense_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained('ac_expenses')->cascadeOnDelete();
            $table->foreignId('particular_id')->constrained('ac_particulars')->restrictOnDelete();
            $table->string('invoice')->nullable();
            $table->decimal('qty', 10, 2)->default(1);
            $table->string('uom')->nullable();
            $table->decimal('rate', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ac_expense_details');
    }
};
