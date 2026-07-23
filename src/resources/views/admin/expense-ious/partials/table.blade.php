<table>
    <thead>
        <tr>
            <th>IOU No.</th>
            <th>Issue Date</th>
            <th>Adjust Date</th>
            <th>Branch</th>
            <th>Account</th>
            <th>Employee</th>
            <th>Payment Method</th>
            <th>Amount</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($expenseIous as $iou)
        <tr>
            <td>{{ $iou->iou_no }}</td>
            <td>{{ $iou->issue_date->format('d M Y') }}</td>
            <td>{{ $iou->adjust_date?->format('d M Y') ?? '-' }}</td>
            <td>{{ $iou->branch->name }}</td>
            <td>{{ $iou->account->name }}</td>
            <td>{{ $iou->employee->name ?? $iou->receiver_name ?? '-' }}</td>
            <td>{{ $iou->paymentMethod->name }}</td>
            <td>{{ number_format($iou->amount, 2) }}</td>
            <td>{{ $iou->status }}</td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center">No data available.</td></tr>
        @endforelse
    </tbody>
</table>
