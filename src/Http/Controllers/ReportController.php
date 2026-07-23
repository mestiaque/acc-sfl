<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\AccSfl\Exports\CashFlowMonthlyExport;
use ME\AccSfl\Exports\CashFlowYearlyExport;
use ME\AccSfl\Models\AcBranch;
use ME\AccSfl\Services\CashFlowReportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(private readonly CashFlowReportService $reports)
    {
    }

    public function monthly(Request $request): View
    {
        $this->authorize('ac_report.view');

        [$year, $month, $branchId] = $this->monthlyParams($request);
        $report = $this->reports->monthly($year, $month, $branchId);
        $branches = AcBranch::query()->active()->orderBy('name')->get();

        return view('acc-sfl::admin.reports.cash-flow-monthly', compact('report', 'branches', 'branchId'));
    }

    public function monthlyPrint(Request $request): View
    {
        $this->authorize('ac_report.export');

        [$year, $month, $branchId] = $this->monthlyParams($request);
        $report = $this->reports->monthly($year, $month, $branchId);

        return view('acc-sfl::admin.reports.cash-flow-monthly-print', compact('report'));
    }

    public function monthlyExport(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_report.export');

        [$year, $month, $branchId] = $this->monthlyParams($request);
        $report = $this->reports->monthly($year, $month, $branchId);

        return Excel::download(new CashFlowMonthlyExport($report), "cash-flow-{$report['label']}.xlsx");
    }

    public function yearly(Request $request): View
    {
        $this->authorize('ac_report.view');

        [$fyStart, $branchId] = $this->yearlyParams($request);
        $report = $this->reports->yearly($fyStart, $branchId);
        $branches = AcBranch::query()->active()->orderBy('name')->get();

        return view('acc-sfl::admin.reports.cash-flow-yearly', compact('report', 'branches', 'branchId'));
    }

    public function yearlyPrint(Request $request): View
    {
        $this->authorize('ac_report.export');

        [$fyStart, $branchId] = $this->yearlyParams($request);
        $report = $this->reports->yearly($fyStart, $branchId);

        return view('acc-sfl::admin.reports.cash-flow-yearly-print', compact('report'));
    }

    public function yearlyExport(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_report.export');

        [$fyStart, $branchId] = $this->yearlyParams($request);
        $report = $this->reports->yearly($fyStart, $branchId);

        return Excel::download(new CashFlowYearlyExport($report), "cash-flow-{$report['label']}.xlsx");
    }

    /**
     * @return array{0: int, 1: int, 2: ?int}
     */
    private function monthlyParams(Request $request): array
    {
        $today = Carbon::today();

        return [
            $request->integer('year', $today->year),
            $request->integer('month', $today->month),
            $request->filled('branch_id') ? $request->integer('branch_id') : null,
        ];
    }

    /**
     * @return array{0: int, 1: ?int}
     */
    private function yearlyParams(Request $request): array
    {
        $today = Carbon::today();
        // The fiscal year (July -> June) containing "today" starts this calendar year
        // if today is on/after July, otherwise it started the previous calendar year.
        $defaultFyStart = $today->month >= 7 ? $today->year : $today->year - 1;

        return [
            $request->integer('fiscal_year', $defaultFyStart),
            $request->filled('branch_id') ? $request->integer('branch_id') : null,
        ];
    }
}
