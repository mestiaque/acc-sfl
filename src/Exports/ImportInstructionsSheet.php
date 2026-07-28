<?php

namespace ME\AccSfl\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportInstructionsSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function array(): array
    {
        return [
            ['Accounts Management - Data Entry Template'],
            [''],
            ['This file has 2 data sheets: "Balance Receive" and "Expense". Each has 2 sample rows - replace them with your own data. One row = one record.'],
            [''],
            ['General rules'],
            ['- Date columns: use YYYY-MM-DD format (e.g. 2025-07-15).'],
            ['- Branch, Account, Payment Method: must exactly match a name already set up under Masters.'],
            ['- Particular Code: must exactly match an existing A/C code under Master Particulars > Particulars (not the name).'],
            ['- "Particular Name" columns are for your own reference only - they are not used for matching.'],
            [''],
            ['Balance Receive sheet'],
            ['- Required: Date, Branch, Account, Particular Code, Amount.'],
            ['- Optional: Particular Name, Description.'],
            [''],
            ['Expense sheet'],
            ['- Required: Date, Branch, Account, Payment Method, Particular Code, Amount.'],
            ['- Optional: Particular Name, Qty, Rate, Invoice, UOM, Receiver Name, Receiver Mobile, Company Name, Description.'],
            ['- The system stores Amount = Qty x Rate. For a simple lump-sum expense with no natural quantity, set Qty = 1 and Rate = Amount.'],
            ['- Only one Particular per expense row (matches the current Add Expense form).'],
        ];
    }

    public function title(): string
    {
        return 'Instructions';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            5 => ['font' => ['bold' => true]],
            11 => ['font' => ['bold' => true]],
            15 => ['font' => ['bold' => true]],
        ];
    }
}
