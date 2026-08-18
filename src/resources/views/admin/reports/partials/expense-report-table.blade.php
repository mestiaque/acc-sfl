<table>
    <thead>
        <tr>
            <th>Month</th>
            <th>Date</th>
            <th>Particular</th>
            <th>Description</th>
            <th>A/C Code</th>
            <th>Invoice / Challan No.</th>
            <th>Receiver</th>
            <th>Qty</th>
            <th>Unit of Measure</th>
            <th>Rate</th>
            <th>Expense</th>
            <th>Balance</th>
            <th>Remarks</th>
        </tr>
    </thead>
    <tbody>
        @forelse($grouped as $code => $group)
        <tr class="subtotal-row">
            <td colspan="13"><strong>{{ $code }} - {{ $group->first()['particular'] ?? 'N/A' }}</strong></td>
        </tr>
        @foreach($group as $row)
        <tr>
            <td>{{ $row['date'] ? strtoupper($row['date']->format('F')) : '-' }}</td>
            <td>{{ $row['date']?->format('d-m-y') ?? '-' }}</td>
            <td>{{ $row['particular'] ?: '-' }}</td>
            <td>{{ $row['description'] ?: '-' }}</td>
            <td>{{ $row['ac_code'] ?: '-' }}</td>
            <td>{{ $row['invoice'] ?: '-' }}</td>
            <td>{{ $row['receiver'] ?: '-' }}</td>
            <td style="text-align:right">{{ $row['qty'] !== null ? number_format($row['qty'], 2) : '-' }}</td>
            <td>{{ $row['uom'] ?: '-' }}</td>
            <td style="text-align:right">{{ $row['rate'] !== null ? number_format($row['rate'], 2) : '-' }}</td>
            <td style="text-align:right">{{ number_format($row['expense'], 2) }}</td>
            <td style="text-align:right">{{ $row['balance'] !== null ? number_format($row['balance'], 2) : '-' }}</td>
            <td>{{ $row['remarks'] ?: '-' }}</td>
        </tr>
        @endforeach
        <tr class="subtotal-row">
            <td colspan="10" style="text-align:right">TOTAL {{ $code }}</td>
            <td style="text-align:right">{{ number_format($group->sum('expense'), 2) }}</td>
            <td colspan="2"></td>
        </tr>
        @empty
        <tr><td colspan="13" style="text-align:center">No data available.</td></tr>
        @endforelse
    </tbody>
    @if($totals['count'] > 0)
    <tfoot>
        <tr class="grandtotal-row">
            <th colspan="10" style="text-align:right">TOTAL ({{ $totals['count'] }} records)</th>
            <th style="text-align:right">{{ number_format($totals['amount'], 2) }}</th>
            <th colspan="2"></th>
        </tr>
    </tfoot>
    @endif
</table>
