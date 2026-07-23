<?php

namespace ME\AccSfl\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use ME\AccSfl\Models\AcMasterParticular;
use ME\AccSfl\Models\AcParticular;

class BalanceReceiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('balance_receive') ? 'ac_balance_receive.edit' : 'ac_balance_receive.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        // Once a receive has posted its ac_transactions entry, the fields that drive the
        // ledger (date/branch/account/particular/amount) are locked on edit — changing them
        // here would desync the already-posted transaction and every running balance after
        // it. Only descriptive metadata stays editable; corrections go through a new entry.
        if ($this->route('balance_receive')) {
            return [
                'description' => ['nullable', 'string'],
                'attachment' => ['nullable', 'file', 'max:5120'],
            ];
        }

        return [
            'receive_date' => ['required', 'date'],
            'branch_id' => ['required', 'integer', 'exists:ac_branches,id'],
            'account_id' => ['required', 'integer', 'exists:ac_accounts,id'],
            'particular_id' => [
                'required', 'integer', 'exists:ac_particulars,id',
                function ($attribute, $value, $fail) {
                    $particular = AcParticular::with('masterParticular')->find($value);
                    if ($particular && $particular->masterParticular?->type !== AcMasterParticular::TYPE_DEBIT) {
                        $fail('The selected particular is not a cash-receipt particular.');
                    }
                },
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ];
    }
}
