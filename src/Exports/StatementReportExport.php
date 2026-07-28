<?php

namespace ME\AccSfl\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class StatementReportExport implements FromView
{
    public function __construct(private readonly array $statement)
    {
    }

    public function view(): View
    {
        return view('acc-sfl::admin.reports.partials.statement-report-table', ['statement' => $this->statement]);
    }
}
