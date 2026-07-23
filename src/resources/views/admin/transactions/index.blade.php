@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Transactions') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 p-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fa-solid fa-right-left mr-2 text-info"></i>Transactions</h4>
            <div>
                <a href="{{ route('acc-sfl.transactions.print', request()->query()) }}" target="_blank" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-print mr-1"></i> Print
                </a>
                <a href="{{ route('acc-sfl.transactions.export', request()->query()) }}" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-file-excel mr-1"></i> Export Excel
                </a>
            </div>
        </div>
        <div class="card-body">
            @include('acc-sfl::admin.partials.alerts')

            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label mb-1">Branch</label>
                    <select name="branch_id" class="form-control form-control-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Account</label>
                    <select name="account_id" class="form-control form-control-sm">
                        <option value="">All Accounts</option>
                        @foreach($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) request('account_id') === (string) $account->id)>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Type</label>
                    <select name="transaction_type" class="form-control form-control-sm">
                        <option value="">All Types</option>
                        @foreach($transactionTypes as $type)
                        <option value="{{ $type }}" @selected(request('transaction_type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-1">
                    <button class="btn btn-secondary btn-sm w-100">Filter</button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('acc-sfl.transactions.index') }}" class="btn btn-light btn-sm w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive mt-2">
                <table id="transactionsTable" class="table table-bordered table-sm mb-0" style="width:100%">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Branch</th>
                            <th>Account</th>
                            <th>Payment Method</th>
                            <th>Debit</th>
                            <th>Credit</th>
                            <th>Balance</th>
                            <th>Description</th>
                            <th>Recorded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                            <td>{{ $transaction->transaction_type }}</td>
                            <td>{{ $transaction->branch->name }}</td>
                            <td>{{ $transaction->account->name }}</td>
                            <td>{{ $transaction->paymentMethod->name ?? '-' }}</td>
                            <td class="text-success">{{ $transaction->debit > 0 ? number_format($transaction->debit, 2) : '-' }}</td>
                            <td class="text-danger">{{ $transaction->credit > 0 ? number_format($transaction->credit, 2) : '-' }}</td>
                            <td>{{ number_format($transaction->balance, 2) }}</td>
                            <td>{{ $transaction->description ?: '-' }}</td>
                            <td>{{ $transaction->creator->name ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $transactions->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
