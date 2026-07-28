@php
    $money = fn ($value) => number_format($value, 2);
@endphp
<table>
    <thead>
        <tr>
            <th colspan="7">
                Account: {{ $statement['account']->name }} ({{ $statement['account']->branch->name ?? '-' }})
                &nbsp;|&nbsp; Period: {{ $statement['from_date']->format('d M Y') }} to {{ $statement['to_date']->format('d M Y') }}
            </th>
        </tr>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Payment Method</th>
            <th>Description</th>
            <th>Debit</th>
            <th>Credit</th>
            <th>Balance</th>
        </tr>
    </thead>
    <tbody>
        <tr class="subtotal-row">
            <td colspan="6" style="text-align:right"><strong>OPENING BALANCE</strong></td>
            <td style="text-align:right"><strong>{{ $money($statement['opening_balance']) }}</strong></td>
        </tr>
        @forelse($statement['transactions'] as $transaction)
        <tr>
            <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
            <td>{{ $transaction->transaction_type }}</td>
            <td>{{ $transaction->paymentMethod->name ?? '-' }}</td>
            <td>{{ $transaction->description ?: '-' }}</td>
            <td style="text-align:right">{{ $transaction->debit > 0 ? $money($transaction->debit) : '-' }}</td>
            <td style="text-align:right">{{ $transaction->credit > 0 ? $money($transaction->credit) : '-' }}</td>
            <td style="text-align:right">{{ $money($transaction->balance) }}</td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center">No transactions in this period.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="subtotal-row">
            <th colspan="4" style="text-align:right">PERIOD TOTAL</th>
            <th style="text-align:right">{{ $money($statement['total_debit']) }}</th>
            <th style="text-align:right">{{ $money($statement['total_credit']) }}</th>
            <th></th>
        </tr>
        <tr class="grandtotal-row">
            <th colspan="6" style="text-align:right">CLOSING BALANCE</th>
            <th style="text-align:right">{{ $money($statement['closing_balance']) }}</th>
        </tr>
    </tfoot>
</table>
