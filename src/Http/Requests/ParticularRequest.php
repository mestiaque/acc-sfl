<?php

namespace ME\AccSfl\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParticularRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('particular') ? 'ac_particular.edit' : 'ac_particular.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        $id = $this->route('particular')?->id;

        return [
            'master_particular_id' => ['required', 'integer', 'exists:ac_master_particulars,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('ac_particulars', 'code')->ignore($id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
