<table>
    <thead>
        <tr>
            <th>Expense No.</th>
            <th>Date</th>
            <th>Branch</th>
            <th>Account</th>
            <th>Payment Method</th>
            <th>Total Amount</th>
        </tr>
    </thead>
    <tbody>
        @forelse($expenses as $expense)
        <tr>
            <td>{{ $expense->expense_no }}</td>
            <td>{{ $expense->expense_date->format('d M Y') }}</td>
            <td>{{ $expense->branch->name }}</td>
            <td>{{ $expense->account->name }}</td>
            <td>{{ $expense->paymentMethod->name }}</td>
            <td>{{ number_format($expense->total_amount, 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center">No data available.</td></tr>
        @endforelse
    </tbody>
</table>
