<?php

namespace ME\AccSfl\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use ME\AccSfl\Models\AcExpense;
use ME\AccSfl\Models\AcMasterParticular;
use ME\AccSfl\Models\AcParticular;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('expense') ? 'ac_expense.edit' : 'ac_expense.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        $expense = $this->route('expense');

        // Same rationale as BalanceReceiveRequest: once a transaction has posted (i.e. the
        // expense is no longer pending), ledger-affecting fields (date/branch/account/payment
        // method/line items) are locked; only descriptive metadata stays editable. A still-pending
        // expense has posted nothing yet, so it gets the full rule set below, same as create.
        if ($expense && $expense->status !== AcExpense::STATUS_PENDING) {
            return [
                'company_name' => ['nullable', 'string', 'max:255'],
                'receiver_name' => ['nullable', 'string', 'max:255'],
                'receiver_mobile' => ['nullable', 'string', 'max:30'],
                'employee_id' => ['nullable', 'integer', 'exists:hr_employees,id'],
                'invoice' => ['nullable', 'string', 'max:100'],
                'description' => ['nullable', 'string'],
                'attachment' => ['nullable', 'file', 'max:5120'],
                'attachments' => ['nullable', 'array'],
                'attachments.*' => ['file', 'max:5120'],
            ];
        }

        return [
            'expense_date' => ['required', 'date'],
            'payment_method_id' => ['required', 'integer', 'exists:ac_payment_methods,id'],
            'branch_id' => ['required', 'integer', 'exists:ac_branches,id'],
            'account_id' => ['required', 'integer', 'exists:ac_accounts,id'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'receiver_name' => ['nullable', 'string', 'max:255'],
            'receiver_mobile' => ['nullable', 'string', 'max:30'],
            'employee_id' => ['nullable', 'integer', 'exists:hr_employees,id'],
            'invoice' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:5120'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:5120'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.particular_id' => [
                'required', 'integer', 'exists:ac_particulars,id',
                function ($attribute, $value, $fail) {
                    $particular = AcParticular::with('masterParticular')->find($value);
                    if ($particular && $particular->masterParticular?->type !== AcMasterParticular::TYPE_CREDIT) {
                        $fail('The selected particular is not an expense particular.');
                    }
                },
            ],
            'items.*.qty' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.uom' => ['nullable', 'string', 'max:50'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
