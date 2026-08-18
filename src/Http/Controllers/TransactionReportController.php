<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\AccSfl\Exports\TransactionReportExport;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcBranch;
use ME\AccSfl\Models\AcPaymentMethod;
use ME\AccSfl\Models\AcTransaction;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TransactionReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('ac_report.view');

        $transactions = $this->filteredQuery($request)->latest('transaction_date')->latest('id')->paginate(20)->withQueryString();
        $totals = $this->totals($request);

        return view('acc-sfl::admin.reports.transaction-report', array_merge(
            compact('transactions', 'totals'),
            $this->filterOptions(),
        ));
    }

    public function print(Request $request): View
    {
        $this->authorize('ac_report.export');

        $transactions = $this->filteredQuery($request)->latest('transaction_date')->latest('id')->get();
        $totals = $this->totals($request);

        return view('acc-sfl::admin.reports.transaction-report-print', compact('transactions', 'totals'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_report.export');

        $transactions = $this->filteredQuery($request)->latest('transaction_date')->latest('id')->get();
        $totals = $this->totals($request);

        return Excel::download(new TransactionReportExport($transactions, $totals), 'transaction-report.xlsx');
    }

    private function filteredQuery(Request $request): Builder
    {
        return AcTransaction::query()
            ->with(['branch', 'account', 'paymentMethod', 'creator'])
            ->when(AcAccount::currentUserTiedAccountIds(), fn ($q, $tiedIds) => $q->whereIn('account_id', $tiedIds))
            ->when($request->filled('search'), fn ($q) => $q->where('description', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('account_id'), fn ($q) => $q->where('account_id', $request->integer('account_id')))
            ->when($request->filled('payment_method_id'), fn ($q) => $q->where('payment_method_id', $request->integer('payment_method_id')))
            ->when($request->filled('transaction_type'), fn ($q) => $q->where('transaction_type', $request->string('transaction_type')))
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('transaction_date', '>=', $request->date('from_date')))
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('transaction_date', '<=', $request->date('to_date')));
    }

    private function totals(Request $request): array
    {
        $query = $this->filteredQuery($request);
        $debit = (float) (clone $query)->sum('debit');
        $credit = (float) (clone $query)->sum('credit');

        return [
            'count' => (clone $query)->count(),
            'debit' => $debit,
            'credit' => $credit,
            'net' => $debit - $credit,
        ];
    }

    private function filterOptions(): array
    {
        return [
            'branches' => AcBranch::query()->active()->orderBy('name')->get(),
            'accounts' => AcAccount::query()->active()->visibleToCurrentUser()->orderBy('name')->get(),
            'paymentMethods' => AcPaymentMethod::query()->active()->orderBy('name')->get(),
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
