<?php

namespace ME\AccSfl\Approvals;

use App\Approvals\BaseApprovalHandler;
use App\Models\Approval;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use ME\AccSfl\Models\AcExpense;
use ME\AccSfl\Services\TransactionService;

/**
 * Registered in erp-suhana's config/approval.php under 'accounts.expense'.
 *
 * Expenses post no ledger transaction and affect no account balance until
 * approved here — see AcExpenseObserver (no longer auto-posts on create)
 * and TransactionService::postExpense(), which onApproved() now calls.
 */
class ExpenseApprovalHandler extends BaseApprovalHandler
{
    public function __construct(private readonly TransactionService $transactions)
    {
    }

    public function recipients(?Model $approvable, Approval $approval): array
    {
        return User::all()
            ->filter(fn (User $user) => $user->hasPermission('ac_expense.approve'))
            ->pluck('email')
            ->filter()
            ->values()
            ->all();
    }

    public function onApproved(Approval $approval): void
    {
        $expense = $approval->approvable;

        if (!$expense instanceof AcExpense || $expense->status !== AcExpense::STATUS_PENDING) {
            return;
        }

        $this->transactions->postExpense($expense);

        $expense->update([
            'status' => AcExpense::STATUS_APPROVED,
            'approved_by' => $approval->approved_by,
            'approved_at' => $approval->approved_at,
            'approval_remarks' => $approval->remarks,
        ]);
    }

    public function onRejected(Approval $approval): void
    {
        $expense = $approval->approvable;

        if (!$expense instanceof AcExpense || $expense->status !== AcExpense::STATUS_PENDING) {
            return;
        }

        $expense->update([
            'status' => AcExpense::STATUS_REJECTED,
            'approved_by' => $approval->approved_by,
            'approved_at' => $approval->approved_at,
            'approval_remarks' => $approval->remarks,
        ]);
    }
}
