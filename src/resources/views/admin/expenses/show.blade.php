<table class="table table-sm">
    <tr><th>Expense No.</th><td>{{ $expense->expense_no }}</td></tr>
    <tr><th>Date</th><td>{{ $expense->expense_date->format('d M Y') }}</td></tr>
    <tr><th>Branch</th><td>{{ $expense->branch->name }}</td></tr>
    <tr><th>Account</th><td>{{ $expense->account->name }}</td></tr>
    <tr><th>Payment Method</th><td>{{ $expense->paymentMethod->name }}</td></tr>
    <tr><th>Company Name</th><td>{{ $expense->company_name ?: '-' }}</td></tr>
    <tr><th>Receiver</th><td>{{ $expense->receiver_name ?: '-' }} {{ $expense->receiver_mobile ? '('.$expense->receiver_mobile.')' : '' }}</td></tr>
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
</table>

<h6 class="mt-3">Expense Items</h6>
<div class="table-responsive">
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th>Particular</th>
                <th>Invoice</th>
                <th>Qty</th>
                <th>UOM</th>
                <th>Rate</th>
                <th>Amount</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expense->details as $detail)
            <tr>
                <td>{{ $detail->particular->name }}</td>
                <td>{{ $detail->invoice ?: '-' }}</td>
                <td>{{ number_format($detail->qty, 2) }}</td>
                <td>{{ $detail->uom ?: '-' }}</td>
                <td>{{ number_format($detail->rate, 2) }}</td>
                <td>{{ number_format($detail->amount, 2) }}</td>
                <td>{{ $detail->description ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="text-right">Total Amount</th>
                <th>{{ number_format($expense->total_amount, 2) }}</th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</div>
