<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\AccSfl\Exports\TransactionExport;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcBranch;
use ME\AccSfl\Models\AcTransaction;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('ac_transaction.list');

        $transactions = $this->filteredQuery($request)->latest('id')->paginate(20)->withQueryString();

        return view('acc-sfl::admin.transactions.index', array_merge(
            compact('transactions'),
            $this->filterOptions(),
        ));
    }

    public function print(Request $request): View
    {
        $this->authorize('ac_transaction.list');

        $transactions = $this->filteredQuery($request)->latest('id')->get();

        return view('acc-sfl::admin.transactions.print', compact('transactions'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_transaction.list');

        $transactions = $this->filteredQuery($request)->latest('id')->get();

        return Excel::download(new TransactionExport($transactions), 'transactions.xlsx');
    }

    private function filteredQuery(Request $request): Builder
    {
        return AcTransaction::query()
            ->with(['branch', 'account', 'paymentMethod', 'creator'])
            ->when($request->filled('account_id'), fn ($q) => $q->where('account_id', $request->integer('account_id')))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('transaction_type'), fn ($q) => $q->where('transaction_type', $request->string('transaction_type')))
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('transaction_date', '>=', $request->date('from_date')))
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('transaction_date', '<=', $request->date('to_date')));
    }

    private function filterOptions(): array
    {
        return [
            'branches' => AcBranch::query()->active()->orderBy('name')->get(),
            'accounts' => AcAccount::query()->active()->visibleToCurrentUser()->orderBy('name')->get(),
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
