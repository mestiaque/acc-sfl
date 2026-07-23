<?php

namespace ME\AccSfl\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('payment_method') ? 'ac_payment_method.edit' : 'ac_payment_method.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        $id = $this->route('payment_method')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('ac_payment_methods', 'name')->ignore($id)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
