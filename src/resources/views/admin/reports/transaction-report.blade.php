@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Transaction Report') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 p-4">
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fa-solid fa-right-left mr-2 text-info"></i>Transaction Report</h4>
            <div>
                <a href="{{ route('acc-sfl.reports.transaction.print', request()->query()) }}" target="_blank" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-print mr-1"></i> Print
                </a>
                @can('ac_report.export')
                <a href="{{ route('acc-sfl.reports.transaction.export', request()->query()) }}" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-file-excel mr-1"></i> Export Excel
                </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @include('acc-sfl::admin.partials.alerts')

            <form method="GET" class="row align-items-end">
                <div class="col-md-3 mb-2">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Description">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">Branch</label>
                    <select name="branch_id" class="form-control form-control-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">Account</label>
                    <select name="account_id" class="form-control form-control-sm">
                        <option value="">All Accounts</option>
                        @foreach($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) request('account_id') === (string) $account->id)>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">Payment Method</label>
                    <select name="payment_method_id" class="form-control form-control-sm">
                        <option value="">All Methods</option>
                        @foreach($paymentMethods as $method)
                        <option value="{{ $method->id }}" @selected((string) request('payment_method_id') === (string) $method->id)>{{ $method->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="form-label mb-1">Type</label>
                    <select name="transaction_type" class="form-control form-control-sm">
                        <option value="">All Types</option>
                        @foreach($transactionTypes as $type)
                        <option value="{{ $type }}" @selected(request('transaction_type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 mb-2">
                    <button class="btn btn-secondary btn-sm w-100">Filter</button>
                </div>
                <div class="col-md-2 mb-2">
                    <a href="{{ route('acc-sfl.reports.transaction.index') }}" class="btn btn-light btn-sm w-100">Reset</a>
                </div>
            </form>

            <div class="d-flex flex-wrap gap-3 mb-3">
                <div class="badge badge-secondary p-2" style="font-size: 0.85rem;">Records: {{ $totals['count'] }}</div>
                <div class="badge badge-success p-2" style="font-size: 0.85rem;">Total Debit: BDT {{ number_format($totals['debit'], 2) }}</div>
                <div class="badge badge-danger p-2" style="font-size: 0.85rem;">Total Credit: BDT {{ number_format($totals['credit'], 2) }}</div>
                <div class="badge badge-primary p-2" style="font-size: 0.85rem;">Net: BDT {{ number_format($totals['net'], 2) }}</div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
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
                        @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                            <td>{{ $transaction->transaction_type }}</td>
                            <td>{{ $transaction->branch->name ?? '-' }}</td>
                            <td>{{ $transaction->account->name ?? '-' }}</td>
                            <td>{{ $transaction->paymentMethod->name ?? '-' }}</td>
                            <td class="text-success">{{ $transaction->debit > 0 ? number_format($transaction->debit, 2) : '-' }}</td>
                            <td class="text-danger">{{ $transaction->credit > 0 ? number_format($transaction->credit, 2) : '-' }}</td>
                            <td>{{ number_format($transaction->balance, 2) }}</td>
                            <td>{{ $transaction->description ?: '-' }}</td>
                            <td>{{ $transaction->creator->name ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center">No data available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $transactions->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection

@push('js')
@include('acc-sfl::admin.partials.select2-init')
@endpush
