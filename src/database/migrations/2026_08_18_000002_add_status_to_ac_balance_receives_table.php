<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors 2026_08_16_000001_add_status_to_ac_expenses_table.php for Balance Receive.
 *
 * Every existing balance receive was created under the old auto-post flow
 * (AcBalanceReceiveObserver posted its transaction unconditionally, with no approval
 * step), so every row already has a posted transaction by the time this migration runs.
 * Unlike the Expense migration - whose default 'pending' silently mislabeled already-posted
 * historical rows as awaiting approval, since nothing backfilled them - this migration
 * immediately marks all pre-existing rows 'approved' in the same step the column is added,
 * so there's never a moment where old data shows as pending. Only receives created after
 * this migration (via the new approval-gated flow) default to 'pending'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ac_balance_receives', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('amount');
            $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_remarks')->nullable()->after('approved_at');

            $table->index('status');
        });

        // Only backfill rows that already have a posted transaction - a blanket update here
        // would wrongly mark a genuinely new (correctly pending, transaction-less) receive as
        // 'approved' without ever posting its transaction, if one was created in the window
        // between this migration's code shipping and `php artisan migrate` actually running.
        DB::table('ac_balance_receives')
            ->where('status', 'pending')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('ac_transactions')
                    ->whereColumn('ac_transactions.reference_id', 'ac_balance_receives.id')
                    ->where('ac_transactions.reference_type', 'ac_balance_receive');
            })
            ->update([
                'status' => 'approved',
                'approved_at' => DB::raw('created_at'),
                'approval_remarks' => 'Backfilled: this balance receive predates the approval workflow and was already posted to the ledger at creation.',
            ]);
    }

    public function down(): void
    {
        Schema::table('ac_balance_receives', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['status', 'approved_at', 'approval_remarks']);
        });
    }
};
