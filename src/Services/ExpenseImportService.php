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
use ME\AccSfl\Models\AcBranch;
use ME\AccSfl\Models\AcExpense;
use ME\AccSfl\Models\AcParticular;
use ME\AccSfl\Models\AcPaymentMethod;

/**
 * Parses the "Expense" sheet of the accounts-import-template.xlsx (or any file with a
 * matching sheet name/columns). Mirrors BalanceReceiveImportService's row-by-row,
 * self-contained-error design - see that class for the shared rationale.
 *
 * Qty/Rate/Amount reconciliation matches the manual Add Expense form's invariant
 * (total_amount = qty * rate): a row may supply Amount alone (Qty defaults to 1, Rate is
 * derived as Amount / Qty), or Qty + Rate alone (Amount is derived as Qty * Rate).
 */
class ExpenseImportService
{
    private const SHEET_NAME = 'Expense';

    private const REQUIRED_HEADINGS = ['Date', 'Branch', 'Account', 'Payment Method', 'Particular Code'];

    private const HEADINGS = [
        'Date', 'Branch', 'Account', 'Payment Method', 'Particular Code', 'Particular Name',
        'Qty', 'Rate', 'Amount', 'Invoice', 'UOM', 'Receiver Name', 'Receiver Mobile', 'Company Name', 'Description',
    ];

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

            $fields = [
                'date' => $cell('Date'),
                'branch' => $cell('Branch'),
                'account' => $cell('Account'),
                'payment_method' => $cell('Payment Method'),
                'particular_code' => $cell('Particular Code'),
                'particular_name' => $cell('Particular Name'),
                'qty' => $cell('Qty'),
                'rate' => $cell('Rate'),
                'amount' => $cell('Amount'),
                'invoice' => $cell('Invoice'),
                'uom' => $cell('UOM'),
                'receiver_name' => $cell('Receiver Name'),
                'receiver_mobile' => $cell('Receiver Mobile'),
                'company_name' => $cell('Company Name'),
                'description' => $cell('Description'),
            ];

            if ($fields['date'] === '' && $fields['branch'] === '' && $fields['account'] === '' && $fields['particular_code'] === '' && $fields['amount'] === '' && $fields['qty'] === '' && $fields['rate'] === '') {
                continue;
            }

            $results[] = ['row' => $rowNumber, ...$this->validateRow($fields, $save)];
        }

        return ['error' => null, 'rows' => $results];
    }

    /**
     * Validates a single row's fields (same shape used by process() and by the "recheck a
     * row after an inline edit" endpoint) and, when $save is true and the row is valid,
     * persists it. Returns the same row shape as process() minus the 'row' number, which
     * the caller already knows (spreadsheet row, or the value the client echoes back).
     */
    public function validateRow(array $fields, bool $save = false): array
    {
        $dateRaw = trim((string) ($fields['date'] ?? ''));
        $branchName = trim((string) ($fields['branch'] ?? ''));
        $accountName = trim((string) ($fields['account'] ?? ''));
        $paymentMethodName = trim((string) ($fields['payment_method'] ?? ''));
        $particularCode = trim((string) ($fields['particular_code'] ?? ''));
        $particularName = trim((string) ($fields['particular_name'] ?? ''));
        $qtyRaw = trim((string) ($fields['qty'] ?? ''));
        $rateRaw = trim((string) ($fields['rate'] ?? ''));
        $amountRaw = trim((string) ($fields['amount'] ?? ''));
        $invoice = trim((string) ($fields['invoice'] ?? ''));
        $uom = trim((string) ($fields['uom'] ?? ''));
        $receiverName = trim((string) ($fields['receiver_name'] ?? ''));
        $receiverMobile = trim((string) ($fields['receiver_mobile'] ?? ''));
        $companyName = trim((string) ($fields['company_name'] ?? ''));
        $description = trim((string) ($fields['description'] ?? ''));

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

        $paymentMethod = $paymentMethodName !== '' ? AcPaymentMethod::query()->whereRaw('LOWER(name) = ?', [strtolower($paymentMethodName)])->first() : null;
        if ($paymentMethodName === '') {
            $errors[] = 'Payment Method is required.';
        } elseif (! $paymentMethod) {
            $errors[] = "Payment Method '{$paymentMethodName}' not found.";
        }

        $particular = $particularCode !== '' ? AcParticular::query()->where('code', $particularCode)->first() : null;
        if ($particularCode === '') {
            $errors[] = 'Particular Code is required.';
        } elseif (! $particular) {
            $errors[] = "Particular code '{$particularCode}' not found.";
        }

        $hasAmount = $amountRaw !== '';
        $hasQtyRate = $qtyRaw !== '' && $rateRaw !== '';
        $qty = null;
        $rate = null;
        $amount = null;

        if (! $hasAmount && ! $hasQtyRate) {
            $errors[] = 'Provide either Amount, or both Qty and Rate.';
        } elseif ($qtyRaw !== '' && ! is_numeric($qtyRaw)) {
            $errors[] = "Qty '{$qtyRaw}' is not a valid number.";
        } elseif ($rateRaw !== '' && ! is_numeric($rateRaw)) {
            $errors[] = "Rate '{$rateRaw}' is not a valid number.";
        } elseif ($hasAmount && ! is_numeric($amountRaw)) {
            $errors[] = "Amount '{$amountRaw}' is not a valid number.";
        } else {
            $qty = $qtyRaw !== '' ? (float) $qtyRaw : 1.0;
            if ($hasAmount) {
                $amount = (float) $amountRaw;
                $rate = $rateRaw !== '' ? (float) $rateRaw : ($qty > 0 ? $amount / $qty : $amount);
            } else {
                $rate = (float) $rateRaw;
                $amount = round($qty * $rate, 2);
            }

            if ($qty <= 0) {
                $errors[] = 'Qty must be greater than 0.';
            }
            if ($amount <= 0) {
                $errors[] = 'Amount must be greater than 0.';
            }
        }

        $result = [
            'date' => $dateRaw,
            'branch' => $branchName,
            'account' => $accountName,
            'payment_method' => $paymentMethodName,
            'particular_code' => $particularCode,
            'particular_name' => $particularName,
            'qty' => $qtyRaw,
            'rate' => $rateRaw,
            'amount' => $amountRaw !== '' ? $amountRaw : ($amount !== null ? number_format($amount, 2) : ''),
            'invoice' => $invoice,
            'uom' => $uom,
            'receiver_name' => $receiverName,
            'receiver_mobile' => $receiverMobile,
            'company_name' => $companyName,
            'description' => $description,
            'errors' => $errors,
            'saved' => false,
        ];

        if (empty($errors) && $save) {
            try {
                $totalAmount = round($qty * $rate, 2);

                $expense = AcExpense::create([
                    'expense_date' => $date->toDateString(),
                    'payment_method_id' => $paymentMethod->id,
                    'branch_id' => $branch->id,
                    'account_id' => $account->id,
                    'company_name' => $companyName !== '' ? $companyName : null,
                    'receiver_name' => $receiverName !== '' ? $receiverName : null,
                    'receiver_mobile' => $receiverMobile !== '' ? $receiverMobile : null,
                    'total_amount' => $totalAmount,
                    'description' => $description !== '' ? $description : null,
                    'created_by' => Auth::id(),
                ]);

                $expense->details()->create([
                    'particular_id' => $particular->id,
                    'invoice' => $invoice !== '' ? $invoice : null,
                    'qty' => $qty,
                    'uom' => $uom !== '' ? $uom : null,
                    'rate' => $rate,
                    'amount' => $totalAmount,
                    'description' => $description !== '' ? $description : null,
                ]);

                $result['saved'] = true;
            } catch (\Throwable $e) {
                $result['errors'][] = 'Save failed: '.$e->getMessage();
            }
        }

        return $result;
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
