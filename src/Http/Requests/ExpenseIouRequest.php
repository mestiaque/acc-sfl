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
        // Same rationale as BalanceReceiveRequest: the issue amount already posted an
        // ac_transactions entry, so ledger-affecting fields are locked on edit. Status
        // transitions (Pending -> Adjusted) go through the dedicated adjust() action instead.
        if ($this->route('expense_iou')) {
            return [
                'description' => ['nullable', 'string'],
                'receiver_name' => ['nullable', 'string', 'max:255'],
                'receiver_mobile' => ['nullable', 'string', 'max:30'],
            ];
        }

        return [
            'account_id' => ['required', 'integer', 'exists:ac_accounts,id'],
            'employee_id' => ['nullable', 'integer', 'exists:hr_employees,id'],
            'payment_method_id' => ['required', 'integer', 'exists:ac_payment_methods,id'],
            'branch_id' => ['required', 'integer', 'exists:ac_branches,id'],
            'issue_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string'],
            'receiver_name' => ['nullable', 'string', 'max:255'],
            'receiver_mobile' => ['nullable', 'string', 'max:30'],
        ];
    }
}
