<table>
    <thead>
        <tr>
            <th>IOU No.</th>
            <th>Employee</th>
            <th>Branch</th>
            <th>Account</th>
            <th>Issue Date</th>
            <th>Adjust Date</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        @forelse($expenseIous as $iou)
        <tr>
            <td>{{ $iou->iou_no }}</td>
            <td>{{ $iou->employee->name ?? '-' }}</td>
            <td>{{ $iou->branch->name ?? '-' }}</td>
            <td>{{ $iou->account->name ?? '-' }}</td>
            <td>{{ $iou->issue_date->format('d M Y') }}</td>
            <td>{{ $iou->adjust_date ? $iou->adjust_date->format('d M Y') : '-' }}</td>
            <td class="text-right">{{ number_format($iou->amount, 2) }}</td>
            <td>{{ $iou->status }}</td>
            <td>{{ $iou->description ?: '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center">No data available.</td></tr>
        @endforelse
    </tbody>
    @if($totals['count'] > 0)
    <tfoot>
        <tr class="subtotal-row">
            <th colspan="6" style="text-align:right">TOTAL PENDING</th>
            <th style="text-align:right">{{ number_format($totals['pending_amount'], 2) }}</th>
            <th colspan="2"></th>
        </tr>
        <tr class="subtotal-row">
            <th colspan="6" style="text-align:right">TOTAL ADJUSTED</th>
            <th style="text-align:right">{{ number_format($totals['adjusted_amount'], 2) }}</th>
            <th colspan="2"></th>
        </tr>
        <tr class="grandtotal-row">
            <th colspan="6" style="text-align:right">TOTAL ({{ $totals['count'] }} records)</th>
            <th style="text-align:right">{{ number_format($totals['amount'], 2) }}</th>
            <th colspan="2"></th>
        </tr>
    </tfoot>
    @endif
</table>
