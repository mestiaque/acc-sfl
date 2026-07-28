<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\AccSfl\Exports\ExpenseReportExport;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcBranch;
use ME\AccSfl\Models\AcExpense;
use ME\AccSfl\Models\AcMasterParticular;
use ME\AccSfl\Models\AcPaymentMethod;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExpenseReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('ac_report.view');

        $expenses = $this->filteredQuery($request)->latest('expense_date')->latest('id')->paginate(20)->withQueryString();
        $totals = $this->totals($request);

        return view('acc-sfl::admin.reports.expense-report', array_merge(
            compact('expenses', 'totals'),
            $this->filterOptions(),
        ));
    }

    public function print(Request $request): View
    {
        $this->authorize('ac_report.export');

        $expenses = $this->filteredQuery($request)->latest('expense_date')->latest('id')->get();
        $totals = $this->totals($request);

        return view('acc-sfl::admin.reports.expense-report-print', compact('expenses', 'totals'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_report.export');

        $expenses = $this->filteredQuery($request)->latest('expense_date')->latest('id')->get();
        $totals = $this->totals($request);

        return Excel::download(new ExpenseReportExport($expenses, $totals), 'expense-report.xlsx');
    }

    private function filteredQuery(Request $request): Builder
    {
        return AcExpense::query()
            ->with(['branch', 'account', 'paymentMethod', 'creator', 'details.particular.masterParticular'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(function ($query) use ($search) {
                    $query->where('expense_no', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('receiver_name', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('account_id'), fn ($q) => $q->where('account_id', $request->integer('account_id')))
            ->when($request->filled('payment_method_id'), fn ($q) => $q->where('payment_method_id', $request->integer('payment_method_id')))
            ->when($request->filled('master_particular_id'), fn ($q) => $q->whereHas(
                'details.particular',
                fn ($p) => $p->where('master_particular_id', $request->integer('master_particular_id'))
            ))
            ->when($request->filled('particular_id'), fn ($q) => $q->whereHas(
                'details',
                fn ($d) => $d->where('particular_id', $request->integer('particular_id'))
            ))
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('expense_date', '>=', $request->date('from_date')))
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('expense_date', '<=', $request->date('to_date')))
            ->when($request->filled('min_amount'), fn ($q) => $q->where('total_amount', '>=', $request->float('min_amount')))
            ->when($request->filled('max_amount'), fn ($q) => $q->where('total_amount', '<=', $request->float('max_amount')));
    }

    private function totals(Request $request): array
    {
        $query = $this->filteredQuery($request);

        return [
            'count' => (clone $query)->count(),
            'amount' => (float) (clone $query)->sum('total_amount'),
        ];
    }

    private function filterOptions(): array
    {
        return [
            'branches' => AcBranch::query()->active()->orderBy('name')->get(),
            'accounts' => AcAccount::query()->active()->visibleToCurrentUser()->orderBy('name')->get(),
            'paymentMethods' => AcPaymentMethod::query()->active()->orderBy('name')->get(),
            'masterParticulars' => AcMasterParticular::query()->credit()->active()
                ->with(['particulars' => fn ($q) => $q->active()->orderBy('code')])
                ->orderBy('id')
                ->get(),
        ];
    }
}
