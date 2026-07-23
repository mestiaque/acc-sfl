<?php

namespace ME\AccSfl\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class BranchExport implements FromView
{
    public function __construct(private readonly Collection $branches)
    {
    }

    public function view(): View
    {
        return view('acc-sfl::admin.branches.partials.table', ['branches' => $this->branches]);
    }
}
