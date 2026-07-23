<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Code</th>
            <th>Location</th>
            <th>Branch Head</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($branches as $branch)
        <tr>
            <td>{{ $branch->name }}</td>
            <td>{{ $branch->code }}</td>
            <td>{{ $branch->location ?: '-' }}</td>
            <td>{{ $branch->branch_head ?: '-' }}</td>
            <td>{{ $branch->is_active ? 'Active' : 'Inactive' }}</td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center">No data available.</td></tr>
        @endforelse
    </tbody>
</table>
