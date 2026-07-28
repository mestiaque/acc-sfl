@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Expense IOU Report') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 p-4">
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fa-solid fa-file-invoice-dollar mr-2 text-warning"></i>Expense IOU Report</h4>
            <div>
                <a href="{{ route('acc-sfl.reports.expense-iou.print', request()->query()) }}" target="_blank" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-print mr-1"></i> Print
                </a>
                @can('ac_report.export')
                <a href="{{ route('acc-sfl.reports.expense-iou.export', request()->query()) }}" class="btn btn-light btn-sm">
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
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="IOU no. / receiver">
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
                    <label class="form-label mb-1">Employee</label>
                    <select name="employee_id" class="form-control form-control-sm">
                        <option value="">All Employees</option>
                        @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((string) request('employee_id') === (string) $employee->id)>{{ $employee->employee_id }} - {{ $employee->name }}{{ $employee->department ? ' ('.$employee->department->name.')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="form-label mb-1">Payment Method</label>
                    <select name="payment_method_id" class="form-control form-control-sm">
                        <option value="">All Methods</option>
                        @foreach($paymentMethods as $method)
                        <option value="{{ $method->id }}" @selected((string) request('payment_method_id') === (string) $method->id)>{{ $method->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">Status</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All (Pending &amp; Adjusted)</option>
                        <option value="Pending" @selected(request('status') === 'Pending')>Pending</option>
                        <option value="Adjusted" @selected(request('status') === 'Adjusted')>Adjusted (Complete)</option>
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
                    <a href="{{ route('acc-sfl.reports.expense-iou.index') }}" class="btn btn-light btn-sm w-100">Reset</a>
                </div>
            </form>

            <div class="d-flex flex-wrap gap-3 mb-3">
                <div class="badge badge-secondary p-2" style="font-size: 0.85rem;">Records: {{ $totals['count'] }}</div>
                <div class="badge badge-warning p-2" style="font-size: 0.85rem;">Pending: BDT {{ number_format($totals['pending_amount'], 2) }}</div>
                <div class="badge badge-success p-2" style="font-size: 0.85rem;">Adjusted (Complete): BDT {{ number_format($totals['adjusted_amount'], 2) }}</div>
                <div class="badge badge-primary p-2" style="font-size: 0.85rem;">Total: BDT {{ number_format($totals['amount'], 2) }}</div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead>
                        <tr>
                            <th>IOU No.</th>
                            <th>Employee</th>
                            <th>Branch</th>
                            <th>Account</th>
                            <th>Issue Date</th>
                            <th>Adjust Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenseIous as $iou)
                        <tr>
                            <td>{{ $iou->iou_no }}</td>
                            <td>{{ $iou->employee->name ?? '-' }}</td>
                            <td>{{ $iou->branch->name ?? '-' }}</td>
                            <td>{{ $iou->account->name ?? '-' }}</td>
                            <td>{{ $iou->issue_date->format('d M Y') }}</td>
                            <td>{{ $iou->adjust_date ? $iou->adjust_date->format('d M Y') : '-' }}</td>
                            <td class="text-right">{{ number_format($iou->amount, 2) }}</td>
                            <td>
                                <span class="badge {{ $iou->status === 'Adjusted' ? 'badge-success' : 'badge-warning' }}">{{ $iou->status }}</span>
                            </td>
                            <td>{{ $iou->description ?: '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center">No data available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $expenseIous->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection

@push('js')
@include('acc-sfl::admin.partials.select2-init')
@endpush
