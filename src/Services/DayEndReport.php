<?php

namespace ME\AccSfl\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use ME\AccSfl\Models\AcAccount;
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
        $accounts = AcAccount::query()->active()->with('branch')->orderBy('name')->get();

        $before = DB::table('ac_transactions')->where('transaction_date', '<', $day)
            ->groupBy('account_id')->selectRaw('account_id, SUM(debit) - SUM(credit) bal')->pluck('bal', 'account_id');
        $today = DB::table('ac_transactions')->whereDate('transaction_date', $day)
            ->groupBy('account_id', 'transaction_type')->selectRaw('account_id, transaction_type, SUM(debit) d, SUM(credit) c')->get()->groupBy('account_id');

        $rows = $accounts->map(function (AcAccount $account) use ($before, $today) {
            $t = $today->get($account->id, collect());
            $sum = fn (string $type, string $col) => (float) $t->where('transaction_type', $type)->sum($col);
            $opening = (float) ($before[$account->id] ?? 0);
            $in = (float) $t->sum('d');
            $out = (float) $t->sum('c');

            return [
                'account' => $account->name,
                'branch' => $account->branch?->name,
                'opening' => $opening,
                'received' => $sum(AcTransaction::TYPE_BALANCE_RECEIVE, 'd') + $sum(AcTransaction::TYPE_OPENING_BALANCE, 'd'),
                'expenses' => $sum(AcTransaction::TYPE_EXPENSE, 'c'),
                'iou_issued' => $sum(AcTransaction::TYPE_IOU_ISSUE, 'c') + $sum(AcTransaction::TYPE_IOU_CORRECTION, 'c') - $sum(AcTransaction::TYPE_IOU_CORRECTION, 'd'),
                'iou_returned' => $sum(AcTransaction::TYPE_IOU_ADJUSTMENT, 'd'),
                'in' => $in,
                'out' => $out,
                'closing' => round($opening + $in - $out, 2),
            ];
        })->filter(fn ($r) => $r['opening'] != 0 || $r['in'] != 0 || $r['out'] != 0)->values();

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

        return [
            'date' => $date,
            'accounts' => $rows,
            'totals' => collect(['opening', 'received', 'expenses', 'iou_issued', 'iou_returned', 'in', 'out', 'closing'])->mapWithKeys(fn ($k) => [$k => round($rows->sum($k), 2)])->all(),
            'expenses' => $expenses,
            'expense_totals' => [
                'approved' => $expenses->where('status', AcExpense::STATUS_APPROVED)->sum('amount'),
                'pending' => $expenses->where('status', AcExpense::STATUS_PENDING)->sum('amount'),
                'rejected' => $expenses->where('status', AcExpense::STATUS_REJECTED)->sum('amount'),
            ],
            'ious' => $ious,
            'waiting' => [
                'expenses' => AcExpense::query()->where('status', AcExpense::STATUS_PENDING)->count(),
                'ious' => AcExpenseIou::query()->where('approval_status', AcExpenseIou::APPROVAL_PENDING)->count(),
                'balance_receives' => DB::table('ac_balance_receives')->whereNull('deleted_at')->where('status', 'pending')->count(),
            ],
        ];
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
        \Illuminate\Support\Facades\Mail::send('acc-sfl::emails.day-end-report', [
            'report' => $report,
            'url' => route('acc-sfl.reports.day-end.index', ['date' => $date->toDateString()]),
        ], fn ($message) => $message->to($emails)
            ->subject('Day End Report '.$date->format('d M Y')." - Cash in Hand Tk {$closing}, Expenses Tk ".number_format($report['expense_totals']['approved'], 2)));

        return $emails;
    }

    public function mailEnabled(): bool
    {
        return ! class_exists(\App\Models\MailNotificationSetting::class) || \App\Models\MailNotificationSetting::enabled(self::MAIL_ACTION);
    }
}
