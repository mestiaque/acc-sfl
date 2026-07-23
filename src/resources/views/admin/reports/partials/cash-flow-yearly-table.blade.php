@php
    $months = $report['months'];
    $quarters = $report['quarters'];
    $money = fn ($value) => $value != 0 ? 'BDT '.number_format($value, 2) : 'BDT -';
    $monthChunks = array_chunk($months, 3, true);
    $totalCols = 12 + 4 + 1 + 1; // 12 months + 4 quarter totals + 1 FY total + label col
@endphp
<div class="table-responsive">
<table class="table table-bordered table-sm cash-flow-table mb-0">
    <thead>
        <tr>
            <th style="min-width:240px">{{ $report['label'] }}</th>
            @foreach($monthChunks as $qi => $chunk)
                @foreach($chunk as $month)
                <th class="text-center">{{ $month['label'] }}</th>
                @endforeach
                <th class="text-center bg-secondary text-white">QUARTER {{ $qi + 1 }} TOTALS</th>
            @endforeach
            <th class="text-center bg-dark text-white" style="min-width:130px">FISCAL YEAR TOTALS</th>
        </tr>
    </thead>
    <tbody>
        <tr class="table-info font-weight-bold">
            <th>BEGINNING BALANCE | CASH ON HAND</th>
            @foreach($monthChunks as $qi => $chunk)
                @foreach($chunk as $month)
                <td class="text-right">{{ $money($month['beginning_balance']) }}</td>
                @endforeach
                <td class="text-right">{{ $money($quarters[$qi + 1]['beginning_balance']) }}</td>
            @endforeach
            <td class="text-right">{{ $money($report['beginning_balance']) }}</td>
        </tr>

        <tr><th colspan="{{ $totalCols }}" class="bg-success text-white">( + ) CASH RECEIPTS</th></tr>
        @foreach($report['taxonomy']['receipts'] as $master)
            @foreach($master->particulars as $particular)
            <tr>
                <td class="pl-4">{{ strtoupper($particular->name) }}</td>
                @foreach($monthChunks as $qi => $chunk)
                    @php $quarterSum = 0; @endphp
                    @foreach($chunk as $month)
                        @php
                            $amt = $month['receipts'][$particular->id] ?? 0;
                            $quarterSum += $amt;
                        @endphp
                        <td class="text-right">{{ $money($amt) }}</td>
                    @endforeach
                    <td class="text-right">{{ $money($quarterSum) }}</td>
                @endforeach
                <td class="text-right">{{ $money($report['totals_by_particular'][$particular->id] ?? 0) }}</td>
            </tr>
            @endforeach
        @endforeach
        <tr class="font-weight-bold table-success">
            <th>TOTAL CASH RECEIPTS</th>
            @foreach($monthChunks as $qi => $chunk)
                @foreach($chunk as $month)
                <td class="text-right">{{ $money($month['total_receipts']) }}</td>
                @endforeach
                <td class="text-right">{{ $money($quarters[$qi + 1]['total_receipts']) }}</td>
            @endforeach
            <td class="text-right">{{ $money($report['total_receipts']) }}</td>
        </tr>

        <tr><th colspan="{{ $totalCols }}" class="bg-danger text-white">( - ) CASH PAYMENTS</th></tr>
        @foreach($report['taxonomy']['payments'] as $master)
            <tr><th colspan="{{ $totalCols }}" class="bg-warning">{{ strtoupper($master->name) }}</th></tr>
            @php
                $masterGrandTotal = 0;
            @endphp
            @foreach($master->particulars as $particular)
            <tr>
                <td class="pl-4">{{ strtoupper($particular->name) }}</td>
                @foreach($monthChunks as $qi => $chunk)
                    @php $quarterSum = 0; @endphp
                    @foreach($chunk as $month)
                        @php
                            $amt = $month['payments'][$particular->id] ?? 0;
                            $quarterSum += $amt;
                        @endphp
                        <td class="text-right">{{ $money($amt) }}</td>
                    @endforeach
                    <td class="text-right">{{ $money($quarterSum) }}</td>
                @endforeach
                @php
                    $particularTotal = $report['totals_by_particular'][$particular->id] ?? 0;
                    $masterGrandTotal += $particularTotal;
                @endphp
                <td class="text-right">{{ $money($particularTotal) }}</td>
            </tr>
            @endforeach
            <tr class="font-weight-bold table-warning">
                <th>TOTAL {{ strtoupper($master->name) }}</th>
                @foreach($monthChunks as $qi => $chunk)
                    @php $quarterSum = 0; @endphp
                    @foreach($chunk as $month)
                        @php
                            $sum = 0;
                            foreach ($master->particulars as $p) { $sum += $month['payments'][$p->id] ?? 0; }
                            $quarterSum += $sum;
                        @endphp
                        <td class="text-right">{{ $money($sum) }}</td>
                    @endforeach
                    <td class="text-right">{{ $money($quarterSum) }}</td>
                @endforeach
                <td class="text-right">{{ $money($masterGrandTotal) }}</td>
            </tr>
        @endforeach

        <tr class="font-weight-bold table-secondary">
            <th>ENDING BALANCE</th>
            @foreach($monthChunks as $qi => $chunk)
                @foreach($chunk as $month)
                <td class="text-right">{{ $money($month['ending_balance']) }}</td>
                @endforeach
                <td class="text-right">{{ $money($quarters[$qi + 1]['ending_balance']) }}</td>
            @endforeach
            <td class="text-right">{{ $money($report['ending_balance']) }}</td>
        </tr>
    </tbody>
</table>
</div>
