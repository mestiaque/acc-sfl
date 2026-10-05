<?php

namespace ME\AccSfl\Approvals;

use App\Approvals\BaseApprovalHandler;
use App\Models\Approval;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use ME\AccSfl\Models\AcExpense;
use ME\AccSfl\Services\SalaryAdvanceService;
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
    public function __construct(
        private readonly TransactionService $transactions,
        private readonly SalaryAdvanceService $salaryAdvances,
    ) {
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

        // Bought with an IOU: the cash already left the account at IOU issue, and the
        // difference is settled when the IOU is adjusted - so nothing is posted here.
        if (! $expense->iou_id) {
            $this->transactions->postExpense($expense);
        }

        $expense->update([
            'status' => AcExpense::STATUS_APPROVED,
            'approved_by' => $approval->approved_by,
            'approved_at' => $approval->approved_at,
            'approval_remarks' => $approval->remarks,
        ]);

        $this->salaryAdvances->post($expense);
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

    /** Expense memo shown in the shared approval email (erp-suhana emails/approval-request). */
    public function mailContent(?Model $approvable, Approval $approval): array
    {
        if (!$approvable instanceof AcExpense) {
            return [];
        }

        $approvable->loadMissing(['branch', 'account', 'paymentMethod', 'creator', 'employee', 'details.particular.masterParticular', 'attachments']);

        $rows = $approvable->details->map(fn ($detail) => [
            ['text' => $detail->particular->name ?? '-', 'sub' => collect([
                $detail->particular?->masterParticular?->name,
                $detail->description,
                $detail->invoice ? "Invoice: {$detail->invoice}" : null,
            ])->filter()->implode(' · ')],
            number_format((float) $detail->qty, 2),
            $detail->uom ?: '-',
            number_format((float) $detail->rate, 2),
            number_format((float) $detail->amount, 2),
        ])->all();

        if ($rows === []) {
            $rows = [[strip_tags((string) $approvable->description) ?: '-', '-', '-', '-', number_format((float) $approvable->total_amount, 2)]];
        }

        return [
            'badge' => 'EXPENSE MEMO',
            'number' => $approvable->expense_no,
            'date' => $approvable->expense_date?->format('d.m.Y'),
            'meta' => [
                'Billing Date' => $approvable->billing_date?->format('d.m.Y'),
                'Paid to (Company)' => $approvable->company_name,
                'Receiver' => trim(($approvable->receiver_name ?? '').($approvable->receiver_mobile ? " ({$approvable->receiver_mobile})" : '')),
                'Employee' => $approvable->employee?->name,
                'Branch' => $approvable->branch?->name,
                'Account' => $approvable->account?->name,
                'Payment Method' => $approvable->paymentMethod?->name,
                'Invoice' => $approvable->invoice,
                'Attachments' => ($count = $approvable->attachments->count() + ($approvable->attachment ? 1 : 0)) ? "{$count} (view in system)" : null,
            ],
            'columns' => [['label' => 'Particular'], ['label' => 'Qty', 'align' => 'right'], ['label' => 'UOM'], ['label' => 'Rate', 'align' => 'right'], ['label' => 'Amount (Tk)', 'align' => 'right']],
            'rows' => $rows,
            'total' => ['label' => 'Total', 'value' => (float) $approvable->total_amount, 'money' => true],
            'notes' => ['Description' => $approvable->details->isNotEmpty() ? strip_tags((string) $approvable->description) : null],
        ];
    }
}
