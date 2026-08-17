<?php

namespace ME\AccSfl\Observers;

use ME\AccSfl\Models\AcExpense;
use ME\AccSfl\Services\VoucherNumberService;

class AcExpenseObserver
{
    public function __construct(
        private readonly VoucherNumberService $vouchers,
    ) {
    }

    public function creating(AcExpense $expense): void
    {
        if (empty($expense->expense_no)) {
            $expense->expense_no = $this->vouchers->next(
                AcExpense::class,
                'expense_no',
                config('acc-sfl.voucher_prefixes.expense', 'EXP'),
            );
        }
    }
}
