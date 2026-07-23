<?php

namespace ME\AccSfl\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use ME\AccSfl\Models\AcMasterParticular;

class MasterParticularRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('master_particular') ? 'ac_master_particular.edit' : 'ac_master_particular.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        $id = $this->route('master_particular')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('ac_master_particulars', 'name')->ignore($id)],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in([AcMasterParticular::TYPE_DEBIT, AcMasterParticular::TYPE_CREDIT])],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
