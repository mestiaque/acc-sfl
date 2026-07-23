<?php

namespace ME\AccSfl\Observers;

use ME\AccSfl\Models\AcBalanceReceive;
use ME\AccSfl\Services\TransactionService;
use ME\AccSfl\Services\VoucherNumberService;

class AcBalanceReceiveObserver
{
    public function __construct(
        private readonly VoucherNumberService $vouchers,
        private readonly TransactionService $transactions,
    ) {
    }

    public function creating(AcBalanceReceive $receive): void
    {
        if (empty($receive->receive_no)) {
            $receive->receive_no = $this->vouchers->next(
                AcBalanceReceive::class,
                'receive_no',
                config('acc-sfl.voucher_prefixes.balance_receive', 'BR'),
            );
        }
    }

    public function created(AcBalanceReceive $receive): void
    {
        $this->transactions->postBalanceReceive($receive);
    }
}
