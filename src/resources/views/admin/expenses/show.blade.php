<table class="table table-sm">
    <tr><th>Expense No.</th><td>{{ $expense->expense_no }}</td></tr>
    <tr><th>Date</th><td>{{ $expense->expense_date->format('d M Y') }}</td></tr>
    <tr><th>Branch</th><td>{{ $expense->branch->name }}</td></tr>
    <tr><th>Account</th><td>{{ $expense->account->name }}</td></tr>
    <tr><th>Payment Method</th><td>{{ $expense->paymentMethod->name }}</td></tr>
    <tr><th>Company Name</th><td>{{ $expense->company_name ?: '-' }}</td></tr>
    <tr><th>Receiver</th><td>{{ $expense->receiver_name ?: '-' }} {{ $expense->receiver_mobile ? '('.$expense->receiver_mobile.')' : '' }}</td></tr>
    <tr><th>Employee</th><td>{{ $expense->employee->name ?? '-' }}</td></tr>
    <tr><th>Invoice</th><td>{{ $expense->invoice ?: '-' }}</td></tr>
    <tr><th>Description</th><td>{{ $expense->description ?: '-' }}</td></tr>
    <tr>
        <th>Attachment</th>
        <td>
            @if($expense->attachment)
                <a href="{{ \Illuminate\Support\Facades\Storage::url($expense->attachment) }}" target="_blank">View file</a>
            @else
                -
            @endif
        </td>
    </tr>
    <tr><th>Recorded By</th><td>{{ $expense->creator->name ?? '-' }}</td></tr>
    <tr>
        <th>Status</th>
        <td>
            <span class="badge {{ ['pending' => 'badge-warning', 'approved' => 'badge-success', 'rejected' => 'badge-danger'][$expense->status] ?? 'badge-secondary' }} p-1">{{ ucfirst($expense->status) }}</span>
            @if($expense->status !== 'pending')
                by {{ $expense->approver->name ?? '-' }} on {{ $expense->approved_at?->format('d M Y h:i A') }}
                @if($expense->approval_remarks)
                    — {{ $expense->approval_remarks }}
                @endif
            @endif
        </td>
    </tr>
</table>

<h6 class="mt-3">Expense Items</h6>
<div class="table-responsive">
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th>Particular</th>
                <th>UOM</th>
                <th>Qty</th>
                <th>Rate</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expense->details as $detail)
            <tr>
                <td>{{ $detail->particular->name }}</td>
                <td>{{ $detail->uom ?: '-' }}</td>
                <td>{{ number_format($detail->qty, 2) }}</td>
                <td>{{ number_format($detail->rate, 2) }}</td>
                <td>{{ number_format($detail->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-right">Total Amount</th>
                <th>{{ number_format($expense->total_amount, 2) }}</th>
            </tr>
        </tfoot>
    </table>
</div>
