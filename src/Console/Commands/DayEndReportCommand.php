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
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : today();
        $sent = $report->send($date, $this->option('to') ?: null);

        $this->line($sent ? 'Day end report '.$date->toDateString().' sent to: '.implode(', ', $sent) : 'Not sent (disabled in Settings > Mail Notifications, or no recipient).');

        return self::SUCCESS;
    }
}
