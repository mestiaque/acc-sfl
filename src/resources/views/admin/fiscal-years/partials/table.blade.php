<table>
    <thead>
        <tr>
            <th>Fiscal Year</th>
            <th>Start</th>
            <th>End</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($fiscalYears as $fiscalYear)
        <tr>
            <td>{{ $fiscalYear->label }}</td>
            <td>{{ str_pad($fiscalYear->start_month, 2, '0', STR_PAD_LEFT) }}/{{ $fiscalYear->start_year }}</td>
            <td>{{ str_pad($fiscalYear->end_month, 2, '0', STR_PAD_LEFT) }}/{{ $fiscalYear->end_year }}</td>
            <td>{{ $fiscalYear->is_active ? 'Active' : 'Inactive' }}</td>
        </tr>
        @empty
        <tr><td colspan="4">No fiscal years found.</td></tr>
        @endforelse
    </tbody>
</table>
