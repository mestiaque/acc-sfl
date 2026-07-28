<?php

namespace ME\AccSfl\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpenseTemplateSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function array(): array
    {
        return [
            ['2025-07-15', 'Rangpur Branch', 'Rangpur Cash A/C', 'Cash', '3012', 'Salaries - Direct', 1, 18000, 18000, '', '', 'Md. Karim', '01712345678', '', 'July salary payment'],
            ['2025-07-16', 'Head Office', 'Main Cash A/C', 'Bank Transfer', '4001', 'Steel Materials', 50, 120, 6000, 'INV-2213', 'pcs', 'ABC Traders', '01898765432', 'ABC Traders Ltd.', 'Steel purchase for construction'],
        ];
    }

    public function headings(): array
    {
        return [
            'Date', 'Branch', 'Account', 'Payment Method', 'Particular Code', 'Particular Name',
            'Qty', 'Rate', 'Amount', 'Invoice', 'UOM', 'Receiver Name', 'Receiver Mobile', 'Company Name', 'Description',
        ];
    }

    public function title(): string
    {
        return 'Expense';
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
