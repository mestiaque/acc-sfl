<?php

namespace ME\AccSfl\Observers;

use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Services\TransactionService;

class AcAccountObserver
{
    public function __construct(private readonly TransactionService $transactions)
    {
    }

    public function creating(AcAccount $account): void
    {
        if (is_null($account->current_balance)) {
            $account->current_balance = $account->opening_balance ?? 0;
        }
    }

    public function created(AcAccount $account): void
    {
        $this->transactions->postOpeningBalance($account);
    }
}
