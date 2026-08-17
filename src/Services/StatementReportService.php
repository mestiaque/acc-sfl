<?php

namespace ME\AccSfl\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcBalanceReceive;
use ME\AccSfl\Models\AcExpense;
use ME\AccSfl\Models\AcExpenseIou;
use ME\AccSfl\Models\AcTransaction;

/**
 * Builds a bank-style running-balance statement for a single account, one row per
 * source line item (matching the company's existing "Cash Receipts & Payment Balance
 * Sheet" spreadsheet layout: Month/Date/Particular/Description/A-C Code/Invoice/
 * Receiver/Qty/UOM/Rate/Expense/Receive/Balance/Remarks).
 *
 * Reuses the balance already stored per-transaction on ac_transactions (a per-account
 * running total maintained transactionally when transactions are posted - see
 * AcAccount::current_balance), so the opening balance for any date range is simply the
 * balance of the last transaction dated before the range starts. A single ac_transactions
 * row is posted per Expense (its total_amount), not per line item, so an Expense
 * transaction is exploded here into one report row per AcExpenseDetail, with the running
 * balance split backwards across those lines so each row still shows a real
 * post-line balance instead of repeating the expense's total-only balance on every line.
 */
class StatementReportService
{
    public function build(int $accountId, Carbon $fromDate, Carbon $toDate, ?string $transactionType = null): array
    {
        $account = AcAccount::query()->with('branch')->findOrFail($accountId);

        $openingTransaction = AcTransaction::query()
            ->where('account_id', $accountId)
            ->whereDate('transaction_date', '<', $fromDate->toDateString())
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->first();

        $openingBalance = $openingTransaction ? (float) $openingTransaction->balance : 0.0;

        $transactions = AcTransaction::query()
            ->with([
                'reference' => fn ($morphTo) => $morphTo->morphWith([
                    AcExpense::class => ['details.particular'],
                    AcBalanceReceive::class => ['particular'],
                    AcExpenseIou::class => ['employee'],
                ]),
            ])
            ->where('account_id', $accountId)
            ->whereDate('transaction_date', '>=', $fromDate->toDateString())
            ->whereDate('transaction_date', '<=', $toDate->toDateString())
            ->when($transactionType, fn ($q) => $q->where('transaction_type', $transactionType))
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $rows = $this->buildRows($transactions);

        $totalExpense = (float) $rows->sum('expense');
        $totalReceive = (float) $rows->sum('receive');
        $closingBalance = $transactions->isNotEmpty() ? (float) $transactions->last()->balance : $openingBalance;

        return [
            'account' => $account,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'opening_balance' => $openingBalance,
            'rows' => $rows,
            'total_expense' => $totalExpense,
            'total_receive' => $totalReceive,
            'closing_balance' => $closingBalance,
        ];
    }

    private function buildRows(Collection $transactions): Collection
    {
        $rows = collect();

        foreach ($transactions as $transaction) {
            if ($transaction->transaction_type === AcTransaction::TYPE_EXPENSE
                && $transaction->reference instanceof AcExpense
                && $transaction->reference->details->isNotEmpty()) {
                foreach ($this->explodeExpense($transaction) as $row) {
                    $rows->push($row);
                }

                continue;
            }

            $rows->push($this->rowFor($transaction));
        }

        return $rows;
    }

    /**
     * One ac_transactions row is posted per Expense (its total_amount), so this splits
     * that single row's balance backwards across each line item in the same order they
     * were entered, giving each line a real running balance instead of all lines sharing
     * the expense total's post-transaction balance.
     */
    private function explodeExpense(AcTransaction $transaction): array
    {
        $expense = $transaction->reference;
        $running = (float) $transaction->balance + (float) $transaction->credit;

        return $expense->details->map(function ($detail) use ($transaction, $expense, &$running) {
            $running -= (float) $detail->amount;

            return [
                'date' => $transaction->transaction_date,
                'particular' => $detail->particular?->name,
                'description' => $detail->description,
                'ac_code' => $detail->particular?->code,
                'invoice' => $detail->invoice ?: $expense->invoice,
                'receiver' => $expense->receiver_name,
                'qty' => $detail->qty > 0 ? (float) $detail->qty : null,
                'uom' => $detail->uom,
                'rate' => $detail->rate > 0 ? (float) $detail->rate : null,
                'expense' => (float) $detail->amount,
                'receive' => null,
                'balance' => $running,
                'remarks' => null,
            ];
        })->all();
    }

    private function rowFor(AcTransaction $transaction): array
    {
        $reference = $transaction->reference;

        [$particular, $description, $acCode, $receiver] = match (true) {
            $transaction->transaction_type === AcTransaction::TYPE_BALANCE_RECEIVE && $reference instanceof AcBalanceReceive => [
                $reference->particular?->name,
                $reference->description,
                $reference->particular?->code,
                null,
            ],
            in_array($transaction->transaction_type, [AcTransaction::TYPE_IOU_ISSUE, AcTransaction::TYPE_IOU_ADJUSTMENT], true)
                && $reference instanceof AcExpenseIou => [
                    $transaction->transaction_type,
                    $reference->description,
                    null,
                    $reference->employee?->name,
                ],
            $transaction->transaction_type === AcTransaction::TYPE_OPENING_BALANCE => [
                'Opening Balance',
                $transaction->description,
                null,
                null,
            ],
            default => [$transaction->transaction_type, $transaction->description, null, null],
        };

        return [
            'date' => $transaction->transaction_date,
            'particular' => $particular,
            'description' => $description,
            'ac_code' => $acCode,
            'invoice' => null,
            'receiver' => $receiver,
            'qty' => null,
            'uom' => null,
            'rate' => null,
            'expense' => (float) $transaction->credit > 0 ? (float) $transaction->credit : null,
            'receive' => (float) $transaction->debit > 0 ? (float) $transaction->debit : null,
            'balance' => (float) $transaction->balance,
            'remarks' => null,
        ];
    }
}
