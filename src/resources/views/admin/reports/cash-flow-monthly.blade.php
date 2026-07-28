@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Monthly Cash Flow') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 p-4">
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fa-solid fa-calendar-days mr-2 text-info"></i>Monthly Cash Flow</h4>
            <div>
                <a href="{{ route('acc-sfl.reports.cash-flow.monthly.print', request()->query()) }}" target="_blank" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-print mr-1"></i> Print
                </a>
                @can('ac_report.export')
                <a href="{{ route('acc-sfl.reports.cash-flow.monthly.export', request()->query()) }}" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-file-excel mr-1"></i> Export Excel
                </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @include('acc-sfl::admin.partials.alerts')

            <form method="GET" class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">Year</label>
                    <select name="year" class="form-control form-control-sm">
                        @for($y = now()->year - 3; $y <= now()->year + 1; $y++)
                        <option value="{{ $y }}" @selected($report['year'] == $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Month</label>
                    <select name="month" class="form-control form-control-sm">
                        @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" @selected($report['month'] == $m)>{{ \Illuminate\Support\Carbon::create(2000, $m, 1)->format('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Branch</label>
                    <select name="branch_id" class="form-control form-control-sm">
                        <option value="">All Branches (Consolidated)</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) $branchId === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-secondary btn-sm w-100">View</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-2">
            @include('acc-sfl::admin.reports.partials.cash-flow-monthly-table', ['report' => $report])
        </div>
    </div>
</div>

<style>
    .cash-flow-table th, .cash-flow-table td { white-space: nowrap; font-size: 0.8rem; }
    .cash-flow-scroll { max-height: 65vh; overflow-y: auto; }
    .cash-flow-scroll table.cash-flow-table { border-collapse: separate; border-spacing: 0; }
    .cash-flow-scroll thead th { position: sticky; top: 0; z-index: 2; background-color: #343a40; color: #fff; }
    .float-end { float: right; }
    .cf-amt { display: flex; justify-content: space-between; gap: 6px; }
    .cf-cur { opacity: 0.7; }
</style>
@endsection
