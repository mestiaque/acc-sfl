<?php

namespace ME\AccSfl\Services;

use ME\AccSfl\Models\AcExpense;

/**
 * Mirrors approved Salary Advance expense lines (particulars flagged is_salary_advance) into
 * the employee's HR Earnings & Deductions as an Advance/IOU entry
 * (ME\Hr\Models\HrEmployeeOtherTransaction). HR is an optional integration for this module,
 * so everything here is a no-op when the HR package isn't installed.
 */
class SalaryAdvanceService
{
    private const HR_MODEL = \ME\Hr\Models\HrEmployeeOtherTransaction::class;

    public function post(AcExpense $expense): void
    {
        if (! class_exists(self::HR_MODEL) || ! $expense->employee_id || $expense->hr_other_transaction_id) {
            return;
        }

        $amount = $expense->salaryAdvanceAmount();

        if ($amount <= 0) {
            return;
        }

        $txn = (self::HR_MODEL)::create([
            'employee_id' => $expense->employee_id,
            'txn_date' => $expense->expense_date,
            'advance_iou' => $amount,
            'remarks' => "Salary Advance - {$expense->expense_no}",
            'status' => 1,
            'created_by' => $expense->approved_by ?? $expense->created_by,
        ]);

        $expense->forceFill(['hr_other_transaction_id' => $txn->id])->save();
    }

    public function reverse(AcExpense $expense): void
    {
        if (! class_exists(self::HR_MODEL) || ! $expense->hr_other_transaction_id) {
            return;
        }

        (self::HR_MODEL)::whereKey($expense->hr_other_transaction_id)->delete();

        $expense->forceFill(['hr_other_transaction_id' => null])->save();
    }
}
