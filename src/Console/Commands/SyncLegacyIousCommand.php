<?php

namespace ME\AccSfl\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ME\AccSfl\Models\AcExpenseIou;
use ME\AccSfl\Models\AcTransaction;

/**
 * IOUs adjusted before IOU settlement existed (settlement_amount is NULL) only got a zero-value
 * "IOU Adjustment" entry: their cash stayed deducted with no expense recorded for it. This
 * reopens them (back to Pending, zero-value entry removed - no balance change) so the real
 * purchases can be recorded as expenses "Against IOU" and the IOU adjusted again under the
 * current rules (debit the advance, credit the expenses). The old adjust date is kept so the
 * re-adjust can reuse it.
 */
class SyncLegacyIousCommand extends Command
{
    protected $signature = 'acc-sfl:sync-legacy-ious
        {--iou=* : Only these IOU numbers (e.g. --iou=IOU-000001 --iou=IOU-000002)}
        {--apply : Reopen them. Without this it is a dry run that only lists what would change}';

    protected $description = 'Reopen IOUs adjusted under the old (zero-value) rule so they can be settled with expenses';

    public function handle(): int
    {
        $ious = AcExpenseIou::query()
            ->where('status', AcExpenseIou::STATUS_ADJUSTED)
            ->whereNull('settlement_amount')
            ->when($this->option('iou'), fn ($q, $numbers) => $q->whereIn('iou_no', $numbers))
            ->with(['employee', 'account'])
            ->orderBy('id')
            ->get();

        if ($ious->isEmpty()) {
            $this->info('No legacy adjusted IOUs found - nothing to sync.');

            return self::SUCCESS;
        }

        $rows = [];
        $skipped = 0;

        foreach ($ious as $iou) {
            $adjustments = $iou->transactions()->where('transaction_type', AcTransaction::TYPE_IOU_ADJUSTMENT)->get();
            $movesCash = $adjustments->contains(fn ($t) => (float) $t->debit !== 0.0 || (float) $t->credit !== 0.0);

            $action = match (true) {
                $movesCash => 'SKIP - its adjustment entry moved cash, check it by hand',
                $this->option('apply') => 'reopened',
                default => 'would reopen',
            };

            if ($movesCash) {
                $skipped++;
            } elseif ($this->option('apply')) {
                DB::transaction(function () use ($iou, $adjustments) {
                    // Zero-value rows: deleting them changes no balance.
                    $adjustments->each->delete();

                    $iou->update(['status' => AcExpenseIou::STATUS_PENDING]);
                });
            }

            $rows[] = [
                $iou->iou_no,
                $iou->employee->name ?? '-',
                $iou->account->name ?? '-',
                number_format((float) $iou->amount, 2),
                $iou->adjust_date?->format('d M Y') ?? '-',
                $action,
            ];
        }

        $this->table(['IOU', 'Employee', 'Account', 'Amount', 'Old Adjust Date', 'Action'], $rows);

        if (! $this->option('apply')) {
            $this->warn('Dry run - nothing changed. Re-run with --apply to reopen them.');

            return self::SUCCESS;
        }

        $this->info(($ious->count() - $skipped).' IOU(s) reopened as Pending.');
        $this->line('Next, for each one: record what was bought as an Expense with "Against IOU" set to it, approve it,');
        $this->line('then Adjust the IOU (the old adjust date is pre-filled). Adjusting with no expense counts the whole amount as returned.');

        return self::SUCCESS;
    }
}
