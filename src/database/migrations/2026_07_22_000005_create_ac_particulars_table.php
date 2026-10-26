<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ac_particulars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_particular_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_salary_advance')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique('code');

            $table->foreign('master_particular_id')->references('id')->on('ac_master_particulars')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ac_particulars');
    }
};
