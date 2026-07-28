<?php

namespace ME\AccSfl\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BalanceReceiveTemplateSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function array(): array
    {
        return [
            ['2025-07-15', 'Head Office', 'Main Cash A/C', '1001', 'Cash Sales', 25000, 'Cash sale - walk-in customer'],
            ['2025-07-16', 'Rangpur Branch', 'Rangpur Cash A/C', '1004', 'Customer Account Collections', 15000, 'Collection from ABC Traders'],
        ];
    }

    public function headings(): array
    {
        return ['Date', 'Branch', 'Account', 'Particular Code', 'Particular Name', 'Amount', 'Description'];
    }

    public function title(): string
    {
        return 'Balance Receive';
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
