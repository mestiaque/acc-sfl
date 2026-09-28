<?php

namespace ME\AccSfl\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use ME\AccSfl\Models\AcExpense;

class ExpenseApprovalMailService
{
    public function send(AcExpense $expense): void
    {
        // $recipients = User::all()
        //     ->filter(fn (User $user) => $user->hasPermission('ac_expense.approve'))
        //     ->pluck('email')
        //     ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
        //     ->unique()
        //     ->values()
        //     ->all();

        $recipients = ['mrm.khan.1298@gmail.com'];

        if ($recipients === []) {
            Log::warning('Expense approval email was skipped because no user with ac_expense.approve permission has a valid email.', [
                'expense_id' => $expense->getKey(),
            ]);

            return;
        }

        $expense->loadMissing(['branch', 'account', 'paymentMethod', 'creator', 'employee', 'details.particular.masterParticular', 'attachments']);

        try {
            Mail::send('acc-sfl::emails.expense-approval', [
                'expense' => $expense,
                'amountInWords' => app(NumberToWordsService::class)->taka((float) $expense->total_amount),
                'approvalUrl' => route('acc-sfl.expenses.index', ['search' => $expense->expense_no]),
            ], function ($message) use ($recipients, $expense): void {
                $message->to($recipients)
                    ->subject("Expense Memo {$expense->expense_no} - Tk ".number_format((float) $expense->total_amount, 2).' - Approval Required');
            });
        } catch (\Throwable $exception) {
            Log::error('Expense approval email could not be sent.', [
                'expense_id' => $expense->getKey(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
