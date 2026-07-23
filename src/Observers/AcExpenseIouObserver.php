<?php

namespace ME\AccSfl\Observers;

use ME\AccSfl\Models\AcExpenseIou;
use ME\AccSfl\Services\TransactionService;
use ME\AccSfl\Services\VoucherNumberService;

class AcExpenseIouObserver
{
    public function __construct(
        private readonly VoucherNumberService $vouchers,
        private readonly TransactionService $transactions,
    ) {
    }

    public function creating(AcExpenseIou $iou): void
    {
        if (empty($iou->iou_no)) {
            $iou->iou_no = $this->vouchers->next(
                AcExpenseIou::class,
                'iou_no',
                config('acc-sfl.voucher_prefixes.iou', 'IOU'),
            );
        }
    }

    public function created(AcExpenseIou $iou): void
    {
        $this->transactions->postIouIssue($iou);
    }

    public function updated(AcExpenseIou $iou): void
    {
        if ($iou->wasChanged('status') && $iou->status === AcExpenseIou::STATUS_ADJUSTED) {
            $this->transactions->postIouAdjustment($iou);
        }
    }
}
