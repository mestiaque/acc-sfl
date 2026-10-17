{{-- Day end report body (page, print and mail — inline styles so mail clients keep them). props: report (DayEndReport::build()) --}}
@php
    $tk = fn ($v) => number_format((float) $v, 2);
    $th = 'border:1px solid #999;padding:4px 6px;background:#eef2f7;font-size:12px;text-align:left;';
    $td = 'border:1px solid #bbb;padding:4px 6px;font-size:12px;';
    $num = $td . 'text-align:right;white-space:nowrap;';
    $tot = $num . 'font-weight:bold;background:#f7f7f7;';
    $badge = ['approved' => '#1e7e34', 'pending' => '#b8860b', 'rejected' => '#c82333'];
@endphp
<h4 style="margin:10px 0 6px;font-size:14px;">Cash in Hand — {{ $report['date']->format('d M Y') }}</h4>
<table style="border-collapse:collapse;width:100%;margin-bottom:12px;">
    <thead><tr>
        <th style="{{ $th }}">Account</th><th style="{{ $th }}text-align:right;">Opening</th><th style="{{ $th }}text-align:right;">Received</th>
        <th style="{{ $th }}text-align:right;">Expenses</th><th style="{{ $th }}text-align:right;">IOU Issued</th><th style="{{ $th }}text-align:right;">IOU Cleared</th>
        <th style="{{ $th }}text-align:right;">Total In</th><th style="{{ $th }}text-align:right;">Total Out</th><th style="{{ $th }}text-align:right;">Cash in Hand</th>
    </tr></thead>
    <tbody>
        @forelse($report['accounts'] as $r)
            <tr>
                <td style="{{ $td }}">{{ $r['account'] }}@if($r['branch'])<br><small style="color:#777;">{{ $r['branch'] }}</small>@endif</td>
                <td style="{{ $num }}">{{ $tk($r['opening']) }}</td><td style="{{ $num }}">{{ $tk($r['received']) }}</td><td style="{{ $num }}">{{ $tk($r['expenses']) }}</td>
                <td style="{{ $num }}">{{ $tk($r['iou_issued']) }}</td><td style="{{ $num }}">{{ $tk($r['iou_returned']) }}</td>
                <td style="{{ $num }}">{{ $tk($r['in']) }}</td><td style="{{ $num }}">{{ $tk($r['out']) }}</td><td style="{{ $num }}font-weight:bold;">{{ $tk($r['closing']) }}</td>
            </tr>
        @empty
            <tr><td colspan="9" style="{{ $td }}text-align:center;color:#777;">No account balance or movement.</td></tr>
        @endforelse
    </tbody>
    <tfoot><tr>
        <td style="{{ $tot }}text-align:left;">Total</td>
        @foreach(['opening', 'received', 'expenses', 'iou_issued', 'iou_returned', 'in', 'out', 'closing'] as $k)<td style="{{ $tot }}">{{ $tk($report['totals'][$k]) }}</td>@endforeach
    </tr></tfoot>
</table>
<p style="font-size:11px;color:#666;margin:0 0 12px;">From the posted ledger: only approved receives / expenses / IOUs move cash. IOU Cleared = advance cleared back at adjustment (its expenses post on the same day).</p>

<h4 style="margin:10px 0 6px;font-size:14px;">Expenses of the day</h4>
<table style="border-collapse:collapse;width:100%;margin-bottom:12px;">
    <thead><tr><th style="{{ $th }}">Memo</th><th style="{{ $th }}">Account</th><th style="{{ $th }}">Particulars</th><th style="{{ $th }}">Paid to</th><th style="{{ $th }}">IOU</th><th style="{{ $th }}">Status</th><th style="{{ $th }}text-align:right;">Amount (Tk)</th></tr></thead>
    <tbody>
        @forelse($report['expenses'] as $e)
            <tr>
                <td style="{{ $td }}">{{ $e['no'] }}</td><td style="{{ $td }}">{{ $e['account'] }}</td><td style="{{ $td }}">{{ $e['particulars'] }}</td>
                <td style="{{ $td }}">{{ $e['paid_to'] ?: '-' }}</td><td style="{{ $td }}">{{ $e['iou'] ?: '-' }}</td>
                <td style="{{ $td }}color:{{ $badge[$e['status']] ?? '#333' }};font-weight:bold;">{{ ucfirst($e['status']) }}</td>
                <td style="{{ $num }}">{{ $tk($e['amount']) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" style="{{ $td }}text-align:center;color:#777;">No expense on this day.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr><td colspan="6" style="{{ $tot }}">Approved</td><td style="{{ $tot }}">{{ $tk($report['expense_totals']['approved']) }}</td></tr>
        @if($report['expense_totals']['pending'])<tr><td colspan="6" style="{{ $tot }}">Waiting approval</td><td style="{{ $tot }}">{{ $tk($report['expense_totals']['pending']) }}</td></tr>@endif
        @if($report['expense_totals']['rejected'])<tr><td colspan="6" style="{{ $tot }}">Rejected</td><td style="{{ $tot }}">{{ $tk($report['expense_totals']['rejected']) }}</td></tr>@endif
    </tfoot>
</table>

@if($report['ious']->isNotEmpty())
    <h4 style="margin:10px 0 6px;font-size:14px;">IOUs issued</h4>
    <table style="border-collapse:collapse;width:100%;margin-bottom:12px;">
        <thead><tr><th style="{{ $th }}">IOU</th><th style="{{ $th }}">Account</th><th style="{{ $th }}">Employee</th><th style="{{ $th }}">Approval</th><th style="{{ $th }}text-align:right;">Amount (Tk)</th></tr></thead>
        <tbody>
            @foreach($report['ious'] as $i)
                <tr><td style="{{ $td }}">{{ $i['no'] }}</td><td style="{{ $td }}">{{ $i['account'] }}</td><td style="{{ $td }}">{{ $i['employee'] ?: '-' }}</td>
                    <td style="{{ $td }}color:{{ $badge[$i['approval']] ?? '#333' }};font-weight:bold;">{{ ucfirst($i['approval']) }}</td><td style="{{ $num }}">{{ $tk($i['amount']) }}</td></tr>
            @endforeach
        </tbody>
    </table>
@endif

<p style="font-size:12px;margin:6px 0 0;"><strong>Waiting for approval now:</strong> {{ $report['waiting']['expenses'] }} expense(s), {{ $report['waiting']['ious'] }} IOU(s), {{ $report['waiting']['balance_receives'] }} cash receive(s).</p>
