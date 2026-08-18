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

    public function postExpense(AcExpense $expense): AcTransaction
    {
        return DB::transaction(fn () => $this->post(
            account: $expense->account,
            date: $expense->expense_date,
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
     * The schema carries a single `amount` on the IOU (the advance given at issue time),
     * with no separate "amount returned" field. Adjustment therefore only closes the IOU's
     * status and logs an audit-trail entry — it does not move cash a second time, since the
     * cash already left the account at issue and settlement is tracked via linked Expense records.
     */
    public function postIouAdjustment(AcExpenseIou $iou): AcTransaction
    {
        return DB::transaction(fn () => $this->post(
            account: $iou->account,
            date: $iou->adjust_date ?? now()->toDateString(),
            type: AcTransaction::TYPE_IOU_ADJUSTMENT,
            reference: $iou,
            debit: 0,
            credit: 0,
            description: "IOU {$iou->iou_no} adjusted",
            paymentMethodId: $iou->payment_method_id,
            branchId: $iou->branch_id,
        ));
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
