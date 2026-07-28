@extends('printMaster2')

@section('title', 'Account Statement')

@section('contents')
@include('acc-sfl::admin.reports.partials.print-loader')
<div class="print-header">
    <div class="company-info">
        <div class="company-name">{{ config('acc-sfl.company.name') }}</div>
        <div class="text-right" style="text-align:end;">
            <div class="company-address">{{ config('acc-sfl.company.address') }}</div>
        </div>
    </div>
    <div class="report-title"><span>Account Statement - {{ $statement['account']->name }}</span></div>
</div>

@include('acc-sfl::admin.reports.partials.statement-report-table', ['statement' => $statement])
@endsection
