{{-- Day end report body (page, print and mail — inline styles so mail clients keep them). props: report (DayEndReport::build()) --}}
@php
    $tk = fn ($v) => number_format((float) $v, 2);
    $th = 'border:1px solid #999;padding:4px 6px;background:#eef2f7;font-size:12px;text-align:left;';
    $thp = 'border:1px solid #999;padding:4px 6px;background:#fff4d6;font-size:12px;text-align:right;';
    $td = 'border:1px solid #bbb;padding:4px 6px;font-size:12px;';
    $num = $td . 'text-align:right;white-space:nowrap;';
    $pnum = $num . 'background:#fffbea;';
    $tot = $num . 'font-weight:bold;background:#f7f7f7;';
    $badge = ['approved' => '#1e7e34', 'pending' => '#b8860b', 'rejected' => '#c82333'];
    $pt = $report['pending_totals'];
@endphp
<h4 style="margin:10px 0 6px;font-size:14px;">Cash in Hand — {{ $report['date']->format('d M Y') }}</h4>
<table style="border-collapse:collapse;width:100%;margin-bottom:4px;">
    <thead><tr>
        <th style="{{ $th }}">Account</th><th style="{{ $th }}text-align:right;">Opening</th><th style="{{ $th }}text-align:right;">Received</th>
        <th style="{{ $th }}text-align:right;">Expenses</th><th style="{{ $th }}text-align:right;">IOU Issued</th><th style="{{ $th }}text-align:right;">IOU Cleared</th>
        <th style="{{ $th }}text-align:right;">Total In</th><th style="{{ $th }}text-align:right;">Total Out</th><th style="{{ $th }}text-align:right;">Cash in Hand</th>
        <th style="{{ $thp }}">Pending In</th><th style="{{ $thp }}">Pending Out</th><th style="{{ $thp }}">After Approval</th>
    </tr></thead>
    <tbody>
        @forelse($report['accounts'] as $r)
            <tr>
                <td style="{{ $td }}">{{ $r['account'] }}@if($r['branch'])<br><small style="color:#777;">{{ $r['branch'] }}</small>@endif</td>
                <td style="{{ $num }}">{{ $tk($r['opening']) }}</td><td style="{{ $num }}">{{ $tk($r['received']) }}</td><td style="{{ $num }}">{{ $tk($r['expenses']) }}</td>
                <td style="{{ $num }}">{{ $tk($r['iou_issued']) }}</td><td style="{{ $num }}">{{ $tk($r['iou_returned']) }}</td>
                <td style="{{ $num }}">{{ $tk($r['in']) }}</td><td style="{{ $num }}">{{ $tk($r['out']) }}</td><td style="{{ $num }}font-weight:bold;">{{ $tk($r['closing']) }}</td>
                <td style="{{ $pnum }}">{{ $tk($r['pending_in']) }}</td><td style="{{ $pnum }}">{{ $tk($r['pending_out']) }}</td><td style="{{ $pnum }}font-weight:bold;">{{ $tk($r['after_pending']) }}</td>
            </tr>
        @empty
            <tr><td colspan="12" style="{{ $td }}text-align:center;color:#777;">No account balance or movement.</td></tr>
        @endforelse
    </tbody>
    <tfoot><tr>
        <td style="{{ $tot }}text-align:left;">Total</td>
        @foreach(['opening', 'received', 'expenses', 'iou_issued', 'iou_returned', 'in', 'out', 'closing', 'pending_in', 'pending_out', 'after_pending'] as $k)<td style="{{ $tot }}">{{ $tk($report['totals'][$k]) }}</td>@endforeach
    </tr></tfoot>
</table>
<p style="font-size:11px;color:#666;margin:0 0 12px;">
    <strong>Cash in Hand</strong> = Opening + Total In − Total Out, from the posted ledger (approved entries only).
    <strong>Pending In / Out</strong> = cash receives / expenses + IOUs dated up to this day and still waiting for approval — not in Cash in Hand yet.
    <strong>After Approval</strong> = Cash in Hand + Pending In − Pending Out.
    IOU Cleared = advance cleared back at IOU adjustment (its expenses post on the same day).
</p>

<h4 style="margin:10px 0 6px;font-size:14px;">Expenses of the day</h4>
<table style="border-collapse:collapse;width:100%;margin-bottom:4px;">
    <thead><tr><th style="{{ $th }}">Memo</th><th style="{{ $th }}">Account</th><th style="{{ $th }}">Particulars</th><th style="{{ $th }}">Paid to</th><th style="{{ $th }}">IOU</th><th style="{{ $th }}">Status</th><th style="{{ $th }}text-align:right;">Amount (Tk)</th></tr></thead>
    <tbody>
        @forelse($report['expenses'] as $e)
            <tr>
                <td style="{{ $td }}white-space:nowrap;">{{ $e['no'] }}</td><td style="{{ $td }}">{{ $e['account'] }}</td><td style="{{ $td }}">{{ $e['particulars'] }}</td>
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
        @if($report['expense_totals']['pending'])<tr><td colspan="6" style="{{ $tot }}color:#b8860b;">Waiting approval</td><td style="{{ $tot }}color:#b8860b;">{{ $tk($report['expense_totals']['pending']) }}</td></tr>@endif
        @if($report['expense_totals']['rejected'])<tr><td colspan="6" style="{{ $tot }}color:#c82333;">Rejected</td><td style="{{ $tot }}color:#c82333;">{{ $tk($report['expense_totals']['rejected']) }}</td></tr>@endif
    </tfoot>
</table>
@if($report['expense_totals']['via_iou'])
    <p style="font-size:11px;color:#666;margin:0 0 12px;">Tk {{ $tk($report['expense_totals']['via_iou']) }} of the approved amount was spent from an IOU advance — that cash left the account when the IOU was issued, so it is not in today's Expenses column.</p>
@endif

@if($report['ious']->isNotEmpty())
    <h4 style="margin:12px 0 6px;font-size:14px;">IOUs issued</h4>
    <table style="border-collapse:collapse;width:100%;margin-bottom:12px;">
        <thead><tr><th style="{{ $th }}">IOU</th><th style="{{ $th }}">Account</th><th style="{{ $th }}">Employee</th><th style="{{ $th }}">Approval</th><th style="{{ $th }}text-align:right;">Amount (Tk)</th></tr></thead>
        <tbody>
            @foreach($report['ious'] as $i)
                <tr><td style="{{ $td }}white-space:nowrap;">{{ $i['no'] }}</td><td style="{{ $td }}">{{ $i['account'] }}</td><td style="{{ $td }}">{{ $i['employee'] ?: '-' }}</td>
                    <td style="{{ $td }}color:{{ $badge[$i['approval']] ?? '#333' }};font-weight:bold;">{{ ucfirst($i['approval']) }}</td><td style="{{ $num }}">{{ $tk($i['amount']) }}</td></tr>
            @endforeach
        </tbody>
    </table>
@endif

<h4 style="margin:12px 0 6px;font-size:14px;color:#b8860b;">Waiting for approval ({{ $report['pending']->count() }})</h4>
<table style="border-collapse:collapse;width:100%;margin-bottom:4px;">
    <thead><tr><th style="{{ $th }}">Type</th><th style="{{ $th }}">No</th><th style="{{ $th }}">Date</th><th style="{{ $th }}">Account</th><th style="{{ $th }}">Details</th><th style="{{ $th }}text-align:right;">In (Tk)</th><th style="{{ $th }}text-align:right;">Out (Tk)</th></tr></thead>
    <tbody>
        @forelse($report['pending'] as $p)
            <tr>
                <td style="{{ $td }}">{{ $p['type'] }}</td><td style="{{ $td }}white-space:nowrap;">{{ $p['no'] }}</td><td style="{{ $td }}white-space:nowrap;">{{ $p['date']?->format('d M Y') }}</td>
                <td style="{{ $td }}">{{ $p['account'] }}</td><td style="{{ $td }}">{{ $p['details'] ?: '-' }}</td>
                <td style="{{ $num }}">{{ $p['cash'] === 'in' ? $tk($p['amount']) : '' }}</td>
                <td style="{{ $num }}">@if($p['cash'] === 'out'){{ $tk($p['amount']) }}@elseif($p['cash'] === 'iou')<span style="color:#777;">({{ $tk($p['amount']) }})</span>@endif</td>
            </tr>
        @empty
            <tr><td colspan="7" style="{{ $td }}text-align:center;color:#777;">Nothing is waiting for approval.</td></tr>
        @endforelse
    </tbody>
    @if($report['pending']->isNotEmpty())
        <tfoot><tr><td colspan="5" style="{{ $tot }}">Total</td><td style="{{ $tot }}">{{ $tk($pt['in']) }}</td><td style="{{ $tot }}">{{ $tk($pt['out']) }}</td></tr></tfoot>
    @endif
</table>
@if($pt['via_iou'])
    <p style="font-size:11px;color:#666;margin:0 0 12px;">(Tk {{ $tk($pt['via_iou']) }}) = expenses against an IOU — they move no cash themselves (the IOU advance already left the account), so they are not in Pending Out.</p>
@endif
