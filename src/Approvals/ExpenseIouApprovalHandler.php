<?php

namespace ME\AccSfl\Approvals;

use App\Approvals\BaseApprovalHandler;
use App\Models\Approval;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use ME\AccSfl\Models\AcExpenseIou;

/**
 * Module 'accounts.expense_iou' (registered by AccSflServiceProvider::registerApprovalModules()).
 *
 * An IOU posts no ledger transaction until approved here: approval sets approval_status,
 * and AcExpenseIouObserver::updated() then posts the Issue (cash leaves the account).
 * Mail goes to every user with ac_expense_iou.approve, like expenses / balance receives.
 */
class ExpenseIouApprovalHandler extends BaseApprovalHandler
{
    public function recipients(?Model $approvable, Approval $approval): array
    {
        return User::all()
            ->filter(fn (User $user) => $user->hasPermission('ac_expense_iou.approve'))
            ->pluck('email')
            ->filter()
            ->values()
            ->all();
    }

    public function onApproved(Approval $approval): void
    {
        $this->decide($approval, AcExpenseIou::APPROVAL_APPROVED);
    }

    public function onRejected(Approval $approval): void
    {
        $this->decide($approval, AcExpenseIou::APPROVAL_REJECTED);
    }

    private function decide(Approval $approval, string $status): void
    {
        $iou = $approval->approvable;

        if (! $iou instanceof AcExpenseIou || $iou->approval_status !== AcExpenseIou::APPROVAL_PENDING) {
            return;
        }

        $iou->update([
            'approval_status' => $status,
            'approved_by' => $approval->approved_by,
            'approved_at' => $approval->approved_at,
            'approval_remarks' => $approval->remarks,
        ]);
    }

    /** IOU shown in the shared approval email (erp-suhana emails/approval-request). */
    public function mailContent(?Model $approvable, Approval $approval): array
    {
        if (! $approvable instanceof AcExpenseIou) {
            return [];
        }

        $approvable->loadMissing(['branch', 'account', 'paymentMethod', 'employee', 'attachments']);

        return [
            'badge' => 'EXPENSE IOU',
            'number' => $approvable->iou_no,
            'date' => $approvable->issue_date?->format('d.m.Y'),
            'meta' => [
                'Employee' => $approvable->employee?->name,
                'Receiver' => trim(($approvable->receiver_name ?? '').($approvable->receiver_mobile ? " ({$approvable->receiver_mobile})" : '')) ?: null,
                'Branch' => $approvable->branch?->name,
                'Account' => $approvable->account?->name,
                'Payment Method' => $approvable->paymentMethod?->name,
                'Requested by' => $approval->requestedBy?->name,
                'Attachments' => ($count = $approvable->attachments->count()) ? "{$count} (view in system)" : null,
            ],
            'total' => ['label' => 'IOU Amount (advance)', 'value' => (float) $approvable->amount, 'money' => true],
            'notes' => ['Purpose' => strip_tags((string) $approvable->description)],
        ];
    }
}
