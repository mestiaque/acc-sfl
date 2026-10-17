<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Expense IOUs now need approval before the cash leaves the account (mirrors expenses /
 * balance receives). `status` stays Pending / Adjusted (settlement); `approval_status` is
 * the approval. IOUs issued before this already posted their Issue transaction, so they
 * are backfilled as approved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ac_expense_ious', function (Blueprint $table) {
            $table->string('approval_status')->default('pending')->after('status')->index();
            $table->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_remarks')->nullable()->after('approved_at');
        });

        DB::table('ac_expense_ious')->update(['approval_status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('ac_expense_ious', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['approval_status', 'approved_at', 'approval_remarks']);
        });
    }
};
