<?php

namespace ME\AccSfl\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use ME\AccSfl\Services\DayEndReport;

/** Mails the day end report (cash in hand + the day's expenses). Scheduled daily — see AccSflServiceProvider. */
class DayEndReportCommand extends Command
{
    protected $signature = 'acc-sfl:day-end-report {--date= : Day to report (default today)} {--to=* : Send only to these addresses}';

    protected $description = 'Mail the Accounts day end report (cash in hand + day expenses) to users with ac_day_end_report.view';

    public function handle(DayEndReport $report): int
    {
        $this->useHostMailSettings();

        $date = $this->option('date') ? Carbon::parse($this->option('date')) : today();
        $sent = $report->send($date, $this->option('to') ?: null);

        $this->line($sent ? 'Day end report '.$date->toDateString().' sent to: '.implode(', ', $sent) : 'Not sent (disabled in Settings > Mail Notifications, or no recipient).');

        return self::SUCCESS;
    }

    /**
     * The host applies the SMTP saved in General Settings (generals table) only for web
     * requests — its general() helper returns null in console — so the scheduled run would
     * fall back to the .env mailer (often "log"). Apply the same settings here.
     */
    private function useHostMailSettings(): void
    {
        if (! class_exists(\App\Models\General::class)) {
            return;
        }

        $general = \App\Models\General::query()->first();
        if (! $general?->mail_host) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $general->mail_host,
            'mail.mailers.smtp.port' => $general->mail_port,
            'mail.mailers.smtp.encryption' => $general->mail_encryption,
            'mail.mailers.smtp.username' => $general->mail_username,
            'mail.mailers.smtp.password' => $general->mail_password,
        ]);
        if ($general->mail_from_address) {
            config(['mail.from.address' => $general->mail_from_address, 'mail.from.name' => $general->mail_from_name ?: config('app.name')]);
        }
        app('mail.manager')->purge('smtp');
    }
}
