<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ac_fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->string('label')->unique();
            $table->unsignedTinyInteger('start_month');
            $table->unsignedSmallInteger('start_year');
            $table->unsignedTinyInteger('end_month');
            $table->unsignedSmallInteger('end_year');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ac_fiscal_years');
    }
};
