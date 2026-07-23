<?php

namespace ME\AccSfl\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
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
        // Same rationale as BalanceReceiveRequest: total_amount already drove a posted
        // ac_transactions entry, so the ledger-affecting fields (date/branch/account/payment
        // method/line items) are locked on edit; only descriptive metadata stays editable.
        if ($this->route('expense')) {
            return [
                'company_name' => ['nullable', 'string', 'max:255'],
                'receiver_name' => ['nullable', 'string', 'max:255'],
                'receiver_mobile' => ['nullable', 'string', 'max:30'],
                'description' => ['nullable', 'string'],
                'attachment' => ['nullable', 'file', 'max:5120'],
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
            'description' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:5120'],
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
            'items.*.invoice' => ['nullable', 'string', 'max:100'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.uom' => ['nullable', 'string', 'max:50'],
            'items.*.rate' => ['required', 'numeric', 'min:0'],
            'items.*.description' => ['nullable', 'string'],
        ];
    }
}
