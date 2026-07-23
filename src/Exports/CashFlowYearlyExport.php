<?php

namespace ME\AccSfl\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CashFlowYearlyExport implements FromView
{
    public function __construct(private readonly array $report)
    {
    }

    public function view(): View
    {
        return view('acc-sfl::admin.reports.partials.cash-flow-yearly-table', ['report' => $this->report]);
    }
}
