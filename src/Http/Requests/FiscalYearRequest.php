<?php

namespace ME\AccSfl\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use ME\AccSfl\Models\AcFiscalYear;

class FiscalYearRequest extends FormRequest
{
    private const MONTHS = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun',
        7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
    ];

    public function authorize(): bool
    {
        $ability = $this->route('fiscal_year') ? 'ac_fiscal_year.edit' : 'ac_fiscal_year.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        return [
            'start_month' => ['required', 'integer', 'between:1,12'],
            'start_year' => ['required', 'integer', 'digits:4'],
            'end_month' => ['required', 'integer', 'between:1,12'],
            'end_year' => ['required', 'integer', 'digits:4'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->hasAny(['start_month', 'start_year', 'end_month', 'end_year'])) {
                return;
            }

            $startMonth = (int) $this->input('start_month');
            $startYear = (int) $this->input('start_year');
            $endMonth = (int) $this->input('end_month');
            $endYear = (int) $this->input('end_year');

            $totalMonths = ($endYear - $startYear) * 12 + ($endMonth - $startMonth) + 1;

            if ($totalMonths !== 12) {
                $validator->errors()->add('end_month', "The selected period spans {$totalMonths} month(s); a fiscal year must span exactly 12 months.");

                return;
            }

            $duplicate = AcFiscalYear::query()
                ->where('start_month', $startMonth)
                ->where('start_year', $startYear)
                ->when($this->route('fiscal_year'), fn ($q, $id) => $q->whereKeyNot($id))
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('start_month', 'A fiscal year starting on this month/year already exists.');
            }
        });
    }

    /**
     * Fills in the auto-generated label (e.g. "2026/27 (Jul 2026 - Jun 2027)") and any
     * fields the store()/update() call can pass straight to the model.
     */
    public function fiscalYearData(): array
    {
        $startMonth = (int) $this->input('start_month');
        $startYear = (int) $this->input('start_year');
        $endMonth = (int) $this->input('end_month');
        $endYear = (int) $this->input('end_year');

        $shortYearEnd = substr((string) $endYear, -2);
        $label = "{$startYear}/{$shortYearEnd} (".self::MONTHS[$startMonth]." {$startYear} - ".self::MONTHS[$endMonth]." {$endYear})";

        return [
            'label' => $label,
            'start_month' => $startMonth,
            'start_year' => $startYear,
            'end_month' => $endMonth,
            'end_year' => $endYear,
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
