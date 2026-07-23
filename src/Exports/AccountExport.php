<?php

namespace ME\AccSfl\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class AccountExport implements FromView
{
    public function __construct(private readonly Collection $accounts)
    {
    }

    public function view(): View
    {
        return view('acc-sfl::admin.accounts.partials.table', ['accounts' => $this->accounts]);
    }
}
