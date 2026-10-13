<?php

namespace ME\AccSfl\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcBalanceReceive;
use ME\AccSfl\Models\AcExpense;
use ME\AccSfl\Models\AcExpenseIou;
use ME\AccSfl\Models\AcTransaction;

class TransactionService
{
    public function postOpeningBalance(AcAccount $account): ?AcTransaction
    {
        if ((float) $account->opening_balance === 0.0) {
            return null;
        }

        return AcTransaction::create([
            'transaction_date' => $account->created_at?->toDateString() ?? now()->toDateString(),
            'transaction_type' => AcTransaction::TYPE_OPENING_BALANCE,
            'reference_type' => $account->getMorphClass(),
            'reference_id' => $account->id,
            'branch_id' => $account->branch_id,
            'account_id' => $account->id,
            'payment_method_id' => null,
            'debit' => (float) $account->opening_balance,
            'credit' => 0,
            'balance' => (float) $account->opening_balance,
            'description' => 'Opening balance',
            'created_by' => Auth::id(),
        ]);
    }

    public function postBalanceReceive(AcBalanceReceive $receive): AcTransaction
    {
        return DB::transaction(fn () => $this->post(
            account: $receive->account,
            date: $receive->receive_date,
            type: AcTransaction::TYPE_BALANCE_RECEIVE,
            reference: $receive,
            debit: (float) $receive->amount,
            credit: 0,
            description: $receive->description ?: "Balance receive {$receive->receive_no}",
            paymentMethodId: null,
            branchId: $receive->branch_id,
        ));
    }

    /** $date overrides the posting date - an IOU's expenses post on the IOU's adjust date. */
    public function postExpense(AcExpense $expense, string|\DateTimeInterface|null $date = null): AcTransaction
    {
        return DB::transaction(fn () => $this->post(
            account: $expense->account,
            date: $date ?? $expense->expense_date,
            type: AcTransaction::TYPE_EXPENSE,
            reference: $expense,
            debit: 0,
            credit: (float) $expense->total_amount,
            description: $expense->description ?: "Expense {$expense->expense_no}",
            paymentMethodId: $expense->payment_method_id,
            branchId: $expense->branch_id,
        ));
    }

    public function postIouIssue(AcExpenseIou $iou): AcTransaction
    {
        return DB::transaction(fn () => $this->post(
            account: $iou->account,
            date: $iou->issue_date,
            type: AcTransaction::TYPE_IOU_ISSUE,
            reference: $iou,
            debit: 0,
            credit: (float) $iou->amount,
            description: $iou->description ?: "IOU issued {$iou->iou_no}",
            paymentMethodId: $iou->payment_method_id,
            branchId: $iou->branch_id,
        ));
    }

    /**
     * The IOU's amount was edited after issue (only allowed while still Pending). Rather
     * than mutating the original issue transaction — which would corrupt the audit trail —
     * this posts a delta-only correcting entry, the same principle postIouAdjustment()
     * already follows for the adjustment step.
     */
    public function correctIouAmount(AcExpenseIou $iou, float $oldAmount, float $newAmount): ?AcTransaction
    {
        $delta = round($newAmount - $oldAmount, 2);

        if ($delta === 0.0) {
            return null;
        }

        return DB::transaction(fn () => $this->post(
            account: $iou->account,
            date: $iou->issue_date,
            type: AcTransaction::TYPE_IOU_CORRECTION,
            reference: $iou,
            debit: $delta < 0 ? abs($delta) : 0,
            credit: $delta > 0 ? $delta : 0,
            description: "IOU {$iou->iou_no} amount corrected from ".number_format($oldAmount, 2).' to '.number_format($newAmount, 2),
            paymentMethodId: $iou->payment_method_id,
            branchId: $iou->branch_id,
        ));
    }

    /**
     * Settles the IOU as a pair of entries on the adjust date: a debit of the full IOU amount
     * (the advance is cleared back to the account) and a credit for each approved expense
     * bought with it (posted as a normal Expense, so reports still break it down by
     * particular). Those expenses posted nothing when approved, since the cash had already
     * left at issue. The net is settlement_amount (IOU amount minus expenses): positive = the
     * unspent cash came back, negative = the extra the purchase cost was paid out.
     */
    public function postIouAdjustment(AcExpenseIou $iou): AcTransaction
    {
        return DB::transaction(function () use ($iou) {
            $date = $iou->adjust_date ?? now()->toDateString();

            $cleared = $this->post(
                account: $iou->account,
                date: $date,
                type: AcTransaction::TYPE_IOU_ADJUSTMENT,
                reference: $iou,
                debit: (float) $iou->amount,
                credit: 0,
                description: "{$iou->iou_no} adjusted - ".number_format((float) $iou->amount, 2).' advance cleared',
                paymentMethodId: $iou->settlement_payment_method_id ?? $iou->payment_method_id,
                branchId: $iou->branch_id,
            );

            $iou->expenses()->where('status', AcExpense::STATUS_APPROVED)->orderBy('id')->get()
                ->each(fn (AcExpense $expense) => $this->postExpense($expense, $date));

            return $cleared;
        });
    }

    /**
     * Reverses the net balance effect of every given transaction for a single reference
     * (e.g. an IOU's Issue + any Corrections + its zero-value Adjustment, or an expense's
     * single Expense row) in one shot, then deletes those transaction rows outright -
     * AcTransaction has no SoftDeletes, and a reversed entry shouldn't linger as a ledger
     * row. Used by force-delete, and by normal delete when a not-yet-finalized record
     * (e.g. a still-Pending IOU, which posts its Issue transaction immediately on creation)
     * already has a posted transaction that needs undoing before the record itself is removed.
     */
    public function reverseTransactions(AcAccount $account, iterable $transactions): void
    {
        DB::transaction(function () use ($account, $transactions) {
            $netDebit = 0.0;
            $netCredit = 0.0;

            foreach ($transactions as $transaction) {
                $netDebit += (float) $transaction->debit;
                $netCredit += (float) $transaction->credit;
            }

            $lockedAccount = AcAccount::whereKey($account->id)->lockForUpdate()->first();

            $lockedAccount->update([
                'current_balance' => (float) $lockedAccount->current_balance - $netDebit + $netCredit,
            ]);

            foreach ($transactions as $transaction) {
                $transaction->delete();
            }
        });
    }

    private function post(
        AcAccount $account,
        string|\DateTimeInterface $date,
        string $type,
        Model $reference,
        float $debit,
        float $credit,
        ?string $description,
        ?int $paymentMethodId,
        ?int $branchId = null,
    ): AcTransaction {
        $lockedAccount = AcAccount::whereKey($account->id)->lockForUpdate()->first();

        $newBalance = (float) $lockedAccount->current_balance + $debit - $credit;

        $transaction = AcTransaction::create([
            'transaction_date' => $date,
            'transaction_type' => $type,
            'reference_type' => $reference->getMorphClass(),
            'reference_id' => $reference->id,
            'branch_id' => $branchId ?? $lockedAccount->branch_id,
            'account_id' => $lockedAccount->id,
            'payment_method_id' => $paymentMethodId,
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $newBalance,
            'description' => $description,
            'created_by' => Auth::id(),
        ]);

        $lockedAccount->update(['current_balance' => $newBalance]);

        return $transaction;
    }
}
