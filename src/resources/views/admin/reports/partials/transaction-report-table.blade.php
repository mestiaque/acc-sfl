<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Branch</th>
            <th>Account</th>
            <th>Payment Method</th>
            <th>Debit</th>
            <th>Credit</th>
            <th>Balance</th>
            <th>Description</th>
            <th>Recorded By</th>
        </tr>
    </thead>
    <tbody>
        @forelse($transactions as $transaction)
        <tr>
            <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
            <td>{{ $transaction->transaction_type }}</td>
            <td>{{ $transaction->branch->name ?? '-' }}</td>
            <td>{{ $transaction->account->name ?? '-' }}</td>
            <td>{{ $transaction->paymentMethod->name ?? '-' }}</td>
            <td class="text-right">{{ $transaction->debit > 0 ? number_format($transaction->debit, 2) : '-' }}</td>
            <td class="text-right">{{ $transaction->credit > 0 ? number_format($transaction->credit, 2) : '-' }}</td>
            <td class="text-right">{{ number_format($transaction->balance, 2) }}</td>
            <td>{{ $transaction->description ?: '-' }}</td>
            <td>{{ $transaction->creator->name ?? '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="10" style="text-align:center">No data available.</td></tr>
        @endforelse
    </tbody>
    @if($totals['count'] > 0)
    <tfoot>
        <tr class="grandtotal-row">
            <th colspan="5" style="text-align:right">TOTAL ({{ $totals['count'] }} records)</th>
            <th style="text-align:right">{{ number_format($totals['debit'], 2) }}</th>
            <th style="text-align:right">{{ number_format($totals['credit'], 2) }}</th>
            <th style="text-align:right">{{ number_format($totals['net'], 2) }}</th>
            <th colspan="2"></th>
        </tr>
    </tfoot>
    @endif
</table>
