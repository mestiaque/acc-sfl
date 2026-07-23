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
                <div class="col-md-4">
                    <label class="form-label mb-1">Fiscal Year</label>
                    <select name="fiscal_year" class="form-control form-control-sm">
                        @for($y = now()->year - 3; $y <= now()->year + 1; $y++)
                        <option value="{{ $y }}" @selected($report['fiscal_year_start'] == $y)>FY {{ $y }}-{{ substr((string) ($y + 1), -2) }} (Jul {{ $y }} - Jun {{ $y + 1 }})</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-1">Branch</label>
                    <select name="branch_id" class="form-control form-control-sm">
                        <option value="">All Branches (Consolidated)</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) $branchId === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-secondary btn-sm w-100">View</button>
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
</style>
@endsection
