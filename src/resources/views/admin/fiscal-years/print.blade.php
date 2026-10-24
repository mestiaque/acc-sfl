@extends('printMaster2')

@section('title', 'Fiscal Years')

@section('contents')
<div class="print-header">
    <div class="company-info">
        <div style="display:flex;align-items:center;gap:12px;">
            @if(report_logo_url())<img src="{{ report_logo_url() }}" alt="Logo" class="company-logo">@endif
            <div class="company-name">{{ config('acc-sfl.company.name') }}</div>
        </div>
        <div class="text-right" style="text-align:end;">
            <div class="company-address">{{ config('acc-sfl.company.address') }}</div>
        </div>
    </div>
    <div class="report-title"><span>Fiscal Years List</span></div>
</div>

@include('acc-sfl::admin.fiscal-years.partials.table', ['fiscalYears' => $fiscalYears])
@endsection
