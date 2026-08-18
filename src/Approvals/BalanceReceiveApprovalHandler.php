<?php

namespace ME\AccSfl\Approvals;

use App\Approvals\BaseApprovalHandler;
use App\Models\Approval;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use ME\AccSfl\Models\AcBalanceReceive;
use ME\AccSfl\Services\TransactionService;

/**
 * Registered in erp-suhana's config/approval.php under 'accounts.balance_receive'.
 *
 * Balance receives post no ledger transaction and affect no account balance until
 * approved here — see AcBalanceReceiveObserver (no longer auto-posts on create)
 * and TransactionService::postBalanceReceive(), which onApproved() now calls.
 * Mirrors ExpenseApprovalHandler exactly.
 */
class BalanceReceiveApprovalHandler extends BaseApprovalHandler
{
    public function __construct(private readonly TransactionService $transactions)
    {
    }

    public function recipients(?Model $approvable, Approval $approval): array
    {
        return User::all()
            ->filter(fn (User $user) => $user->hasPermission('ac_balance_receive.approve'))
            ->pluck('email')
            ->filter()
            ->values()
            ->all();
    }

    public function onApproved(Approval $approval): void
    {
        $receive = $approval->approvable;

        if (!$receive instanceof AcBalanceReceive || $receive->status !== AcBalanceReceive::STATUS_PENDING) {
            return;
        }

        $this->transactions->postBalanceReceive($receive);

        $receive->update([
            'status' => AcBalanceReceive::STATUS_APPROVED,
            'approved_by' => $approval->approved_by,
            'approved_at' => $approval->approved_at,
            'approval_remarks' => $approval->remarks,
        ]);
    }

    public function onRejected(Approval $approval): void
    {
        $receive = $approval->approvable;

        if (!$receive instanceof AcBalanceReceive || $receive->status !== AcBalanceReceive::STATUS_PENDING) {
            return;
        }

        $receive->update([
            'status' => AcBalanceReceive::STATUS_REJECTED,
            'approved_by' => $approval->approved_by,
            'approved_at' => $approval->approved_at,
            'approval_remarks' => $approval->remarks,
        ]);
    }
}
