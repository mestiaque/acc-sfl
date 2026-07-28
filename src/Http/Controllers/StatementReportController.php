<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\AccSfl\Exports\StatementReportExport;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcTransaction;
use ME\AccSfl\Services\StatementReportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StatementReportController extends Controller
{
    public function __construct(private readonly StatementReportService $statements)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('ac_report.view');

        $accounts = AcAccount::query()->active()->visibleToCurrentUser()->orderBy('name')->get();
        $accountId = $request->filled('account_id') ? $request->integer('account_id') : $accounts->first()?->id;

        $statement = $accountId ? $this->statements->build(
            $accountId,
            $this->fromDate($request),
            $this->toDate($request),
            $request->filled('transaction_type') ? $request->string('transaction_type')->toString() : null,
        ) : null;

        return view('acc-sfl::admin.reports.statement-report', array_merge(
            compact('statement', 'accounts', 'accountId'),
            $this->filterOptions(),
        ));
    }

    public function print(Request $request): View
    {
        $this->authorize('ac_report.export');

        $statement = $this->statements->build(
            $request->integer('account_id'),
            $this->fromDate($request),
            $this->toDate($request),
            $request->filled('transaction_type') ? $request->string('transaction_type')->toString() : null,
        );

        return view('acc-sfl::admin.reports.statement-report-print', compact('statement'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_report.export');

        $statement = $this->statements->build(
            $request->integer('account_id'),
            $this->fromDate($request),
            $this->toDate($request),
            $request->filled('transaction_type') ? $request->string('transaction_type')->toString() : null,
        );

        return Excel::download(new StatementReportExport($statement), 'statement-'.str($statement['account']->name)->slug().'.xlsx');
    }

    private function fromDate(Request $request): Carbon
    {
        return $request->filled('from_date') ? $request->date('from_date') : Carbon::today()->startOfMonth();
    }

    private function toDate(Request $request): Carbon
    {
        return $request->filled('to_date') ? $request->date('to_date') : Carbon::today();
    }

    private function filterOptions(): array
    {
        return [
            'transactionTypes' => [
                AcTransaction::TYPE_OPENING_BALANCE,
                AcTransaction::TYPE_BALANCE_RECEIVE,
                AcTransaction::TYPE_EXPENSE,
                AcTransaction::TYPE_IOU_ISSUE,
                AcTransaction::TYPE_IOU_ADJUSTMENT,
            ],
        ];
    }
}
