@php
    $days = $report['days'];
    $daysInMonth = $report['days_in_month'];
    $totalCols = $daysInMonth + 2;
    $money = fn ($value) => '<span class="cf-amt"><span class="cf-cur">BDT</span><span class="cf-num">'.($value != 0 ? number_format($value, 2) : '-').'</span></span>';
    $monthLabel = \Illuminate\Support\Carbon::create($report['year'], $report['month'], 1)->format('F');
@endphp
<div class="table-responsive cash-flow-scroll">
<table class="table table-bordered table-sm cash-flow-table mb-0">
    <thead>
        <tr>
            <th style="min-width:240px">{{ $report['label'] }}</th>
            @foreach($days as $d => $day)
            <th class="text-center">{{ $d }}</th>
            @endforeach
            <th class="text-center bg-dark text-white" style="min-width:130px">{{ strtoupper($monthLabel) }} TOTALS</th>
        </tr>
    </thead>
    <tbody>
        <tr class="table-info font-weight-bold">
            <th>BEGINNING BALANCE | CASH ON HAND</th>
            @foreach($days as $day)
            <td class="text-right">{!! $money($day['beginning_balance']) !!}</td>
            @endforeach
            <td class="text-right">{!! $money($report['beginning_balance']) !!}</td>
        </tr>

        <tr><th colspan="{{ $totalCols }}" class="bg-success text-white"><span class="cf-sticky-label">( + ) CASH RECEIPTS</span></th></tr>
        @foreach($report['taxonomy']['receipts'] as $master)
            @foreach($master->particulars as $particular)
            <tr>
                <td class="pl-4">{{ $particular->code }} - {{ strtoupper($particular->name) }}</td>
                @foreach($days as $day)
                <td class="text-right">{!! $money($day['receipts'][$particular->id] ?? 0) !!}</td>
                @endforeach
                <td class="text-right">{!! $money($report['totals_by_particular'][$particular->id] ?? 0) !!}</td>
            </tr>
            @endforeach
        @endforeach
        <tr class="font-weight-bold table-success">
            <th>TOTAL CASH RECEIPTS</th>
            @foreach($days as $day)
            <td class="text-right">{!! $money($day['total_receipts']) !!}</td>
            @endforeach
            <td class="text-right">{!! $money($report['total_receipts']) !!}</td>
        </tr>

        <tr><th colspan="{{ $totalCols }}" class="bg-danger text-white"><span class="cf-sticky-label">( - ) CASH PAYMENTS</span></th></tr>
        @foreach($report['taxonomy']['payments'] as $master)
            <tr><th colspan="{{ $totalCols }}" class="bg-warning"><span class="cf-sticky-label">{{ strtoupper($master->name) }}</span></th></tr>
            @php
                $masterDayTotals = [];
                foreach ($days as $d => $day) { $masterDayTotals[$d] = 0; }
                $masterGrandTotal = 0;
            @endphp
            @foreach($master->particulars as $particular)
            <tr>
                <td class="pl-4">{{ $particular->code }} - {{ strtoupper($particular->name) }}</td>
                @foreach($days as $d => $day)
                @php
                    $amt = $day['payments'][$particular->id] ?? 0;
                    $masterDayTotals[$d] += $amt;
                @endphp
                <td class="text-right">{!! $money($amt) !!}</td>
                @endforeach
                @php
                    $particularTotal = $report['totals_by_particular'][$particular->id] ?? 0;
                    $masterGrandTotal += $particularTotal;
                @endphp
                <td class="text-right">{!! $money($particularTotal) !!}</td>
            </tr>
            @endforeach
            <tr class="font-weight-bold table-warning">
                <th>TOTAL {{ strtoupper($master->name) }}</th>
                @foreach($masterDayTotals as $amt)
                <td class="text-right">{!! $money($amt) !!}</td>
                @endforeach
                <td class="text-right">{!! $money($masterGrandTotal) !!}</td>
            </tr>
        @endforeach

        <tr class="font-weight-bold table-danger">
            <th>TOTAL CASH PAYMENTS</th>
            @foreach($days as $day)
            <td class="text-right">{!! $money($day['total_payments']) !!}</td>
            @endforeach
            <td class="text-right">{!! $money($report['total_payments']) !!}</td>
        </tr>

        <tr class="font-weight-bold table-secondary">
            <th>NET CASH CHANGE (CASH RECEIPTS &minus; CASH PAYMENTS)</th>
            @foreach($days as $day)
            <td class="text-right">{!! $money($day['total_receipts'] - $day['total_payments']) !!}</td>
            @endforeach
            <td class="text-right">{!! $money($report['total_receipts'] - $report['total_payments']) !!}</td>
        </tr>

        <tr class="font-weight-bold table-dark text-white">
            <th>MONTH-ENDING CASH POSITION</th>
            @foreach($days as $day)
            <td class="text-right">{!! $money($day['ending_balance']) !!}</td>
            @endforeach
            <td class="text-right">{!! $money($report['ending_balance']) !!}</td>
        </tr>
    </tbody>
</table>
</div>
