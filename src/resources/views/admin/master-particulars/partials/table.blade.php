<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Particulars Count</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($masterParticulars as $masterParticular)
        <tr>
            <td>{{ $masterParticular->name }}</td>
            <td>{{ $masterParticular->type === 'debit' ? 'Debit' : 'Credit' }}</td>
            <td>{{ $masterParticular->particulars_count }}</td>
            <td>{{ $masterParticular->is_active ? 'Active' : 'Inactive' }}</td>
        </tr>
        @empty
        <tr><td colspan="4" style="text-align:center">No data available.</td></tr>
        @endforelse
    </tbody>
</table>
