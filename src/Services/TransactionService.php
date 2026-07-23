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
