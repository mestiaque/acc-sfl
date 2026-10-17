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

    /** Nothing leaves the account until the IOU is approved (ExpenseIouApprovalHandler). */
    public function created(AcExpenseIou $iou): void
    {
        if ($iou->isApproved()) {
            $this->transactions->postIouIssue($iou);
        }
    }

    public function updated(AcExpenseIou $iou): void
    {
        if ($iou->wasChanged('approval_status') && $iou->isApproved() && ! $iou->transactions()->exists()) {
            $this->transactions->postIouIssue($iou);
        }

        if ($iou->wasChanged('status') && $iou->status === AcExpenseIou::STATUS_ADJUSTED) {
            $this->transactions->postIouAdjustment($iou);
        }
    }
}
