<?php

namespace ME\AccSfl\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class ExpenseReportExport implements FromView
{
    public function __construct(private readonly Collection $rows, private readonly array $totals)
    {
    }

    public function view(): View
    {
        return view('acc-sfl::admin.reports.partials.expense-report-table', [
            'rows' => $this->rows,
            'totals' => $this->totals,
        ]);
    }
}
