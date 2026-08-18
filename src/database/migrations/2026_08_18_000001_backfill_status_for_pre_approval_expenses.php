<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Before the approval workflow existed, AcExpenseObserver posted the ledger transaction
 * unconditionally at creation - there was no "pending" state at all. When the `status`
 * column was added (2026_08_16_000001), its DB default of 'pending' applied retroactively
 * to every pre-existing expense, mislabeling already-posted, already-settled expenses as
 * awaiting approval even though they have no matching Approval record (that system didn't
 * exist yet either) and nothing left to approve.
 *
 * This backfills exactly those rows - status still 'pending' AND a ledger transaction
 * already posted against them - to 'approved', so they stop showing (and failing) as
 * actionable in the approvals UI. Newly created expenses always go through
 * ExpenseController::store(), which raises a real Approval request before anything posts,
 * so they're never affected by this backfill.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('ac_expenses')
            ->where('status', 'pending')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('ac_transactions')
                    ->whereColumn('ac_transactions.reference_id', 'ac_expenses.id')
                    ->where('ac_transactions.reference_type', 'ac_expense');
            })
            ->update([
                'status' => 'approved',
                'approved_at' => DB::raw('created_at'),
                'approval_remarks' => 'Backfilled: this expense predates the approval workflow and was already posted to the ledger at creation.',
            ]);
    }

    public function down(): void
    {
        // Not reversible - backfilled rows are indistinguishable from genuinely
        // approved ones once the approval_remarks column is overwritten again.
    }
};
