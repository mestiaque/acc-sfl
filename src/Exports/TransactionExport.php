<?php

namespace ME\AccSfl\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class TransactionExport implements FromView
{
    public function __construct(private readonly Collection $transactions)
    {
    }

    public function view(): View
    {
        return view('acc-sfl::admin.transactions.partials.table', ['transactions' => $this->transactions]);
    }
}
