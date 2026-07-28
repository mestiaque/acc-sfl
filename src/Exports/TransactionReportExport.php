<?php

namespace ME\AccSfl\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class TransactionReportExport implements FromView
{
    public function __construct(private readonly Collection $transactions, private readonly array $totals)
    {
    }

    public function view(): View
    {
        return view('acc-sfl::admin.reports.partials.transaction-report-table', [
            'transactions' => $this->transactions,
            'totals' => $this->totals,
        ]);
    }
}
