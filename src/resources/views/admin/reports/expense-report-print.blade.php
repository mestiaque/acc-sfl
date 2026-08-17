@extends('printMaster2')

@section('title', 'Expense Report')

@section('contents')
@include('acc-sfl::admin.reports.partials.print-loader')
<div class="print-header">
    <div class="company-info">
        <div class="company-name">{{ config('acc-sfl.company.name') }}</div>
        <div class="text-right" style="text-align:end;">
            <div class="company-address">{{ config('acc-sfl.company.address') }}</div>
        </div>
    </div>
    <div class="report-title"><span>Expense Report</span></div>
</div>

@include('acc-sfl::admin.reports.partials.expense-report-table', ['rows' => $rows, 'totals' => $totals])
@endsection
