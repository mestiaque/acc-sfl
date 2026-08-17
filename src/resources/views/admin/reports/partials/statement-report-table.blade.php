@php
    $money = fn ($value) => number_format($value, 2);
@endphp
<table>
    <thead>
        <tr>
            <th colspan="14">
                Account: {{ $statement['account']->name }} ({{ $statement['account']->branch->name ?? '-' }})
                &nbsp;|&nbsp; Period: {{ $statement['from_date']->format('d M Y') }} to {{ $statement['to_date']->format('d M Y') }}
            </th>
        </tr>
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
            <th>Receive</th>
            <th>Balance</th>
            <th>Remarks</th>
        </tr>
    </thead>
    <tbody>
        <tr class="subtotal-row">
            <td colspan="12" style="text-align:right"><strong>OPENING BALANCE</strong></td>
            <td style="text-align:right"><strong>{{ $money($statement['opening_balance']) }}</strong></td>
            <td></td>
        </tr>
        @forelse($statement['rows'] as $row)
        <tr>
            <td>{{ $row['date'] ? strtoupper($row['date']->format('F')) : '-' }}</td>
            <td>{{ $row['date']?->format('d-m-y') ?? '-' }}</td>
            <td>{{ $row['particular'] ?: '-' }}</td>
            <td>{{ $row['description'] ?: '-' }}</td>
            <td>{{ $row['ac_code'] ?: '-' }}</td>
            <td>{{ $row['invoice'] ?: '-' }}</td>
            <td>{{ $row['receiver'] ?: '-' }}</td>
            <td style="text-align:right">{{ $row['qty'] !== null ? $money($row['qty']) : '-' }}</td>
            <td>{{ $row['uom'] ?: '-' }}</td>
            <td style="text-align:right">{{ $row['rate'] !== null ? $money($row['rate']) : '-' }}</td>
            <td style="text-align:right">{{ $row['expense'] !== null ? $money($row['expense']) : '-' }}</td>
            <td style="text-align:right">{{ $row['receive'] !== null ? $money($row['receive']) : '-' }}</td>
            <td style="text-align:right">{{ $money($row['balance']) }}</td>
            <td>{{ $row['remarks'] ?: '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="14" style="text-align:center">No transactions in this period.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="subtotal-row">
            <th colspan="10" style="text-align:right">PERIOD TOTAL</th>
            <th style="text-align:right">{{ $money($statement['total_expense']) }}</th>
            <th style="text-align:right">{{ $money($statement['total_receive']) }}</th>
            <th></th>
            <th></th>
        </tr>
        <tr class="grandtotal-row">
            <th colspan="12" style="text-align:right">CLOSING BALANCE</th>
            <th style="text-align:right">{{ $money($statement['closing_balance']) }}</th>
            <th></th>
        </tr>
    </tfoot>
</table>
