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
use ME\AccSfl\Models\AcFiscalYear;
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

        [$year, $month, $branchId, $accountId, $masterParticularId, $particularIds] = $this->monthlyParams($request);
        $report = $this->reports->monthly($year, $month, $branchId, $accountId, $masterParticularId, $particularIds);

        return view('acc-sfl::admin.reports.cash-flow-monthly', array_merge(
            compact('report', 'branchId', 'accountId', 'masterParticularId', 'particularIds'),
            $this->filterOptions(),
        ));
    }

    public function monthlyPrint(Request $request): View
    {
        $this->authorize('ac_report.export');

        [$year, $month, $branchId, $accountId, $masterParticularId, $particularIds] = $this->monthlyParams($request);
        $report = $this->reports->monthly($year, $month, $branchId, $accountId, $masterParticularId, $particularIds);

        return view('acc-sfl::admin.reports.cash-flow-monthly-print', compact('report'));
    }

    public function monthlyExport(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_report.export');

        [$year, $month, $branchId, $accountId, $masterParticularId, $particularIds] = $this->monthlyParams($request);
        $report = $this->reports->monthly($year, $month, $branchId, $accountId, $masterParticularId, $particularIds);

        return Excel::download(new CashFlowMonthlyExport($report), "cash-flow-{$report['label']}.xlsx");
    }

    public function yearly(Request $request): View
    {
        $this->authorize('ac_report.view');

        [$fyStart, $fiscalYearId, $branchId, $accountId, $masterParticularId, $particularIds] = $this->yearlyParams($request);
        $report = $this->reports->yearly($fyStart, $branchId, $accountId, $masterParticularId, $particularIds);
        $fiscalYears = AcFiscalYear::query()->orderByDesc('start_year')->orderByDesc('start_month')->get();

        return view('acc-sfl::admin.reports.cash-flow-yearly', array_merge(
            compact('report', 'fiscalYears', 'fiscalYearId', 'branchId', 'accountId', 'masterParticularId', 'particularIds'),
            $this->filterOptions(),
        ));
    }

    public function yearlyPrint(Request $request): View
    {
        $this->authorize('ac_report.export');

        [$fyStart, $fiscalYearId, $branchId, $accountId, $masterParticularId, $particularIds] = $this->yearlyParams($request);
        $report = $this->reports->yearly($fyStart, $branchId, $accountId, $masterParticularId, $particularIds);

        return view('acc-sfl::admin.reports.cash-flow-yearly-print', compact('report'));
    }

    public function yearlyExport(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_report.export');

        [$fyStart, $fiscalYearId, $branchId, $accountId, $masterParticularId, $particularIds] = $this->yearlyParams($request);
        $report = $this->reports->yearly($fyStart, $branchId, $accountId, $masterParticularId, $particularIds);

        return Excel::download(new CashFlowYearlyExport($report), "cash-flow-{$report['label']}.xlsx");
    }

    /**
     * @return array{0: int, 1: int, 2: ?int, 3: ?int, 4: ?int, 5: ?array}
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
            $request->filled('particular_id') ? (array) $request->input('particular_id') : null,
        ];
    }

    /**
     * @return array{0: Carbon, 1: ?int, 2: ?int, 3: ?int, 4: ?int, 5: ?array}
     */
    private function yearlyParams(Request $request): array
    {
        $fiscalYear = $this->resolveFiscalYear($request);

        return [
            $fiscalYear ? $fiscalYear->startDate() : $this->fallbackFiscalYearStart(),
            $fiscalYear?->id,
            $request->filled('branch_id') ? $request->integer('branch_id') : null,
            $request->filled('account_id') ? $request->integer('account_id') : null,
            $request->filled('master_particular_id') ? $request->integer('master_particular_id') : null,
            $request->filled('particular_id') ? (array) $request->input('particular_id') : null,
        ];
    }

    /**
     * Resolves which AcFiscalYear master record the report should run for: the one picked
     * in the "fiscal_year" query param, or - failing that - whichever fiscal year contains
     * today, or - failing that (no fiscal years defined yet) - the most recent one.
     */
    private function resolveFiscalYear(Request $request): ?AcFiscalYear
    {
        if ($request->filled('fiscal_year')) {
            $fiscalYear = AcFiscalYear::find($request->integer('fiscal_year'));
            if ($fiscalYear) {
                return $fiscalYear;
            }
        }

        $today = Carbon::today();

        return AcFiscalYear::query()->get()->first(fn (AcFiscalYear $fy) => $today->between($fy->startDate(), $fy->endDate()))
            ?? AcFiscalYear::query()->orderByDesc('start_year')->orderByDesc('start_month')->first();
    }

    /**
     * Used only if the Fiscal Year master table is empty - keeps the report from
     * breaking by falling back to the BD July -> June fiscal year containing today.
     */
    private function fallbackFiscalYearStart(): Carbon
    {
        $today = Carbon::today();
        $year = $today->month >= 7 ? $today->year : $today->year - 1;

        return Carbon::create($year, 7, 1)->startOfMonth();
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
