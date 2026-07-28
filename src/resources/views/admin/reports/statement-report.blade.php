@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Statement Report') }}</title>
@endsection

@php
    $printQuery = array_merge(request()->query(), ['account_id' => $accountId]);
@endphp

@section('contents')
<div class="flex-grow-1 p-4">
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fa-solid fa-file-lines mr-2 text-primary"></i>Account Statement</h4>
            <div>
                <a href="{{ route('acc-sfl.reports.statement.print', $printQuery) }}" target="_blank" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-print mr-1"></i> Print
                </a>
                @can('ac_report.export')
                <a href="{{ route('acc-sfl.reports.statement.export', $printQuery) }}" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-file-excel mr-1"></i> Export Excel
                </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @include('acc-sfl::admin.partials.alerts')

            <form method="GET" class="row align-items-end">
                <div class="col-md-4 mb-2">
                    <label class="form-label mb-1">Account <span class="text-danger">*</span></label>
                    <select name="account_id" class="form-control form-control-sm" required>
                        @foreach($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) $accountId === (string) $account->id)>{{ $account->name }} ({{ $account->branch->name ?? '-' }})</option>
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
                    <input type="date" name="from_date" value="{{ $statement['from_date']->toDateString() ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ $statement['to_date']->toDateString() ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-1 mb-2">
                    <button class="btn btn-secondary btn-sm w-100">View</button>
                </div>
            </form>

            @if($statement)
            <div class="d-flex flex-wrap gap-3 mb-3 mt-2">
                <div class="badge badge-secondary p-2" style="font-size: 0.85rem;">Opening Balance: BDT {{ number_format($statement['opening_balance'], 2) }}</div>
                <div class="badge badge-success p-2" style="font-size: 0.85rem;">Total Debit: BDT {{ number_format($statement['total_debit'], 2) }}</div>
                <div class="badge badge-danger p-2" style="font-size: 0.85rem;">Total Credit: BDT {{ number_format($statement['total_credit'], 2) }}</div>
                <div class="badge badge-primary p-2" style="font-size: 0.85rem;">Closing Balance: BDT {{ number_format($statement['closing_balance'], 2) }}</div>
            </div>

            <div class="table-responsive cash-flow-scroll">
                <table class="table table-bordered table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Payment Method</th>
                            <th>Description</th>
                            <th>Debit</th>
                            <th>Credit</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-secondary font-weight-bold">
                            <td colspan="6" class="text-right">OPENING BALANCE</td>
                            <td class="text-right">{{ number_format($statement['opening_balance'], 2) }}</td>
                        </tr>
                        @forelse($statement['transactions'] as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                            <td>{{ $transaction->transaction_type }}</td>
                            <td>{{ $transaction->paymentMethod->name ?? '-' }}</td>
                            <td>{{ $transaction->description ?: '-' }}</td>
                            <td class="text-success">{{ $transaction->debit > 0 ? number_format($transaction->debit, 2) : '-' }}</td>
                            <td class="text-danger">{{ $transaction->credit > 0 ? number_format($transaction->credit, 2) : '-' }}</td>
                            <td>{{ number_format($transaction->balance, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center">No transactions in this period.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-dark text-white font-weight-bold">
                            <td colspan="6" class="text-right">CLOSING BALANCE</td>
                            <td class="text-right">{{ number_format($statement['closing_balance'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @else
            <p class="text-muted mt-3">No accounts available to show a statement for.</p>
            @endif
        </div>
    </div>
</div>

<style>
    .cash-flow-scroll { max-height: 65vh; overflow-y: auto; }
    .cash-flow-scroll table.table-bordered { border-collapse: separate; border-spacing: 0; }
    .cash-flow-scroll thead th { position: sticky; top: 0; z-index: 2; background-color: #343a40; color: #fff; }
</style>
@endsection

@push('js')
@include('acc-sfl::admin.partials.select2-init')
@endpush
