<?php

namespace ME\AccSfl\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class ExpenseExport implements FromView
{
    public function __construct(private readonly Collection $expenses)
    {
    }

    public function view(): View
    {
        return view('acc-sfl::admin.expenses.partials.table', ['expenses' => $this->expenses]);
    }
}
