<?php

namespace ME\AccSfl\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('account') ? 'ac_account.edit' : 'ac_account.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        $isUpdate = (bool) $this->route('account');

        return [
            'name' => ['required', 'string', 'max:255'],
            'employee_id' => ['nullable', 'string', 'max:100'],
            'designation' => ['nullable', 'string', 'max:150'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'branch_id' => ['required', 'integer', 'exists:ac_branches,id'],
            // Opening balance seeds the ledger via the Opening Balance transaction at creation
            // time — changing it afterwards would desync the ledger, so it's locked on update.
            'opening_balance' => $isUpdate ? ['prohibited'] : ['nullable', 'numeric'],
            'is_active' => ['sometimes', 'boolean'],
            'particular_ids' => ['sometimes', 'array'],
            'particular_ids.*' => ['integer', 'exists:ac_particulars,id'],
        ];
    }
}
