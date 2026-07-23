<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($paymentMethods as $paymentMethod)
        <tr>
            <td>{{ $paymentMethod->name }}</td>
            <td>{{ $paymentMethod->is_active ? 'Active' : 'Inactive' }}</td>
        </tr>
        @empty
        <tr><td colspan="2" style="text-align:center">No data available.</td></tr>
        @endforelse
    </tbody>
</table>
