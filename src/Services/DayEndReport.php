<?php

namespace ME\AccSfl\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcBalanceReceive;
use ME\AccSfl\Models\AcExpense;
use ME\AccSfl\Models\AcExpenseIou;
use ME\AccSfl\Models\AcTransaction;

/**
 * Day end report: cash in hand per account (opening → in / out of the day → closing, from the
 * posted ledger) and the day's expenses (approved, waiting, rejected) + IOUs. Mailed every
 * evening (acc-sfl:day-end-report, scheduled) to users with ac_day_end_report.view; also a page.
 */
class DayEndReport
{
    /** Settings > Mail Notifications key (on / off + hand-picked recipients). */
    public const MAIL_ACTION = 'accounts.day_end_report';

    public const PERMISSION = 'ac_day_end_report.view';

    public function build(Carbon $date): array
    {
        $day = $date->toDateString();
        // Inactive accounts too: one that still holds cash must not drop out of the totals.
        $accounts = AcAccount::query()->with('branch')->orderBy('name')->get();

        $before = DB::table('ac_transactions')->where('transaction_date', '<', $day)
            ->groupBy('account_id')->selectRaw('account_id, SUM(debit) - SUM(credit) bal')->pluck('bal', 'account_id');
        $today = DB::table('ac_transactions')->whereDate('transaction_date', $day)
            ->groupBy('account_id', 'transaction_type')->selectRaw('account_id, transaction_type, SUM(debit) d, SUM(credit) c')->get()->groupBy('account_id');

        $pending = $this->pendingItems($day);

        $rows = $accounts->map(function (AcAccount $account) use ($before, $today, $pending) {
            $t = $today->get($account->id, collect());
            $sum = fn (string $type, string $col) => (float) $t->where('transaction_type', $type)->sum($col);
            $opening = (float) ($before[$account->id] ?? 0);
            $in = (float) $t->sum('d');
            $out = (float) $t->sum('c');
            $closing = round($opening + $in - $out, 2);
            $mine = $pending->where('account_id', $account->id);
            $pendingIn = round((float) $mine->where('cash', 'in')->sum('amount'), 2);
            $pendingOut = round((float) $mine->where('cash', 'out')->sum('amount'), 2);

            return [
                'account' => $account->name.($account->is_active ? '' : ' (inactive)'),
                'branch' => $account->branch?->name,
                'opening' => $opening,
                'received' => $sum(AcTransaction::TYPE_BALANCE_RECEIVE, 'd') + $sum(AcTransaction::TYPE_OPENING_BALANCE, 'd'),
                'expenses' => $sum(AcTransaction::TYPE_EXPENSE, 'c'),
                'iou_issued' => $sum(AcTransaction::TYPE_IOU_ISSUE, 'c') + $sum(AcTransaction::TYPE_IOU_CORRECTION, 'c') - $sum(AcTransaction::TYPE_IOU_CORRECTION, 'd'),
                'iou_returned' => $sum(AcTransaction::TYPE_IOU_ADJUSTMENT, 'd'),
                'in' => $in,
                'out' => $out,
                'closing' => $closing,
                'pending_in' => $pendingIn,
                'pending_out' => $pendingOut,
                'after_pending' => round($closing + $pendingIn - $pendingOut, 2),
            ];
        })->filter(fn ($r) => $r['opening'] != 0 || $r['in'] != 0 || $r['out'] != 0 || $r['pending_in'] != 0 || $r['pending_out'] != 0)->values();

        $expenses = AcExpense::query()->with(['account', 'branch', 'details.particular', 'iou'])
            ->whereDate('expense_date', $day)->orderBy('id')->get()
            ->map(fn (AcExpense $e) => [
                'no' => $e->expense_no,
                'account' => $e->account?->name,
                'particulars' => $e->details->map(fn ($d) => $d->particular?->name)->filter()->unique()->implode(', ') ?: strip_tags((string) $e->description),
                'paid_to' => $e->company_name ?: $e->receiver_name,
                'iou' => $e->iou?->iou_no,
                'status' => $e->status,
                'amount' => (float) $e->total_amount,
            ]);

        $ious = AcExpenseIou::query()->with(['account', 'employee'])->whereDate('issue_date', $day)->orderBy('id')->get()
            ->map(fn (AcExpenseIou $i) => [
                'no' => $i->iou_no,
                'account' => $i->account?->name,
                'employee' => $i->employee?->name ?? $i->receiver_name,
                'approval' => $i->approval_status,
                'amount' => (float) $i->amount,
            ]);

        $keys = ['opening', 'received', 'expenses', 'iou_issued', 'iou_returned', 'in', 'out', 'closing', 'pending_in', 'pending_out', 'after_pending'];

        return [
            'date' => $date,
            'accounts' => $rows,
            'totals' => collect($keys)->mapWithKeys(fn ($k) => [$k => round($rows->sum($k), 2)])->all(),
            'expenses' => $expenses,
            'expense_totals' => [
                'approved' => $expenses->where('status', AcExpense::STATUS_APPROVED)->sum('amount'),
                'pending' => $expenses->where('status', AcExpense::STATUS_PENDING)->sum('amount'),
                'rejected' => $expenses->where('status', AcExpense::STATUS_REJECTED)->sum('amount'),
                'via_iou' => $expenses->where('status', AcExpense::STATUS_APPROVED)->whereNotNull('iou')->sum('amount'),
            ],
            'ious' => $ious,
            'pending' => $pending,
            'pending_totals' => [
                'in' => round((float) $pending->where('cash', 'in')->sum('amount'), 2),
                'out' => round((float) $pending->where('cash', 'out')->sum('amount'), 2),
                'via_iou' => round((float) $pending->where('cash', 'iou')->sum('amount'), 2),
            ],
        ];
    }

    /**
     * Everything dated on or before the day that is still waiting for approval. None of it is
     * in the ledger yet; once approved a cash receive adds to the account ('in'), an expense
     * or IOU takes from it ('out'). An expense against an IOU ('iou') moves no cash itself:
     * the IOU advance already left the account, it is settled at IOU adjustment.
     */
    private function pendingItems(string $day): Collection
    {
        $receives = AcBalanceReceive::query()->with(['account', 'particular'])
            ->where('status', AcBalanceReceive::STATUS_PENDING)->whereDate('receive_date', '<=', $day)->orderBy('receive_date')->orderBy('id')->get()
            ->map(fn (AcBalanceReceive $r) => [
                'type' => 'Cash Receive', 'no' => $r->receive_no, 'date' => $r->receive_date, 'account_id' => $r->account_id, 'account' => $r->account?->name,
                'details' => $r->particular?->name ?: strip_tags((string) $r->description), 'cash' => 'in', 'amount' => (float) $r->amount,
            ]);

        $expenses = AcExpense::query()->with(['account', 'details.particular', 'iou'])
            ->where('status', AcExpense::STATUS_PENDING)->whereDate('expense_date', '<=', $day)->orderBy('expense_date')->orderBy('id')->get()
            ->map(fn (AcExpense $e) => [
                'type' => 'Expense', 'no' => $e->expense_no, 'date' => $e->expense_date, 'account_id' => $e->account_id, 'account' => $e->account?->name,
                'details' => trim(($e->details->map(fn ($d) => $d->particular?->name)->filter()->unique()->implode(', ') ?: strip_tags((string) $e->description))
                    .($e->iou ? " (from IOU {$e->iou->iou_no})" : '')),
                'cash' => $e->iou_id ? 'iou' : 'out', 'amount' => (float) $e->total_amount,
            ]);

        $ious = AcExpenseIou::query()->with(['account', 'employee'])
            ->where('approval_status', AcExpenseIou::APPROVAL_PENDING)->whereDate('issue_date', '<=', $day)->orderBy('issue_date')->orderBy('id')->get()
            ->map(fn (AcExpenseIou $i) => [
                'type' => 'IOU', 'no' => $i->iou_no, 'date' => $i->issue_date, 'account_id' => $i->account_id, 'account' => $i->account?->name,
                'details' => $i->employee?->name ?? $i->receiver_name, 'cash' => 'out', 'amount' => (float) $i->amount,
            ]);

        return $receives->toBase()->concat($expenses)->concat($ious)->values();
    }

    /** Users with the permission (valid email), unless Settings > Mail Notifications picks others. */
    public function recipients(): Collection
    {
        $override = class_exists(\App\Models\MailNotificationSetting::class)
            ? \App\Models\MailNotificationSetting::recipientEmailOverride(self::MAIL_ACTION) : null;
        $emails = $override ? collect($override) : User::all()
            ->filter(fn (User $user) => (int) ($user->status ?? 1) === 1 && $user->hasPermission(self::PERMISSION))
            ->pluck('email');

        if ($test = config('approval.test_recipients', [])) {
            $emails = collect($test);
        }

        return $emails->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL) !== false)->unique()->values();
    }

    /** Mails the report; returns the addresses it went to (empty = disabled / nobody to send to). */
    public function send(Carbon $date, ?array $to = null): array
    {
        if ($to === null && ! $this->mailEnabled()) {
            return [];
        }
        $emails = $to ?? $this->recipients()->all();
        if ($emails === []) {
            \Illuminate\Support\Facades\Log::warning('Day end report mail skipped: no user with '.self::PERMISSION.' has a valid email.');

            return [];
        }

        $report = $this->build($date);
        $closing = number_format($report['totals']['closing'], 2);
        $pendingCount = $report['pending']->count();
        \Illuminate\Support\Facades\Mail::send('acc-sfl::emails.day-end-report', [
            'report' => $report,
            'url' => route('acc-sfl.reports.day-end.index', ['date' => $date->toDateString()]),
        ], fn ($message) => $message->to($emails)
            ->subject('Day End Report '.$date->format('d M Y')." - Cash in Hand Tk {$closing}, Expenses Tk ".number_format($report['expense_totals']['approved'], 2)
                .($pendingCount ? ", {$pendingCount} waiting approval" : '')));

        return $emails;
    }

    public function mailEnabled(): bool
    {
        return ! class_exists(\App\Models\MailNotificationSetting::class) || \App\Models\MailNotificationSetting::enabled(self::MAIL_ACTION);
    }
}
