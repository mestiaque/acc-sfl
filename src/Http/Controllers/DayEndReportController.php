<?php

namespace ME\AccSfl\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\AccSfl\Services\DayEndReport;

class DayEndReportController extends Controller
{
    public function __construct(private readonly DayEndReport $report)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize(DayEndReport::PERMISSION);

        return view('acc-sfl::admin.reports.day-end-report', [
            'report' => $this->report->build($this->date($request)),
            'recipients' => $this->report->recipients(),
            'mailEnabled' => $this->report->mailEnabled(),
        ]);
    }

    public function print(Request $request): View
    {
        $this->authorize(DayEndReport::PERMISSION);

        return view('acc-sfl::admin.reports.day-end-report-print', ['report' => $this->report->build($this->date($request))]);
    }

    /** Send the mail now (same as the evening schedule) for the picked day. */
    public function send(Request $request): RedirectResponse
    {
        $this->authorize(DayEndReport::PERMISSION);
        $sent = $this->report->send($this->date($request), null, 'manual');

        return back()->with($sent ? 'success' : 'error', $sent
            ? 'Day end report mailed to '.count($sent).' user(s): '.implode(', ', $sent)
            : 'Not sent — mail is off in Settings > Mail Notifications, or no user with the Day End Report permission has an email.');
    }

    private function date(Request $request): Carbon
    {
        return $request->filled('date') ? Carbon::parse($request->input('date'))->startOfDay() : today();
    }
}
