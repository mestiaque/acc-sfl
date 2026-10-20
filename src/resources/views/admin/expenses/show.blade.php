@php $acFiles = collect($expense->galleryFiles()); @endphp

{{--
    The attachment preview floats top-right at 40% width; each info row is its own flex box
    (a new block formatting context), so rows beside the preview narrow to fit and the rows
    below it run full width.
--}}
<style>
    .ac-exp-info { overflow: hidden; }
    .ac-exp-preview { float: right; width: 40%; margin: 0 0 12px 16px; }
    .ac-exp-row { display: flex; border-top: 1px solid #dee2e6; padding: .3rem .3rem; font-size: 13px; }
    .ac-exp-row > .ac-exp-label { flex: 0 0 160px; font-weight: 600; }
    .ac-exp-row > .ac-exp-value { flex: 1 1 auto; min-width: 0; }
    .ac-exp-row:last-child { border-bottom: 1px solid #dee2e6; }
    @media (max-width: 575.98px) { .ac-exp-preview { float: none; width: 100%; margin: 0 0 12px; } }
</style>

<div class="ac-exp-info">
    <div class="ac-exp-preview">
        @include('acc-sfl::admin.partials.attachment-gallery', ['files' => $acFiles->all()])
    </div>

    <div class="ac-exp-row"><div class="ac-exp-label">Expense No.</div><div class="ac-exp-value">{{ $expense->expense_no }}</div></div>
    <div class="ac-exp-row"><div class="ac-exp-label">Date</div><div class="ac-exp-value">{{ $expense->expense_date->format('d M Y') }}</div></div>
    <div class="ac-exp-row"><div class="ac-exp-label">Billing Date</div><div class="ac-exp-value">{{ $expense->billing_date?->format('d M Y') ?? '-' }}</div></div>
    <div class="ac-exp-row"><div class="ac-exp-label">Branch</div><div class="ac-exp-value">{{ $expense->branch->name }}</div></div>
    <div class="ac-exp-row"><div class="ac-exp-label">Account</div><div class="ac-exp-value">{{ $expense->account->name }}</div></div>
    <div class="ac-exp-row"><div class="ac-exp-label">Payment Method</div><div class="ac-exp-value">{{ $expense->paymentMethod->name }}</div></div>
    <div class="ac-exp-row"><div class="ac-exp-label">Company Name</div><div class="ac-exp-value">{{ $expense->company_name ?: '-' }}</div></div>
    <div class="ac-exp-row"><div class="ac-exp-label">Receiver</div><div class="ac-exp-value">{{ $expense->receiver_name ?: '-' }} {{ $expense->receiver_mobile ? '('.$expense->receiver_mobile.')' : '' }}</div></div>
    <div class="ac-exp-row"><div class="ac-exp-label">Employee</div><div class="ac-exp-value">{{ $expense->employee->name ?? '-' }}</div></div>
    @if($expense->iou)
    <div class="ac-exp-row"><div class="ac-exp-label">Against IOU</div><div class="ac-exp-value">{{ $expense->iou->iou_no }} <small class="text-muted">(paid from IOU — posted to the ledger when the IOU is adjusted)</small></div></div>
    @endif
    <div class="ac-exp-row"><div class="ac-exp-label">Invoice</div><div class="ac-exp-value">{{ $expense->invoice ?: '-' }}</div></div>
    <div class="ac-exp-row"><div class="ac-exp-label">Attachments</div><div class="ac-exp-value">{{ $acFiles->count() ?: '-' }}</div></div>
    <div class="ac-exp-row"><div class="ac-exp-label">Recorded By</div><div class="ac-exp-value">{{ $expense->creator->name ?? '-' }}</div></div>
    <div class="ac-exp-row">
        <div class="ac-exp-label">Status</div>
        <div class="ac-exp-value">
            <span class="badge {{ ['pending' => 'badge-warning', 'approved' => 'badge-success', 'rejected' => 'badge-danger'][$expense->status] ?? 'badge-secondary' }} p-1">{{ ucfirst($expense->status) }}</span>
            @if($expense->status !== 'pending')
                by {{ $expense->approver->name ?? '-' }} on {{ $expense->approved_at?->format('d M Y h:i A') }}
                @if($expense->approval_remarks)
                    — {{ $expense->approval_remarks }}
                @endif
            @endif
        </div>
    </div>
</div>

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

<h6 class="mt-3">Description</h6>
<div class="border rounded p-2 bg-light" style="white-space:pre-line;font-size:13px;">{{ trim(html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], "\n", (string) $expense->description)))) ?: '-' }}</div>
