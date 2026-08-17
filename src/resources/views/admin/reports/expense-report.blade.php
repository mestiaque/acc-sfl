@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Expense Report') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 p-4">
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fa-solid fa-money-bill-wave mr-2 text-warning"></i>Expense Report</h4>
            <div>
                <a href="{{ route('acc-sfl.reports.expense.print', request()->query()) }}" target="_blank" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-print mr-1"></i> Print
                </a>
                @can('ac_report.export')
                <a href="{{ route('acc-sfl.reports.expense.export', request()->query()) }}" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-file-excel mr-1"></i> Export Excel
                </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @include('acc-sfl::admin.partials.alerts')

            <form method="GET" class="row align-items-end">
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Expense no. / company / receiver">
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
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">Master Particular</label>
                    <select name="master_particular_id" class="form-control form-control-sm">
                        <option value="">All</option>
                        @foreach($masterParticulars as $master)
                        <option value="{{ $master->id }}" @selected((string) request('master_particular_id') === (string) $master->id)>{{ $master->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
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
                    <label class="form-label mb-1">Employee</label>
                    <select name="employee_id" class="form-control form-control-sm">
                        <option value="">All Employees</option>
                        @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((string) request('employee_id') === (string) $employee->id)>{{ $employee->employee_id }} - {{ $employee->name }}</option>
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
                    <label class="form-label mb-1">Status</label>
                    @php($currentStatus = request('status', 'approved'))
                    <select name="status" class="form-control form-control-sm">
                        <option value="approved" @selected($currentStatus === 'approved')>Approved</option>
                        <option value="pending" @selected($currentStatus === 'pending')>Pending</option>
                        <option value="rejected" @selected($currentStatus === 'rejected')>Rejected</option>
                        <option value="all" @selected($currentStatus === 'all')>All</option>
                    </select>
                </div>
                {{-- <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">Min Amount</label>
                    <input type="number" step="0.01" name="min_amount" value="{{ request('min_amount') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">Max Amount</label>
                    <input type="number" step="0.01" name="max_amount" value="{{ request('max_amount') }}" class="form-control form-control-sm">
                </div> --}}
                <div class="col-md-2 mb-2">
                    <button class="btn btn-secondary btn-sm w-100">Filter</button>
                </div>
                <div class="col-md-2 mb-2">
                    <a href="{{ route('acc-sfl.reports.expense.index') }}" class="btn btn-light btn-sm w-100">Reset</a>
                </div>
                <div class="col-md-2 mb-2 offset-md-2">
                    <div class="badge badge-primary p-2" style="font-size: 0.85rem;">Total Expense: BDT {{ number_format($totals['amount'], 2) }}</div>
                </div>
            </form>

            <div class="table-responsive">
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
                            <th>Balance</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
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
                            <td class="text-right">{{ number_format($row['expense'], 2) }}</td>
                            <td class="text-right">{{ $row['balance'] !== null ? number_format($row['balance'], 2) : '-' }}</td>
                            <td>{{ $row['remarks'] ?: '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="13" class="text-center">No data available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $expenses->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection

@push('js')
@include('acc-sfl::admin.partials.select2-init')
@endpush
