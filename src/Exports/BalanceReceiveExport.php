<?php

namespace ME\AccSfl\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class BalanceReceiveExport implements FromView
{
    public function __construct(private readonly Collection $balanceReceives)
    {
    }

    public function view(): View
    {
        return view('acc-sfl::admin.balance-receives.partials.table', ['balanceReceives' => $this->balanceReceives]);
    }
}
