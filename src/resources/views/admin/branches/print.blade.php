@extends('printMaster2')

@section('title', 'Branches')

@section('contents')
<div class="print-header">
    <div class="company-info">
        <div class="company-name">{{ config('acc-sfl.company.name') }}</div>
        <div class="text-right" style="text-align:end;">
            <div class="company-address">{{ config('acc-sfl.company.address') }}</div>
        </div>
    </div>
    <div class="report-title"><span>Branches List</span></div>
</div>

@include('acc-sfl::admin.branches.partials.table', ['branches' => $branches])
@endsection
