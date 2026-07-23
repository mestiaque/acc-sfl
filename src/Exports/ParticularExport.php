<?php

namespace ME\AccSfl\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class ParticularExport implements FromView
{
    public function __construct(private readonly Collection $particulars)
    {
    }

    public function view(): View
    {
        return view('acc-sfl::admin.particulars.partials.table', ['particulars' => $this->particulars]);
    }
}
