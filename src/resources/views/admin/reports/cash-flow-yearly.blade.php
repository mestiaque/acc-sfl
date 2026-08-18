@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Yearly Cash Flow Overview') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 p-4">
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fa-solid fa-calendar-week mr-2 text-info"></i>Yearly Cash Flow Overview</h4>
            <div>
                <a href="{{ route('acc-sfl.reports.cash-flow.yearly.print', request()->query()) }}" target="_blank" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-print mr-1"></i> Print
                </a>
                @can('ac_report.export')
                <a href="{{ route('acc-sfl.reports.cash-flow.yearly.export', request()->query()) }}" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-file-excel mr-1"></i> Export Excel
                </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @include('acc-sfl::admin.partials.alerts')

            <form method="GET" class="row align-items-end">
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">Fiscal Year</label>
                    <select name="fiscal_year" class="form-control form-control-sm">
                        @foreach($fiscalYears as $fy)
                        <option value="{{ $fy->id }}" @selected($fiscalYearId === $fy->id)>{{ $fy->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">Branch</label>
                    <select name="branch_id" class="form-control form-control-sm">
                        <option value="">All Branches (Consolidated)</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) $branchId === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">Account</label>
                    <select name="account_id" class="form-control form-control-sm">
                        <option value="">All Accounts (Consolidated)</option>
                        @foreach($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) $accountId === (string) $account->id)>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">Master Particular</label>
                    <select name="master_particular_id" class="form-control form-control-sm">
                        <option value="">All</option>
                        @foreach($masterParticulars as $master)
                        <option value="{{ $master->id }}" @selected((string) $masterParticularId === (string) $master->id)>{{ $master->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="form-label mb-1">Particular</label>
                    @php
                        $selectedParticularIds = array_map('strval', (array) $particularIds);
                    @endphp
                    <select name="particular_id[]" class="form-control form-control-sm" multiple>
                        @foreach($masterParticulars as $master)
                        <optgroup label="{{ $master->name }}">
                            @foreach($master->particulars as $particular)
                            <option value="{{ $particular->id }}" @selected(in_array((string) $particular->id, $selectedParticularIds, true))>{{ $particular->code }} - {{ $particular->name }}</option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <button class="btn btn-secondary btn-sm w-100">View</button>
                </div>
                <div class="col-md-2 mb-2">
                    <a href="{{ route('acc-sfl.reports.cash-flow.yearly') }}" class="btn btn-light btn-sm w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-2">
            @include('acc-sfl::admin.reports.partials.cash-flow-yearly-table', ['report' => $report])
        </div>
    </div>
</div>

<style>
    .cash-flow-table th, .cash-flow-table td { white-space: nowrap; font-size: 0.8rem; }
    .cash-flow-scroll { max-height: 65vh; overflow-y: auto; }
    .cash-flow-scroll table.cash-flow-table { border-collapse: separate; border-spacing: 0; }
    .cash-flow-scroll thead th { position: sticky; top: 0; z-index: 2; background-color: #343a40; color: #fff; }
    .cash-flow-scroll th:first-child, .cash-flow-scroll td:first-child { position: sticky; left: 0; z-index: 1; }
    .cash-flow-scroll thead th:first-child { z-index: 3; }
    .cash-flow-scroll tbody td:first-child, .cash-flow-scroll tbody > tr > th:first-child { background-color: #fff; }
    .cash-flow-scroll tr.table-info > *:first-child { background-color: #d1ecf1; }
    .cash-flow-scroll tr.table-success > *:first-child { background-color: #c3e6cb; }
    .cash-flow-scroll tr.table-warning > *:first-child { background-color: #ffeeba; }
    .cash-flow-scroll tr.table-secondary > *:first-child { background-color: #d6d8db; }
    .cash-flow-scroll tr.table-danger > *:first-child { background-color: #f5c6cb; }
    .cash-flow-scroll tr.table-dark > *:first-child { background-color: #343a40 !important; color: #fff; }
    .cf-sticky-label { position: sticky; left: 8px; display: inline-block; }
    .float-end { float: right; }
    .cf-amt { display: flex; justify-content: space-between; gap: 6px; }
    .cf-cur { opacity: 0.7; }
</style>
@endsection

@push('js')
@include('acc-sfl::admin.partials.select2-init')
@endpush
