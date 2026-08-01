@extends('printMaster2')

@section('title', 'Transaction Slip - '.$expense->expense_no)

@section('contents')
<div class="slip-wrap">
    <div class="slip-top">
        <div class="slip-barcode">
            <svg id="slipBarcode"></svg>
            <div class="slip-sl">SL: <strong>{{ str_pad($expense->id, 10, '0', STR_PAD_LEFT) }}</strong></div>
        </div>
        <div class="slip-heading">
            <div class="slip-company-name">{{ config('acc-sfl.company.name') }}</div>
            @if(config('acc-sfl.company.subtitle'))
            <div class="slip-company-subtitle">({{ config('acc-sfl.company.subtitle') }})</div>
            @endif
            <div class="slip-company-address">{{ config('acc-sfl.company.address') }}</div>
            @if(config('acc-sfl.company.mobile') || config('acc-sfl.company.email'))
            <div class="slip-company-contact">
                @if(config('acc-sfl.company.mobile')) Mobile: {{ config('acc-sfl.company.mobile') }} @endif
                @if(config('acc-sfl.company.mobile') && config('acc-sfl.company.email')), @endif
                @if(config('acc-sfl.company.email')) {{ config('acc-sfl.company.email') }} @endif
            </div>
            @endif
        </div>
        <div class="slip-date">
            <div>Date: <strong>{{ $expense->expense_date->format('d.m.Y') }}</strong></div>
        </div>
    </div>

    <div class="slip-badge"><span>TRANSACTION SLIP</span></div>
    <div class="slip-subtitle">{{ $expense->company_name ?: $expense->account->name }} Expense</div>

    <div class="slip-meta">
        <div><span class="slip-label">Expense No:</span> {{ $expense->expense_no }}</div>
        <div><span class="slip-label">Branch:</span> {{ $expense->branch->name }}</div>
        <div><span class="slip-label">Account:</span> {{ $expense->account->name }}</div>
        <div><span class="slip-label">Payment Method:</span> {{ $expense->paymentMethod->name }}</div>
    </div>

    <div class="slip-field">
        <span class="slip-label">Paid to ( Name of company ) :</span> {{ $expense->company_name ?: '-' }}
    </div>
    <div class="slip-field">
        <span class="slip-label">( Name of receiver ) :</span>
        {{ $expense->receiver_name ?: '-' }}
        @if($expense->receiver_mobile) ({{ $expense->receiver_mobile }}) @endif
    </div>

    <table class="slip-table">
        <thead>
            <tr>
                <th>Cash Paid To</th>
                <th>Invoice</th>
                <th class="text-right">Qty</th>
                <th>UOM</th>
                <th class="text-right">Rate</th>
                <th class="text-right">Taka</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expense->details as $detail)
            <tr>
                <td>{{ $detail->particular->name ?? $expense->description }}</td>
                <td>{{ $detail->invoice ?: '-' }}</td>
                <td class="text-right">{{ number_format($detail->qty, 2) }}</td>
                <td>{{ $detail->uom ?: '-' }}</td>
                <td class="text-right">{{ number_format($detail->rate, 2) }}</td>
                <td class="text-right">{{ number_format($detail->amount, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td>{{ $expense->description ?: '-' }}</td>
                <td>-</td><td class="text-right">-</td><td>-</td><td class="text-right">-</td>
                <td class="text-right">{{ number_format($expense->total_amount, 2) }}</td>
            </tr>
            @endforelse
            @for($i = 0; $i < max(0, 3 - $expense->details->count()); $i++)
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>
            @endfor
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="text-right">Total</th>
                <th class="text-right">{{ number_format($expense->total_amount, 2) }}</th>
            </tr>
        </tfoot>
    </table>

    @if($expense->description)
    <div class="slip-field slip-description">
        <span class="slip-label">Description:</span> {!! $expense->description !!}
    </div>
    @endif

    <div class="slip-words">
        <span class="slip-label">Taka in word:</span>
        <span class="slip-words-value">{{ $amountInWords }}</span>
    </div>

    <div class="slip-signatures">
        <div class="slip-sign"><div class="slip-sign-line"></div><div class="slip-sign-label">Receiver</div></div>
        <div class="slip-sign"><div class="slip-sign-line"></div><div class="slip-sign-label">Accountant</div></div>
        <div class="slip-sign"><div class="slip-sign-line"></div><div class="slip-sign-label">Approved by</div></div>
    </div>

    <div class="slip-footer">
        Recorded by: {{ $expense->creator->name ?? '-' }}
        @if($expense->attachment) &nbsp;|&nbsp; Attachment: {{ basename($expense->attachment) }} @endif
    </div>
</div>

<style>
    .slip-wrap { padding: 10px 20px; font-family: 'Times New Roman', serif; }
    .slip-top { display: flex; align-items: flex-start; justify-content: space-between; }
    .slip-barcode { text-align: center; }
    .slip-sl { font-size: 12px; margin-top: 2px; }
    .slip-heading { text-align: center; flex: 1; }
    .slip-company-name { font-size: 24px; font-weight: bold; }
    .slip-company-subtitle, .slip-company-address, .slip-company-contact { font-size: 12px; }
    .slip-date { font-size: 13px; white-space: nowrap; }
    .slip-badge { text-align: center; margin: 12px 0 4px; }
    .slip-badge span { background: #000; color: #fff; padding: 4px 16px; font-weight: bold; letter-spacing: 1px; }
    .slip-subtitle { text-align: center; color: #666; font-size: 12px; margin-bottom: 12px; }
    .slip-meta { display: flex; flex-wrap: wrap; gap: 4px 24px; font-size: 12px; margin-bottom: 10px; border-bottom: 1px dashed #999; padding-bottom: 6px; }
    .slip-field { border-bottom: 1px solid #000; padding: 4px 0; margin-bottom: 6px; font-style: italic; }
    .slip-description { font-style: normal; font-size: 12px; }
    .slip-label { font-weight: bold; font-style: normal; }
    .slip-footer { margin-top: 20px; font-size: 11px; color: #555; border-top: 1px solid #ccc; padding-top: 6px; }
    .slip-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .slip-table th, .slip-table td { border: 1px solid #ccc; padding: 6px 8px; font-size: 13px; }
    .slip-table thead th { background: #f5f5f5; text-align: left; }
    .slip-table .text-right { text-align: right; }
    .slip-words { border-bottom: 1px solid #000; padding: 6px 0; margin-top: 16px; display: flex; gap: 8px; }
    .slip-words-value { font-style: italic; }
    .slip-signatures { display: flex; justify-content: space-between; margin-top: 60px; }
    .slip-sign { width: 30%; text-align: center; }
    .slip-sign-line { border-top: 1px solid #000; margin-bottom: 4px; }
    .slip-sign-label { font-size: 12px; color: #333; }
</style>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
    JsBarcode('#slipBarcode', @json($expense->expense_no), {
        format: 'CODE128',
        displayValue: false,
        height: 40,
        width: 1.5,
        margin: 0,
    });
</script>
@endsection
