<table>
    <thead>
        <tr>
            <th>Month</th>
            <th>Date</th>
            <th>Particular</th>
            <th>Description</th>
            <th>A/C Code</th>
            <th>Receiver</th>
            <th>Receive</th>
            <th>Balance</th>
            <th>Remarks</th>
        </tr>
    </thead>
    <tbody>
        @forelse($grouped as $code => $group)
        <tr class="subtotal-row">
            <td colspan="9"><strong>{{ $code }} - {{ $group->first()->particular->name ?? 'N/A' }}</strong></td>
        </tr>
        @foreach($group as $receive)
        @php
            $transaction = $receive->transactions->first();
            $balance = $transaction->balance ?? null;
        @endphp
        <tr>
            <td>{{ strtoupper($receive->receive_date->format('F')) }}</td>
            <td>{{ $receive->receive_date->format('d-m-y') }}</td>
            <td>{{ $receive->particular->name ?? '-' }}</td>
            <td>{{ $receive->description ?: '-' }}</td>
            <td>{{ $receive->particular->code ?? '-' }}</td>
            <td>-</td>
            <td style="text-align:right">{{ number_format($receive->amount, 2) }}</td>
            <td style="text-align:right">{{ $balance !== null ? number_format($balance, 2) : '-' }}</td>
            <td>-</td>
        </tr>
        @endforeach
        <tr class="subtotal-row">
            <td colspan="6" style="text-align:right">TOTAL {{ $code }}</td>
            <td style="text-align:right">{{ number_format($group->sum('amount'), 2) }}</td>
            <td colspan="2"></td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center">No data available.</td></tr>
        @endforelse
    </tbody>
    @if($totals['count'] > 0)
    <tfoot>
        <tr class="grandtotal-row">
            <th colspan="6" style="text-align:right">TOTAL ({{ $totals['count'] }} records)</th>
            <th style="text-align:right">{{ number_format($totals['amount'], 2) }}</th>
            <th colspan="2"></th>
        </tr>
    </tfoot>
    @endif
</table>
