<?php

namespace ME\AccSfl\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use ME\AccSfl\Models\AcExpense;
use ME\AccSfl\Models\AcExpenseIou;
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
                'billing_date' => ['nullable', 'date'],
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
            'billing_date' => ['nullable', 'date'],
            'payment_method_id' => ['required', 'integer', 'exists:ac_payment_methods,id'],
            'branch_id' => ['required', 'integer', 'exists:ac_branches,id'],
            'account_id' => ['required', 'integer', 'exists:ac_accounts,id'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'receiver_name' => ['nullable', 'string', 'max:255'],
            'receiver_mobile' => ['nullable', 'string', 'max:30'],
            'employee_id' => ['nullable', 'integer', 'exists:hr_employees,id'],
            'iou_id' => ['nullable', 'integer', Rule::exists('ac_expense_ious', 'id')
                ->where('status', AcExpenseIou::STATUS_PENDING)->whereNull('deleted_at')],
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

    /**
     * A Salary Advance line is posted to the employee's HR Earnings & Deductions on approval,
     * so it can't be saved without an employee. For an already-approved expense the line items
     * are locked, so the check runs against its stored details instead of the submitted items.
     */
    /** An expense bought with an IOU always belongs to the employee who took that IOU. */
    protected function prepareForValidation(): void
    {
        if ($this->filled('iou_id') && $iou = AcExpenseIou::find($this->input('iou_id'))) {
            $this->merge(['employee_id' => $iou->employee_id]);
        }
    }

    public function withValidator($validator): void
    {
        // The IOU's cash left a specific account, so the expense bought with it must sit on
        // that same account for the settlement on adjust to balance.
        $validator->after(function ($validator) {
            if (! $this->filled('iou_id') || ! $this->has('account_id')) {
                return;
            }

            $iou = AcExpenseIou::find($this->input('iou_id'));
            if ($iou && (int) $iou->account_id !== (int) $this->input('account_id')) {
                $validator->errors()->add('account_id', "Account must be {$iou->account?->name} - the account IOU {$iou->iou_no} was issued from.");
            }
        });

        $validator->after(function ($validator) {
            if ($this->filled('employee_id')) {
                return;
            }

            $expense = $this->route('expense');
            $particularIds = $expense && $expense->status !== AcExpense::STATUS_PENDING
                ? $expense->details()->pluck('particular_id')->all()
                : collect($this->input('items', []))->pluck('particular_id')->filter()->all();

            if ($particularIds !== [] && AcParticular::whereIn('id', $particularIds)->where('is_salary_advance', true)->exists()) {
                $validator->errors()->add('employee_id', 'Employee is required for a Salary Advance expense.');
            }
        });
    }
}
