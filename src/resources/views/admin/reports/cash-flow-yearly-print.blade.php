@extends('printMaster2')

@section('title')Cash Flow FY{{ $report['fiscal_year_start'] }}@endsection

@push('css')
<style>
    table.cash-flow-table { width: 100%; border-collapse: collapse; }
    table.cash-flow-table th, table.cash-flow-table td { border: 1px solid #999; padding: 3px 6px; font-size: 10px; white-space: nowrap; }
    table.cash-flow-table th { background: #f0f0f0; }
    .bg-success { background: #28a745 !important; color: #fff !important; }
    .bg-danger { background: #dc3545 !important; color: #fff !important; }
    .bg-warning { background: #ffc107 !important; }
    .bg-secondary { background: #6c757d !important; color: #fff !important; }
    .bg-dark { background: #343a40 !important; color: #fff !important; }
    .table-info { background: #d1ecf1; }
    .table-success { background: #c3e6cb; }
    .table-warning { background: #ffeeba; }
    .table-secondary { background: #d6d8db; }
    .text-white { color: #fff !important; }
    .pl-4 { padding-left: 18px !important; }
    .font-weight-bold { font-weight: bold; }
    .signoff { margin-top: 24px; width: 100%; border-collapse: collapse; }
    .signoff td { border: 1px solid #999; padding: 6px 10px; font-size: 11px; }
</style>
@endpush

@section('contents')
<div class="print-header">
    <div class="company-info">
        <div class="company-name">{{ config('acc-sfl.company.name') }}</div>
        <div class="text-right" style="text-align:end;">
            <div class="company-address">{{ config('acc-sfl.company.address') }}</div>
        </div>
    </div>
    <div class="report-title"><span>Yearly Cash Flow Overview - {{ $report['label'] }}</span></div>
</div>

@include('acc-sfl::admin.reports.partials.cash-flow-yearly-table', ['report' => $report])

<table class="signoff">
    <tr>
        <td style="width:20%"><strong>Completed By</strong><br><br>____________________</td>
        <td style="width:20%"><strong>Verified By</strong><br><br>____________________</td>
        <td style="width:20%"><strong>Approved By</strong><br><br>____________________</td>
        <td style="width:20%"><strong>Dates Represented</strong><br>{{ $report['label'] }}</td>
        <td style="width:20%"><strong>Date of Last Update</strong><br>{{ now()->format('d M Y') }}</td>
    </tr>
</table>
@endsection
