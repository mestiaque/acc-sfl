@extends('printMaster2')

@section('title', 'Expenses')

@section('contents')
<div class="print-header">
    <div class="company-info">
        <div class="company-name">{{ config('acc-sfl.company.name') }}</div>
        <div class="text-right" style="text-align:end;">
            <div class="company-address">{{ config('acc-sfl.company.address') }}</div>
        </div>
    </div>
    <div class="report-title"><span>Expenses List</span></div>
</div>

@include('acc-sfl::admin.expenses.partials.table', ['expenses' => $expenses])
@endsection
