<?php

namespace ME\AccSfl\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use ME\AccSfl\Models\AcBalanceReceive;
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
        $balanceReceive = $this->route('balance_receive');

        // Same rationale as ExpenseRequest: once a receive has posted its ac_transactions
        // entry (i.e. it's no longer pending), the fields that drive the ledger
        // (date/branch/account/particular/amount) are locked — changing them would desync
        // the already-posted transaction and every running balance after it. A still-pending
        // receive has posted nothing yet, so it gets the full rule set below, same as create.
        if ($balanceReceive && $balanceReceive->status !== AcBalanceReceive::STATUS_PENDING) {
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
