<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Code</th>
            <th>Master Particular</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($particulars as $particular)
        <tr>
            <td>{{ $particular->name }}</td>
            <td>{{ $particular->code ?: '-' }}</td>
            <td>{{ $particular->masterParticular->name }}</td>
            <td>{{ $particular->is_active ? 'Active' : 'Inactive' }}</td>
        </tr>
        @empty
        <tr><td colspan="4" style="text-align:center">No data available.</td></tr>
        @endforelse
    </tbody>
</table>
