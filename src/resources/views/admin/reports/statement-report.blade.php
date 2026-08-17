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
                <div class="badge badge-secondary p-2 mr-2" style="font-size: 0.85rem;">Opening Balance: BDT {{ number_format($statement['opening_balance'], 2) }}</div>
                <div class="badge badge-success p-2 mr-2" style="font-size: 0.85rem;">Total Receive: BDT {{ number_format($statement['total_receive'], 2) }}</div>
                <div class="badge badge-danger p-2 mr-2" style="font-size: 0.85rem;">Total Expense: BDT {{ number_format($statement['total_expense'], 2) }}</div>
                <div class="badge badge-primary p-2 mr-2" style="font-size: 0.85rem;">Closing Balance: BDT {{ number_format($statement['closing_balance'], 2) }}</div>
            </div>

            <div class="table-responsive cash-flow-scroll">
                <table class="table table-bordered table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Date</th>
                            <th>Particular</th>
                            <th>Description</th>
                            <th>A/C Code</th>
                            <th>Invoice / Challan No.</th>
                            <th>Receiver</th>
                            <th>Qty</th>
                            <th>Unit of Measure</th>
                            <th>Rate</th>
                            <th>Expense</th>
                            <th>Receive</th>
                            <th>Balance</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-secondary font-weight-bold">
                            <td colspan="12" class="text-right">OPENING BALANCE</td>
                            <td class="text-right">{{ number_format($statement['opening_balance'], 2) }}</td>
                            <td></td>
                        </tr>
                        @forelse($statement['rows'] as $row)
                        <tr>
                            <td>{{ $row['date'] ? strtoupper($row['date']->format('F')) : '-' }}</td>
                            <td>{{ $row['date']?->format('d-m-y') ?? '-' }}</td>
                            <td>{{ $row['particular'] ?: '-' }}</td>
                            <td>{{ $row['description'] ?: '-' }}</td>
                            <td>{{ $row['ac_code'] ?: '-' }}</td>
                            <td>{{ $row['invoice'] ?: '-' }}</td>
                            <td>{{ $row['receiver'] ?: '-' }}</td>
                            <td class="text-right">{{ $row['qty'] !== null ? number_format($row['qty'], 2) : '-' }}</td>
                            <td>{{ $row['uom'] ?: '-' }}</td>
                            <td class="text-right">{{ $row['rate'] !== null ? number_format($row['rate'], 2) : '-' }}</td>
                            <td class="text-danger text-right">{{ $row['expense'] !== null ? number_format($row['expense'], 2) : '-' }}</td>
                            <td class="text-success text-right">{{ $row['receive'] !== null ? number_format($row['receive'], 2) : '-' }}</td>
                            <td class="text-right">{{ number_format($row['balance'], 2) }}</td>
                            <td>{{ $row['remarks'] ?: '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="14" class="text-center">No transactions in this period.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-dark text-white font-weight-bold">
                            <td colspan="12" class="text-right">CLOSING BALANCE</td>
                            <td class="text-right">{{ number_format($statement['closing_balance'], 2) }}</td>
                            <td></td>
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
