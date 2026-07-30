<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\AccSfl\Exports\BalanceReceiveReportExport;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcBalanceReceive;
use ME\AccSfl\Models\AcBranch;
use ME\AccSfl\Models\AcMasterParticular;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BalanceReceiveReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('ac_report.view');

        $receives = $this->filteredQuery($request)->latest('receive_date')->latest('id')->paginate(20)->withQueryString();
        $totals = $this->totals($request);

        return view('acc-sfl::admin.reports.balance-receive-report', array_merge(
            compact('receives', 'totals'),
            $this->filterOptions(),
        ));
    }

    public function print(Request $request): View
    {
        $this->authorize('ac_report.export');

        $receives = $this->filteredQuery($request)->latest('receive_date')->latest('id')->get();
        $totals = $this->totals($request);

        return view('acc-sfl::admin.reports.balance-receive-report-print', compact('receives', 'totals'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_report.export');

        $receives = $this->filteredQuery($request)->latest('receive_date')->latest('id')->get();
        $totals = $this->totals($request);

        return Excel::download(new BalanceReceiveReportExport($receives, $totals), 'balance-receive-report.xlsx');
    }

    private function filteredQuery(Request $request): Builder
    {
        return AcBalanceReceive::query()
            ->with(['branch', 'account', 'particular.masterParticular', 'creator', 'transactions'])
            ->when(AcAccount::currentUserTiedAccount(), fn ($q, $tied) => $q->where('account_id', $tied->id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(function ($query) use ($search) {
                    $query->where('receive_no', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('account_id'), fn ($q) => $q->where('account_id', $request->integer('account_id')))
            ->when($request->filled('master_particular_id'), fn ($q) => $q->whereHas(
                'particular',
                fn ($p) => $p->where('master_particular_id', $request->integer('master_particular_id'))
            ))
            ->when($request->filled('particular_id'), fn ($q) => $q->where('particular_id', $request->integer('particular_id')))
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('receive_date', '>=', $request->date('from_date')))
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('receive_date', '<=', $request->date('to_date')))
            ->when($request->filled('min_amount'), fn ($q) => $q->where('amount', '>=', $request->float('min_amount')))
            ->when($request->filled('max_amount'), fn ($q) => $q->where('amount', '<=', $request->float('max_amount')));
    }

    private function totals(Request $request): array
    {
        $query = $this->filteredQuery($request);

        return [
            'count' => (clone $query)->count(),
            'amount' => (float) (clone $query)->sum('amount'),
        ];
    }

    private function filterOptions(): array
    {
        $allowedParticularIds = AcAccount::currentUserAllowedParticularIds();

        return [
            'branches' => AcBranch::query()->active()->orderBy('name')->get(),
            'accounts' => AcAccount::query()->active()->visibleToCurrentUser()->orderBy('name')->get(),
            'masterParticulars' => AcMasterParticular::query()->debit()->active()
                ->with(['particulars' => fn ($q) => $q->active()->orderBy('code')
                    ->when($allowedParticularIds !== null, fn ($q2) => $q2->whereIn('id', $allowedParticularIds))])
                ->orderBy('id')
                ->get(),
        ];
    }
}
