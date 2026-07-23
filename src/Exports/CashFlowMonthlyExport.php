<?php

namespace ME\AccSfl\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CashFlowMonthlyExport implements FromView
{
    public function __construct(private readonly array $report)
    {
    }

    public function view(): View
    {
        return view('acc-sfl::admin.reports.partials.cash-flow-monthly-table', ['report' => $this->report]);
    }
}
