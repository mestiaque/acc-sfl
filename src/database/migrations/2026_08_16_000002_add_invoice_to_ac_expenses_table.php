<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ac_expenses', function (Blueprint $table) {
            $table->string('invoice')->nullable()->after('receiver_mobile');
        });
    }

    public function down(): void
    {
        Schema::table('ac_expenses', function (Blueprint $table) {
            $table->dropColumn('invoice');
        });
    }
};
