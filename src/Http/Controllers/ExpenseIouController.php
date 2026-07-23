<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\AccSfl\Exports\ExpenseIouExport;
use ME\AccSfl\Http\Requests\ExpenseIouRequest;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcBranch;
use ME\AccSfl\Models\AcExpenseIou;
use ME\AccSfl\Models\AcPaymentMethod;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExpenseIouController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('ac_expense_iou.list');

        $expenseIous = $this->filteredQuery($request)->latest('id')->paginate(20)->withQueryString();

        $branches = AcBranch::query()->active()->orderBy('name')->get();
        $accounts = AcAccount::query()->active()->visibleToCurrentUser()->orderBy('name')->get();
        $paymentMethods = AcPaymentMethod::query()->active()->orderBy('name')->get();
        $users = \App\Models\User::query()->orderBy('name')->get(['id', 'name']);

        return view('acc-sfl::admin.expense-ious.index', compact('expenseIous', 'branches', 'accounts', 'paymentMethods', 'users'));
    }

    public function print(Request $request): View
    {
        $this->authorize('ac_expense_iou.list');

        $expenseIous = $this->filteredQuery($request)->latest('id')->get();

        return view('acc-sfl::admin.expense-ious.print', compact('expenseIous'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_expense_iou.list');

        $expenseIous = $this->filteredQuery($request)->latest('id')->get();

        return Excel::download(new ExpenseIouExport($expenseIous), 'expense-ious.xlsx');
    }

    private function filteredQuery(Request $request): Builder
    {
        return AcExpenseIou::query()
            ->with(['branch', 'account', 'employee', 'paymentMethod'])
            ->when($request->filled('search'), fn ($q) => $q->where('iou_no', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));
    }

    public function store(ExpenseIouRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['status'] = AcExpenseIou::STATUS_PENDING;

            AcExpenseIou::create($data);
        });

        return back()->with('success', 'Expense IOU issued successfully.');
    }

    public function update(ExpenseIouRequest $request, AcExpenseIou $expenseIou): RedirectResponse
    {
        DB::transaction(fn () => $expenseIou->update($request->validated()));

        return back()->with('success', 'Expense IOU updated successfully.');
    }

    public function adjust(Request $request, AcExpenseIou $expenseIou): RedirectResponse
    {
        $this->authorize('ac_expense_iou.edit');

        if ($expenseIou->status === AcExpenseIou::STATUS_ADJUSTED) {
            return back()->with('error', 'This IOU has already been adjusted.');
        }

        $validated = $request->validate([
            'adjust_date' => ['required', 'date', 'after_or_equal:'.$expenseIou->issue_date->toDateString()],
        ]);

        DB::transaction(fn () => $expenseIou->update([
            'adjust_date' => $validated['adjust_date'],
            'status' => AcExpenseIou::STATUS_ADJUSTED,
        ]));

        return back()->with('success', 'Expense IOU adjusted successfully.');
    }

    public function destroy(AcExpenseIou $expenseIou): RedirectResponse
    {
        $this->authorize('ac_expense_iou.delete');

        if ($expenseIou->isReferenced()) {
            return back()->with('error', 'This IOU cannot be deleted because it already has a posted transaction.');
        }

        $expenseIou->delete();

        return back()->with('success', 'Expense IOU deleted successfully.');
    }
}
