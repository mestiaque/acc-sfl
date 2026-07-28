<?php

namespace ME\AccSfl\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class ExpenseIouReportExport implements FromView
{
    public function __construct(private readonly Collection $expenseIous, private readonly array $totals)
    {
    }

    public function view(): View
    {
        return view('acc-sfl::admin.reports.partials.expense-iou-report-table', [
            'expenseIous' => $this->expenseIous,
            'totals' => $this->totals,
        ]);
    }
}
