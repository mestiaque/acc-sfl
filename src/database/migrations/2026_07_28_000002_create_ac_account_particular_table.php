<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional per-account allow-list of Particulars. When an account has one or more rows
 * here, a user tied to that account (ac_accounts.user_id) only sees those particulars in
 * every Particular select/filter across the module; an account with no rows here is
 * unrestricted (its user sees every particular, same as before this table existed).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ac_account_particular', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('ac_accounts')->cascadeOnDelete();
            $table->foreignId('particular_id')->constrained('ac_particulars')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['account_id', 'particular_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ac_account_particular');
    }
};
