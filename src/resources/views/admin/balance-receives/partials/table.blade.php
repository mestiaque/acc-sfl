<table>
    <thead>
        <tr>
            <th>Receive No.</th>
            <th>Date</th>
            <th>Branch</th>
            <th>Account</th>
            <th>Particular</th>
            <th>Amount</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        @forelse($balanceReceives as $receive)
        <tr>
            <td>{{ $receive->receive_no }}</td>
            <td>{{ $receive->receive_date->format('d M Y') }}</td>
            <td>{{ $receive->branch->name }}</td>
            <td>{{ $receive->account->name }}</td>
            <td>{{ $receive->particular->name }}</td>
            <td>{{ number_format($receive->amount, 2) }}</td>
            <td>{{ $receive->description ?: '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center">No data available.</td></tr>
        @endforelse
    </tbody>
</table>
