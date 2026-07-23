<?php

namespace ME\AccSfl\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class MasterParticularExport implements FromView
{
    public function __construct(private readonly Collection $masterParticulars)
    {
    }

    public function view(): View
    {
        return view('acc-sfl::admin.master-particulars.partials.table', ['masterParticulars' => $this->masterParticulars]);
    }
}
