<?php

namespace ME\AccSfl\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ImportTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new ImportInstructionsSheet(),
            new BalanceReceiveTemplateSheet(),
            new ExpenseTemplateSheet(),
        ];
    }
}
