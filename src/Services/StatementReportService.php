<?php

namespace ME\AccSfl\Services;

use Illuminate\Support\Carbon;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcTransaction;

/**
 * Builds a bank-style running-balance statement for a single account. Reuses the balance
 * already stored per-transaction on ac_transactions (a per-account running total maintained
 * transactionally when transactions are posted - see AcAccount::current_balance), so the
 * opening balance for any date range is simply the balance of the last transaction dated
 * before the range starts, and the closing balance is the balance of the last transaction
 * within it.
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
            ->with(['paymentMethod', 'creator'])
            ->where('account_id', $accountId)
            ->whereDate('transaction_date', '>=', $fromDate->toDateString())
            ->whereDate('transaction_date', '<=', $toDate->toDateString())
            ->when($transactionType, fn ($q) => $q->where('transaction_type', $transactionType))
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $totalDebit = (float) $transactions->sum('debit');
        $totalCredit = (float) $transactions->sum('credit');
        $closingBalance = $transactions->isNotEmpty() ? (float) $transactions->last()->balance : $openingBalance;

        return [
            'account' => $account,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'opening_balance' => $openingBalance,
            'transactions' => $transactions,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'closing_balance' => $closingBalance,
        ];
    }
}
