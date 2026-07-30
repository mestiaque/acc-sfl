<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\AccSfl\Exports\ExpenseIouReportExport;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcBranch;
use ME\AccSfl\Models\AcExpenseIou;
use ME\AccSfl\Models\AcPaymentMethod;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExpenseIouReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('ac_report.view');

        $expenseIous = $this->filteredQuery($request)->latest('issue_date')->latest('id')->paginate(20)->withQueryString();
        $totals = $this->totals($request);

        return view('acc-sfl::admin.reports.expense-iou-report', array_merge(
            compact('expenseIous', 'totals'),
            $this->filterOptions(),
        ));
    }

    public function print(Request $request): View
    {
        $this->authorize('ac_report.export');

        $expenseIous = $this->filteredQuery($request)->latest('issue_date')->latest('id')->get();
        $totals = $this->totals($request);

        return view('acc-sfl::admin.reports.expense-iou-report-print', compact('expenseIous', 'totals'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_report.export');

        $expenseIous = $this->filteredQuery($request)->latest('issue_date')->latest('id')->get();
        $totals = $this->totals($request);

        return Excel::download(new ExpenseIouReportExport($expenseIous, $totals), 'expense-iou-report.xlsx');
    }

    private function filteredQuery(Request $request): Builder
    {
        return AcExpenseIou::query()
            ->with(['branch', 'account', 'employee', 'paymentMethod'])
            ->when(AcAccount::currentUserTiedAccount(), fn ($q, $tied) => $q->where('account_id', $tied->id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(function ($query) use ($search) {
                    $query->where('iou_no', 'like', "%{$search}%")
                        ->orWhere('receiver_name', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('account_id'), fn ($q) => $q->where('account_id', $request->integer('account_id')))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('payment_method_id'), fn ($q) => $q->where('payment_method_id', $request->integer('payment_method_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('issue_date', '>=', $request->date('from_date')))
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('issue_date', '<=', $request->date('to_date')))
            ->when($request->filled('min_amount'), fn ($q) => $q->where('amount', '>=', $request->float('min_amount')))
            ->when($request->filled('max_amount'), fn ($q) => $q->where('amount', '<=', $request->float('max_amount')));
    }

    private function totals(Request $request): array
    {
        $query = $this->filteredQuery($request);

        return [
            'count' => (clone $query)->count(),
            'amount' => (float) (clone $query)->sum('amount'),
            'pending_amount' => (float) (clone $query)->where('status', AcExpenseIou::STATUS_PENDING)->sum('amount'),
            'adjusted_amount' => (float) (clone $query)->where('status', AcExpenseIou::STATUS_ADJUSTED)->sum('amount'),
        ];
    }

    private function filterOptions(): array
    {
        return [
            'branches' => AcBranch::query()->active()->orderBy('name')->get(),
            'accounts' => AcAccount::query()->active()->visibleToCurrentUser()->orderBy('name')->get(),
            'paymentMethods' => AcPaymentMethod::query()->active()->orderBy('name')->get(),
            'employees' => $this->activeEmployees(),
        ];
    }

    /**
     * HR is an optional integration for this module (see AcExpenseIou::employee()), so
     * this is guarded rather than a hard dependency - installs without the HR package
     * simply get an empty employee filter instead of a fatal error.
     */
    private function activeEmployees(): \Illuminate\Support\Collection
    {
        if (! class_exists(\ME\Hr\Models\HrEmployee::class)) {
            return collect();
        }

        return \ME\Hr\Models\HrEmployee::query()
            ->whereNull('exited_at')
            ->where(fn ($q) => $q->whereNull('employment_status')->orWhereIn('employment_status', ['', 'regular', 'active']))
            ->with(['department:id,name', 'designation:id,name'])
            ->orderBy('employee_id')
            ->get(['id', 'employee_id', 'name', 'department_id', 'designation_id']);
    }
}
