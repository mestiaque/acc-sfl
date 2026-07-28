<table>
    <thead>
        <tr>
            <th>Expense No.</th>
            <th>Date</th>
            <th>Branch</th>
            <th>Account</th>
            <th>Payment Method</th>
            <th>Particular</th>
            <th>Amount</th>
            <th>Receiver</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        @forelse($expenses as $expense)
        @php($detail = $expense->details->first())
        <tr>
            <td>{{ $expense->expense_no }}</td>
            <td>{{ $expense->expense_date->format('d M Y') }}</td>
            <td>{{ $expense->branch->name ?? '-' }}</td>
            <td>{{ $expense->account->name ?? '-' }}</td>
            <td>{{ $expense->paymentMethod->name ?? '-' }}</td>
            <td>{{ $detail && $detail->particular ? $detail->particular->code.' - '.$detail->particular->name : '-' }}</td>
            <td class="text-right">{{ number_format($expense->total_amount, 2) }}</td>
            <td>{{ $expense->receiver_name ?: '-' }}</td>
            <td>{{ $expense->description ?: '-' }}</td>
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
