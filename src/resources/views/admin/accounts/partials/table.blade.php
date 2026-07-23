<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Branch</th>
            <th>Employee ID</th>
            <th>Designation</th>
            <th>Opening Balance</th>
            <th>Current Balance</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($accounts as $account)
        <tr>
            <td>{{ $account->name }}</td>
            <td>{{ $account->branch->name }}</td>
            <td>{{ $account->employee_id ?: '-' }}</td>
            <td>{{ $account->designation ?: '-' }}</td>
            <td>{{ number_format($account->opening_balance, 2) }}</td>
            <td>{{ number_format($account->current_balance, 2) }}</td>
            <td>{{ $account->is_active ? 'Active' : 'Inactive' }}</td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center">No data available.</td></tr>
        @endforelse
    </tbody>
</table>
