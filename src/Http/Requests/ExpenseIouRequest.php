<?php

namespace ME\AccSfl\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseIouRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('expense_iou') ? 'ac_expense_iou.edit' : 'ac_expense_iou.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        // Branch/account/payment method stay locked forever after issue — they pick the
        // ledger the transaction posted to, and account is what the tied-account visibility
        // scope is keyed on. Issue date, amount, employee and description remain editable
        // while the IOU is still Pending; the amount's ac_transactions entry is corrected
        // by ExpenseIouController::update() (see TransactionService::correctIouAmount()).
        // Once Adjusted, only the description stays editable — everything else, including
        // amount, is locked because adjust() has already closed the ledger entry for this IOU.
        $iou = $this->route('expense_iou');

        if ($iou && $iou->status === \ME\AccSfl\Models\AcExpenseIou::STATUS_PENDING) {
            return [
                'issue_date' => ['required', 'date'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'employee_id' => ['required', 'integer', 'exists:hr_employees,id'],
                'description' => ['nullable', 'string'],
                'attachments' => ['nullable', 'array'],
                'attachments.*' => ['file', 'max:5120'],
            ];
        }

        if ($iou) {
            return [
                'description' => ['nullable', 'string'],
                'attachments' => ['nullable', 'array'],
                'attachments.*' => ['file', 'max:5120'],
            ];
        }

        return [
            'account_id' => ['required', 'integer', 'exists:ac_accounts,id'],
            'employee_id' => ['required', 'integer', 'exists:hr_employees,id'],
            'payment_method_id' => ['required', 'integer', 'exists:ac_payment_methods,id'],
            'branch_id' => ['required', 'integer', 'exists:ac_branches,id'],
            'issue_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:5120'],
        ];
    }
}
