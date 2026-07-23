<?php

namespace ME\AccSfl\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('branch') ? 'ac_branch.edit' : 'ac_branch.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        $branchId = $this->route('branch')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('ac_branches', 'code')->ignore($branchId)],
            'location' => ['nullable', 'string', 'max:255'],
            'branch_head' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
