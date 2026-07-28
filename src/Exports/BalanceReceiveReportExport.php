<?php

namespace ME\AccSfl\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class BalanceReceiveReportExport implements FromView
{
    public function __construct(private readonly Collection $receives, private readonly array $totals)
    {
    }

    public function view(): View
    {
        return view('acc-sfl::admin.reports.partials.balance-receive-report-table', [
            'receives' => $this->receives,
            'totals' => $this->totals,
        ]);
    }
}
