<?php

namespace ME\AccSfl\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class ExpenseIouExport implements FromView
{
    public function __construct(private readonly Collection $expenseIous)
    {
    }

    public function view(): View
    {
        return view('acc-sfl::admin.expense-ious.partials.table', ['expenseIous' => $this->expenseIous]);
    }
}
