<table>
    <thead>
        <tr>
            <th>Receive No.</th>
            <th>Date</th>
            <th>Branch</th>
            <th>Account</th>
            <th>Particular</th>
            <th>Amount</th>
            <th>Description</th>
            <th>Recorded By</th>
        </tr>
    </thead>
    <tbody>
        @forelse($receives as $receive)
        <tr>
            <td>{{ $receive->receive_no }}</td>
            <td>{{ $receive->receive_date->format('d M Y') }}</td>
            <td>{{ $receive->branch->name ?? '-' }}</td>
            <td>{{ $receive->account->name ?? '-' }}</td>
            <td>{{ $receive->particular ? $receive->particular->code.' - '.$receive->particular->name : '-' }}</td>
            <td class="text-right">{{ number_format($receive->amount, 2) }}</td>
            <td>{{ $receive->description ?: '-' }}</td>
            <td>{{ $receive->creator->name ?? '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center">No data available.</td></tr>
        @endforelse
    </tbody>
    @if($totals['count'] > 0)
    <tfoot>
        <tr class="grandtotal-row">
            <th colspan="5" style="text-align:right">TOTAL ({{ $totals['count'] }} records)</th>
            <th style="text-align:right">{{ number_format($totals['amount'], 2) }}</th>
            <th colspan="2"></th>
        </tr>
    </tfoot>
    @endif
</table>
