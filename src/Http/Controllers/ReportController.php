<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\AccSfl\Exports\CashFlowMonthlyExport;
use ME\AccSfl\Exports\CashFlowYearlyExport;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcBranch;
use ME\AccSfl\Models\AcMasterParticular;
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

        [$year, $month, $branchId, $accountId, $masterParticularId, $particularId] = $this->monthlyParams($request);
        $report = $this->reports->monthly($year, $month, $branchId, $accountId, $masterParticularId, $particularId);

        return view('acc-sfl::admin.reports.cash-flow-monthly', array_merge(
            compact('report', 'branchId', 'accountId', 'masterParticularId', 'particularId'),
            $this->filterOptions(),
        ));
    }

    public function monthlyPrint(Request $request): View
    {
        $this->authorize('ac_report.export');

        [$year, $month, $branchId, $accountId, $masterParticularId, $particularId] = $this->monthlyParams($request);
        $report = $this->reports->monthly($year, $month, $branchId, $accountId, $masterParticularId, $particularId);

        return view('acc-sfl::admin.reports.cash-flow-monthly-print', compact('report'));
    }

    public function monthlyExport(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_report.export');

        [$year, $month, $branchId, $accountId, $masterParticularId, $particularId] = $this->monthlyParams($request);
        $report = $this->reports->monthly($year, $month, $branchId, $accountId, $masterParticularId, $particularId);

        return Excel::download(new CashFlowMonthlyExport($report), "cash-flow-{$report['label']}.xlsx");
    }

    public function yearly(Request $request): View
    {
        $this->authorize('ac_report.view');

        [$fyStart, $branchId, $accountId, $masterParticularId, $particularId] = $this->yearlyParams($request);
        $report = $this->reports->yearly($fyStart, $branchId, $accountId, $masterParticularId, $particularId);

        return view('acc-sfl::admin.reports.cash-flow-yearly', array_merge(
            compact('report', 'branchId', 'accountId', 'masterParticularId', 'particularId'),
            $this->filterOptions(),
        ));
    }

    public function yearlyPrint(Request $request): View
    {
        $this->authorize('ac_report.export');

        [$fyStart, $branchId, $accountId, $masterParticularId, $particularId] = $this->yearlyParams($request);
        $report = $this->reports->yearly($fyStart, $branchId, $accountId, $masterParticularId, $particularId);

        return view('acc-sfl::admin.reports.cash-flow-yearly-print', compact('report'));
    }

    public function yearlyExport(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_report.export');

        [$fyStart, $branchId, $accountId, $masterParticularId, $particularId] = $this->yearlyParams($request);
        $report = $this->reports->yearly($fyStart, $branchId, $accountId, $masterParticularId, $particularId);

        return Excel::download(new CashFlowYearlyExport($report), "cash-flow-{$report['label']}.xlsx");
    }

    /**
     * @return array{0: int, 1: int, 2: ?int, 3: ?int, 4: ?int, 5: ?int}
     */
    private function monthlyParams(Request $request): array
    {
        $today = Carbon::today();

        return [
            $request->integer('year', $today->year),
            $request->integer('month', $today->month),
            $request->filled('branch_id') ? $request->integer('branch_id') : null,
            $request->filled('account_id') ? $request->integer('account_id') : null,
            $request->filled('master_particular_id') ? $request->integer('master_particular_id') : null,
            $request->filled('particular_id') ? $request->integer('particular_id') : null,
        ];
    }

    /**
     * @return array{0: int, 1: ?int, 2: ?int, 3: ?int, 4: ?int}
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
            $request->filled('account_id') ? $request->integer('account_id') : null,
            $request->filled('master_particular_id') ? $request->integer('master_particular_id') : null,
            $request->filled('particular_id') ? $request->integer('particular_id') : null,
        ];
    }

    private function filterOptions(): array
    {
        $allowedParticularIds = AcAccount::currentUserAllowedParticularIds();

        return [
            'branches' => AcBranch::query()->active()->orderBy('name')->get(),
            'accounts' => AcAccount::query()->active()->visibleToCurrentUser()->orderBy('name')->get(),
            'masterParticulars' => AcMasterParticular::query()->active()
                ->with(['particulars' => fn ($q) => $q->active()->orderBy('code')
                    ->when($allowedParticularIds !== null, fn ($q2) => $q2->whereIn('id', $allowedParticularIds))])
                ->orderBy('id')
                ->get(),
        ];
    }
}
