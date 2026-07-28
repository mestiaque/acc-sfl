@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Balance Receive Report') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 p-4">
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fa-solid fa-hand-holding-dollar mr-2 text-success"></i>Balance Receive Report</h4>
            <div>
                <a href="{{ route('acc-sfl.reports.balance-receive.print', request()->query()) }}" target="_blank" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-print mr-1"></i> Print
                </a>
                @can('ac_report.export')
                <a href="{{ route('acc-sfl.reports.balance-receive.export', request()->query()) }}" class="btn btn-light btn-sm">
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
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Receive no. / description">
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
                    <label class="form-label mb-1">Master Particular</label>
                    <select name="master_particular_id" class="form-control form-control-sm">
                        <option value="">All</option>
                        @foreach($masterParticulars as $master)
                        <option value="{{ $master->id }}" @selected((string) request('master_particular_id') === (string) $master->id)>{{ $master->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="form-label mb-1">Particular</label>
                    <select name="particular_id" class="form-control form-control-sm">
                        <option value="">All Particulars</option>
                        @foreach($masterParticulars as $master)
                        <optgroup label="{{ $master->name }}">
                            @foreach($master->particulars as $particular)
                            <option value="{{ $particular->id }}" @selected((string) request('particular_id') === (string) $particular->id)>{{ $particular->code }} - {{ $particular->name }}</option>
                            @endforeach
                        </optgroup>
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
                    <label class="form-label mb-1">Min Amount</label>
                    <input type="number" step="0.01" name="min_amount" value="{{ request('min_amount') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">Max Amount</label>
                    <input type="number" step="0.01" name="max_amount" value="{{ request('max_amount') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 mb-2">
                    <button class="btn btn-secondary btn-sm w-100">Filter</button>
                </div>
                <div class="col-md-2 mb-2">
                    <a href="{{ route('acc-sfl.reports.balance-receive.index') }}" class="btn btn-light btn-sm w-100">Reset</a>
                </div>
            </form>

            <div class="d-flex gap-3 mb-3">
                <div class="badge badge-success p-2" style="font-size: 0.85rem;">Records: {{ $totals['count'] }}</div>
                <div class="badge badge-primary p-2" style="font-size: 0.85rem;">Total Amount: BDT {{ number_format($totals['amount'], 2) }}</div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Receive No.</th>
                            <th>Date</th>
                            <th>Branch</th>
                            <th>Account</th>
                            <th>Particular</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Recorded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($receives as $receive)
                        <tr>
                            <td>{{ $receive->receive_no }}</td>
                            <td>{{ $receive->receive_date->format('d M Y') }}</td>
                            <td>{{ $receive->branch->name ?? '-' }}</td>
                            <td>{{ $receive->account->name ?? '-' }}</td>
                            <td>{{ $receive->particular ? $receive->particular->code.' - '.$receive->particular->name : '-' }}</td>
                            <td class="text-right">{{ number_format($receive->amount, 2) }}</td>
                            <td>{{ $receive->description ?: '-' }}</td>
                            <td>{{ $receive->creator->name ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center">No data available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $receives->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection

@push('js')
@include('acc-sfl::admin.partials.select2-init')
@endpush
