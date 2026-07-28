@extends('printMaster2')

@section('title', 'Transaction Report')

@section('contents')
@include('acc-sfl::admin.reports.partials.print-loader')
<div class="print-header">
    <div class="company-info">
        <div class="company-name">{{ config('acc-sfl.company.name') }}</div>
        <div class="text-right" style="text-align:end;">
            <div class="company-address">{{ config('acc-sfl.company.address') }}</div>
        </div>
    </div>
    <div class="report-title"><span>Transaction Report</span></div>
</div>

@include('acc-sfl::admin.reports.partials.transaction-report-table', ['transactions' => $transactions, 'totals' => $totals])
@endsection
