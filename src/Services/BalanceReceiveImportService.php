<?php

namespace ME\AccSfl\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcBalanceReceive;
use ME\AccSfl\Models\AcBranch;
use ME\AccSfl\Models\AcParticular;

/**
 * Parses the "Balance Receive" sheet of the accounts-import-template.xlsx (or any file
 * with a matching sheet name/columns) and, for each row, resolves Branch/Account by exact
 * name and Particular by A/C code, then either just reports what it found (preview) or
 * actually creates the record (import). Rows are processed and saved independently of each
 * other - one bad row never blocks the others, and every row carries its own error list so
 * problems can be pointed at the exact line that caused them.
 *
 * Deliberately calls AcBalanceReceive::create() (not a bespoke import codepath) so voucher
 * numbering and transaction posting - both wired up via AcBalanceReceiveObserver - stay
 * identical to a manually-entered Balance Receive.
 */
class BalanceReceiveImportService
{
    private const SHEET_NAME = 'Balance Receive';

    private const REQUIRED_HEADINGS = ['Date', 'Branch', 'Account', 'Particular Code', 'Amount'];

    private const HEADINGS = ['Date', 'Branch', 'Account', 'Particular Code', 'Particular Name', 'Amount', 'Description'];

    public function preview(UploadedFile $file): array
    {
        return $this->process($file, save: false);
    }

    public function import(UploadedFile $file): array
    {
        return $this->process($file, save: true);
    }

    private function process(UploadedFile $file, bool $save): array
    {
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (\Throwable $e) {
            return ['error' => 'Could not read this file. Please make sure it is a valid .xlsx/.xls file.', 'rows' => []];
        }

        $sheet = $this->findSheet($spreadsheet, self::SHEET_NAME);
        $grid = $sheet->toArray(null, true, true, false);

        if (empty($grid)) {
            return ['error' => 'The sheet appears to be empty.', 'rows' => []];
        }

        $header = array_map(fn ($h) => trim((string) $h), array_shift($grid));
        $columnIndex = [];
        foreach (self::HEADINGS as $heading) {
            $idx = array_search($heading, $header, true);
            $columnIndex[$heading] = $idx === false ? null : $idx;
        }

        $missing = array_filter(self::REQUIRED_HEADINGS, fn ($heading) => $columnIndex[$heading] === null);
        if (! empty($missing)) {
            return ['error' => 'Missing required column(s): '.implode(', ', $missing).'. Please use the provided template.', 'rows' => []];
        }

        $results = [];
        $rowNumber = 1;

        foreach ($grid as $row) {
            $rowNumber++;

            $cell = fn (string $heading) => $columnIndex[$heading] !== null ? trim((string) ($row[$columnIndex[$heading]] ?? '')) : '';

            $dateRaw = $cell('Date');
            $branchName = $cell('Branch');
            $accountName = $cell('Account');
            $particularCode = $cell('Particular Code');
            $particularName = $cell('Particular Name');
            $amountRaw = $cell('Amount');
            $description = $cell('Description');

            if ($dateRaw === '' && $branchName === '' && $accountName === '' && $particularCode === '' && $amountRaw === '') {
                continue;
            }

            $errors = [];

            $date = $this->parseDate($dateRaw);
            if ($dateRaw === '') {
                $errors[] = 'Date is required.';
            } elseif (! $date) {
                $errors[] = "Invalid date '{$dateRaw}'.";
            }

            $branch = $branchName !== '' ? AcBranch::query()->whereRaw('LOWER(name) = ?', [strtolower($branchName)])->first() : null;
            if ($branchName === '') {
                $errors[] = 'Branch is required.';
            } elseif (! $branch) {
                $errors[] = "Branch '{$branchName}' not found.";
            }

            $account = $accountName !== '' ? AcAccount::query()->whereRaw('LOWER(name) = ?', [strtolower($accountName)])->first() : null;
            if ($accountName === '') {
                $errors[] = 'Account is required.';
            } elseif (! $account) {
                $errors[] = "Account '{$accountName}' not found.";
            }

            $particular = $particularCode !== '' ? AcParticular::query()->where('code', $particularCode)->first() : null;
            if ($particularCode === '') {
                $errors[] = 'Particular Code is required.';
            } elseif (! $particular) {
                $errors[] = "Particular code '{$particularCode}' not found.";
            }

            $amount = null;
            if ($amountRaw === '') {
                $errors[] = 'Amount is required.';
            } elseif (! is_numeric($amountRaw)) {
                $errors[] = "Amount '{$amountRaw}' is not a valid number.";
            } else {
                $amount = (float) $amountRaw;
                if ($amount <= 0) {
                    $errors[] = 'Amount must be greater than 0.';
                }
            }

            $result = [
                'row' => $rowNumber,
                'date' => $dateRaw,
                'branch' => $branchName,
                'account' => $accountName,
                'particular_code' => $particularCode,
                'particular_name' => $particularName,
                'amount' => $amountRaw,
                'description' => $description,
                'errors' => $errors,
                'saved' => false,
            ];

            if (empty($errors) && $save) {
                try {
                    AcBalanceReceive::create([
                        'receive_date' => $date->toDateString(),
                        'branch_id' => $branch->id,
                        'account_id' => $account->id,
                        'particular_id' => $particular->id,
                        'amount' => $amount,
                        'description' => $description !== '' ? $description : null,
                        'created_by' => Auth::id(),
                    ]);
                    $result['saved'] = true;
                } catch (\Throwable $e) {
                    $result['errors'][] = 'Save failed: '.$e->getMessage();
                }
            }

            $results[] = $result;
        }

        return ['error' => null, 'rows' => $results];
    }

    private function findSheet(Spreadsheet $spreadsheet, string $preferredName): Worksheet
    {
        foreach ($spreadsheet->getSheetNames() as $name) {
            if (strcasecmp($name, $preferredName) === 0) {
                return $spreadsheet->getSheetByName($name);
            }
        }

        return $spreadsheet->getSheet(0);
    }

    private function parseDate(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
