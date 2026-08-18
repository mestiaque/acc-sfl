@php
    try {
        $acStats = \ME\AccSfl\Http\Controllers\DashboardController::stats();
    } catch (\Throwable $e) {
        $acStats = null;
    }
@endphp

@if($acStats)
@php
    $s = $acStats;
    $last30Labels = $s['last30']->pluck('label')->toJson();
    $last30Receipts = $s['last30']->pluck('receipts')->toJson();
    $last30Payments = $s['last30']->pluck('payments')->toJson();
    $trendLabels = $s['monthlyTrend']->pluck('label')->toJson();
    $trendReceipts = $s['monthlyTrend']->pluck('receipts')->toJson();
    $trendPayments = $s['monthlyTrend']->pluck('payments')->toJson();
    $branchNames = $s['branchBalances']->pluck('name')->toJson();
    $branchAmounts = $s['branchBalances']->pluck('balance')->toJson();
    $widgetId = 'ac_widget_'.uniqid();
    $monthNet = $s['monthReceipts'] - $s['monthExpenses'];
@endphp

<style>
.ac-stat-card-link { display: block; text-decoration: none; color: inherit; height: 100%; }
.ac-stat-card { background: #fff; border-radius: 12px; padding: 20px 18px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 12px rgba(0,0,0,.07); border: none; transition: transform .2s, box-shadow .2s; height: 100%; }
.ac-stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,.11); }
.ac-stat-icon { width: 54px; height: 54px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
.ac-stat-val { font-size: 24px; font-weight: 700; line-height: 1; margin-bottom: 3px; }
.ac-stat-lbl { font-size: 12px; color: #888; font-weight: 500; text-transform: uppercase; letter-spacing: .5px; }
.ac-section-title { font-size: 13px; font-weight: 700; color: #444; text-transform: uppercase; letter-spacing: 1px; border-left: 3px solid #10b981; padding-left: 10px; margin-bottom: 16px; }
.ac-chart-card { background: #fff; border-radius: 12px; padding: 18px 20px; box-shadow: 0 2px 12px rgba(0,0,0,.07); height: 100%; }
.ac-quick-btn { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 10px; background: #f2fbf7; border: 1px solid #dff3ea; color: #444; font-size: 13px; font-weight: 500; text-decoration: none; transition: all .2s; }
.ac-quick-btn:hover { background: #10b981; color: #fff; border-color: #10b981; }
.ac-quick-btn i { width: 20px; text-align: center; }
.ac-recent-table td { font-size: 13px; vertical-align: middle; padding: 8px 10px; }
.ac-badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
</style>

{{-- ── Section Header ── --}}
<div class="d-flex align-items-center justify-content-between mb-3 mt-1">
    <h4 class="mb-0" style="font-size:17px;font-weight:700;">
        <i class="fa-solid fa-cash-register mr-2" style="color:#10b981;"></i> Accounting Overview
    </h4>
    @if(Route::has('acc-sfl.dashboard'))
    <a href="{{ route('acc-sfl.dashboard') }}" class="btn btn-sm btn-outline-secondary" style="font-size:12px;">
        <i class="fa-solid fa-gauge mr-1"></i> Accounting Dashboard
    </a>
    @endif
</div>

{{-- ── Stat Cards ── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg">
        @if(Route::has('acc-sfl.accounts.index'))<a href="{{ route('acc-sfl.accounts.index') }}" class="ac-stat-card-link">@endif
        <div class="ac-stat-card">
            <div class="ac-stat-icon" style="background:#ecfdf5;"><i class="fa-solid fa-sack-dollar" style="color:#10b981;"></i></div>
            <div>
                <div class="ac-stat-val" style="color:#10b981;">{{ number_format($s['totalCashOnHand'], 0) }}</div>
                <div class="ac-stat-lbl">Total Cash on Hand</div>
            </div>
        </div>
        @if(Route::has('acc-sfl.accounts.index'))</a>@endif
    </div>
    <div class="col-6 col-md-4 col-lg">
        @if(Route::has('acc-sfl.balance-receives.index'))<a href="{{ route('acc-sfl.balance-receives.index') }}" class="ac-stat-card-link">@endif
        <div class="ac-stat-card">
            <div class="ac-stat-icon" style="background:#eef2ff;"><i class="fa-solid fa-hand-holding-dollar" style="color:#6366f1;"></i></div>
            <div>
                <div class="ac-stat-val" style="color:#6366f1;">{{ number_format($s['todaysReceipts'], 0) }}</div>
                <div class="ac-stat-lbl">Today's Receipts</div>
            </div>
        </div>
        @if(Route::has('acc-sfl.balance-receives.index'))</a>@endif
    </div>
    <div class="col-6 col-md-4 col-lg">
        @if(Route::has('acc-sfl.expenses.index'))<a href="{{ route('acc-sfl.expenses.index') }}" class="ac-stat-card-link">@endif
        <div class="ac-stat-card">
            <div class="ac-stat-icon" style="background:#fffbeb;"><i class="fa-solid fa-money-bill-wave" style="color:#f59e0b;"></i></div>
            <div>
                <div class="ac-stat-val" style="color:#f59e0b;">{{ number_format($s['todaysExpenses'], 0) }}</div>
                <div class="ac-stat-lbl">Today's Expenses</div>
            </div>
        </div>
        @if(Route::has('acc-sfl.expenses.index'))</a>@endif
    </div>
    <div class="col-6 col-md-4 col-lg">
        @if(Route::has('acc-sfl.expense-ious.index'))<a href="{{ route('acc-sfl.expense-ious.index') }}" class="ac-stat-card-link">@endif
        <div class="ac-stat-card">
            <div class="ac-stat-icon" style="background:#fff1f2;"><i class="fa-solid fa-file-invoice-dollar" style="color:#f43f5e;"></i></div>
            <div>
                <div class="ac-stat-val" style="color:#f43f5e;">{{ $s['pendingIouCount'] }}</div>
                <div class="ac-stat-lbl">Pending IOUs</div>
                <div class="text-muted" style="font-size:11px;">{{ number_format($s['pendingIouAmount'], 0) }} outstanding</div>
            </div>
        </div>
        @if(Route::has('acc-sfl.expense-ious.index'))</a>@endif
    </div>
    <div class="col-6 col-md-4 col-lg">
        @if(Route::has('acc-sfl.reports.cash-flow.monthly'))<a href="{{ route('acc-sfl.reports.cash-flow.monthly') }}" class="ac-stat-card-link">@endif
        <div class="ac-stat-card">
            <div class="ac-stat-icon" style="background:{{ $monthNet >= 0 ? '#ecfdf5' : '#fff1f2' }};"><i class="fa-solid fa-chart-line" style="color:{{ $monthNet >= 0 ? '#10b981' : '#f43f5e' }};"></i></div>
            <div>
                <div class="ac-stat-val" style="color:{{ $monthNet >= 0 ? '#10b981' : '#f43f5e' }};">{{ number_format($monthNet, 0) }}</div>
                <div class="ac-stat-lbl">Net Cash This Month</div>
            </div>
        </div>
        @if(Route::has('acc-sfl.reports.cash-flow.monthly'))</a>@endif
    </div>
</div>

{{-- ── Charts Row 1 ── --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="ac-chart-card">
            <div class="ac-section-title">Cash Flow – Last 30 Days</div>
            <div id="{{ $widgetId }}_flow" style="height:220px;"></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="ac-chart-card text-center">
            <div class="ac-section-title">Today's Cash Movement</div>
            <div id="{{ $widgetId }}_donut" style="height:220px;"></div>
        </div>
    </div>
</div>

{{-- ── Charts Row 2 ── --}}
<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="ac-chart-card">
            <div class="ac-section-title">Cash on Hand by Branch</div>
            <div id="{{ $widgetId }}_branch" style="height:230px;"></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="ac-chart-card">
            <div class="ac-section-title">6-Month Cash Flow Trend</div>
            <div id="{{ $widgetId }}_trend" style="height:230px;"></div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="ac-chart-card">
            <div class="ac-section-title">Quick Links</div>
            <div class="d-flex flex-column gap-2">
                @can('ac_balance_receive.list')<a href="{{ route('acc-sfl.balance-receives.index') }}" class="ac-quick-btn"><i class="fa-solid fa-hand-holding-dollar"></i> Balance Receive</a>@endcan
                @can('ac_expense.list')<a href="{{ route('acc-sfl.expenses.index') }}" class="ac-quick-btn"><i class="fa-solid fa-money-bill-wave"></i> Expenses</a>@endcan
                @can('ac_expense_iou.list')<a href="{{ route('acc-sfl.expense-ious.index') }}" class="ac-quick-btn"><i class="fa-solid fa-file-invoice-dollar"></i> Expense IOU</a>@endcan
                @can('ac_report.view')<a href="{{ route('acc-sfl.reports.cash-flow.yearly') }}" class="ac-quick-btn"><i class="fa-solid fa-chart-line"></i> Reports</a>@endcan
            </div>
        </div>
    </div>
</div>

{{-- ── Insights Row ── --}}
<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="ac-chart-card h-100">
            <div class="ac-section-title">Top Expense Categories (This Month)</div>
            @if($s['topExpenseCategories']->isNotEmpty())
                <div class="d-flex flex-column gap-2">
                    @foreach($s['topExpenseCategories'] as $category)
                    <div class="d-flex align-items-center justify-content-between" style="font-size:13px;">
                        <span>{{ $category['name'] }}</span>
                        <span class="ac-badge" style="background:#fffbeb;color:#f59e0b;">{{ number_format($category['amount'], 0) }}</span>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-muted text-center py-4" style="font-size:13px;">No expenses recorded this month</div>
            @endif
        </div>
    </div>
    <div class="col-lg-4">
        <div class="ac-chart-card h-100">
            <div class="ac-section-title">Recent Balance Receives</div>
            @if($s['recentBalanceReceives']->isNotEmpty())
                <div class="d-flex flex-column gap-2">
                    @foreach($s['recentBalanceReceives'] as $receive)
                    <div class="d-flex align-items-center justify-content-between" style="font-size:13px;">
                        <span>{{ $receive->particular->name ?? $receive->receive_no }} <span class="text-muted">({{ $receive->branch->name ?? '-' }})</span></span>
                        <span class="ac-badge" style="background:#eef2ff;color:#6366f1;">{{ number_format($receive->amount, 0) }}</span>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-muted text-center py-4" style="font-size:13px;">No balance receives yet</div>
            @endif
        </div>
    </div>
    <div class="col-lg-4">
        <div class="ac-chart-card h-100">
            <div class="ac-section-title">Recent Expenses</div>
            @if($s['recentExpenses']->isNotEmpty())
                <div class="d-flex flex-column gap-2">
                    @foreach($s['recentExpenses'] as $expense)
                    @php
                        $detail = $expense->details->first();
                    @endphp
                    <div class="d-flex align-items-center justify-content-between" style="font-size:13px;">
                        <span>{{ $detail->particular->name ?? $expense->expense_no }} <span class="text-muted">({{ $expense->branch->name ?? '-' }})</span></span>
                        <span class="ac-badge" style="background:#fff1f2;color:#f43f5e;">{{ number_format($expense->total_amount, 0) }}</span>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-muted text-center py-4" style="font-size:13px;">No expenses yet</div>
            @endif
        </div>
    </div>
</div>

{{-- ── Summary Row ── --}}
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="ac-chart-card h-100">
            <div class="ac-section-title">This Month Overview</div>
            <div class="row g-2 text-center">
                <div class="col-4">
                    <div class="ac-stat-val" style="color:#6366f1;font-size:20px;">{{ number_format($s['monthReceipts'], 0) }}</div>
                    <div class="ac-stat-lbl">Receipts</div>
                </div>
                <div class="col-4">
                    <div class="ac-stat-val" style="color:#f59e0b;font-size:20px;">{{ number_format($s['monthExpenses'], 0) }}</div>
                    <div class="ac-stat-lbl">Expenses</div>
                </div>
                <div class="col-4">
                    <div class="ac-stat-val" style="color:{{ $monthNet >= 0 ? '#10b981' : '#f43f5e' }};font-size:20px;">{{ number_format($monthNet, 0) }}</div>
                    <div class="ac-stat-lbl">Net</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="ac-chart-card h-100">
            <div class="ac-section-title">IOU Summary</div>
            <div class="row g-2 text-center">
                <div class="col-4">
                    <div class="ac-stat-val" style="color:#f43f5e;font-size:20px;">{{ $s['pendingIouCount'] }}</div>
                    <div class="ac-stat-lbl">Pending</div>
                </div>
                <div class="col-4">
                    <div class="ac-stat-val" style="color:#10b981;font-size:20px;">{{ $s['adjustedIouCount'] }}</div>
                    <div class="ac-stat-lbl">Adjusted (Month)</div>
                </div>
                <div class="col-4">
                    <div class="ac-stat-val" style="color:#6366f1;font-size:20px;">{{ number_format($s['pendingIouAmount'], 0) }}</div>
                    <div class="ac-stat-lbl">Outstanding Amount</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Recent Transactions & Account Balances ── --}}
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="ac-chart-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="ac-section-title mb-0">Recent Transactions</div>
                @if(Route::has('acc-sfl.transactions.index'))
                <a href="{{ route('acc-sfl.transactions.index') }}" style="font-size:12px;color:#10b981;text-decoration:none;">View All &rarr;</a>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background:#f8f9ff;">
                        <tr>
                            <th style="font-size:12px;color:#888;font-weight:600;border:none;padding:8px 10px;">Date</th>
                            <th style="font-size:12px;color:#888;font-weight:600;border:none;padding:8px 10px;">Type</th>
                            <th style="font-size:12px;color:#888;font-weight:600;border:none;padding:8px 10px;">Account</th>
                            <th style="font-size:12px;color:#888;font-weight:600;border:none;padding:8px 10px;">Debit</th>
                            <th style="font-size:12px;color:#888;font-weight:600;border:none;padding:8px 10px;">Credit</th>
                            <th style="font-size:12px;color:#888;font-weight:600;border:none;padding:8px 10px;">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($s['recentTransactions'] as $transaction)
                        <tr>
                            <td class="ac-recent-table">{{ $transaction->transaction_date->format('d M Y') }}</td>
                            <td class="ac-recent-table">{{ $transaction->transaction_type }}</td>
                            <td class="ac-recent-table">{{ $transaction->account->name ?? '-' }}</td>
                            <td class="ac-recent-table text-success">{{ $transaction->debit > 0 ? number_format($transaction->debit, 0) : '-' }}</td>
                            <td class="ac-recent-table text-danger">{{ $transaction->credit > 0 ? number_format($transaction->credit, 0) : '-' }}</td>
                            <td class="ac-recent-table">{{ number_format($transaction->balance, 0) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">No transactions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="ac-chart-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="ac-section-title mb-0">Account Balances</div>
                @if(Route::has('acc-sfl.accounts.index'))
                <a href="{{ route('acc-sfl.accounts.index') }}" style="font-size:12px;color:#10b981;text-decoration:none;">Manage &rarr;</a>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background:#f8f9ff;">
                        <tr>
                            <th style="font-size:12px;color:#888;font-weight:600;border:none;padding:8px 10px;">Account</th>
                            <th style="font-size:12px;color:#888;font-weight:600;border:none;padding:8px 10px;">Branch</th>
                            <th style="font-size:12px;color:#888;font-weight:600;border:none;padding:8px 10px;">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($s['accountBalances'] as $account)
                        <tr>
                            <td class="ac-recent-table">{{ $account->name }}</td>
                            <td class="ac-recent-table">{{ $account->branch->name ?? '-' }}</td>
                            <td class="ac-recent-table">{{ number_format($account->current_balance, 0) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">No accounts yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ── ApexCharts ── --}}
<script>
(function() {
    var last30Labels = {!! $last30Labels !!};
    var last30Receipts = {!! $last30Receipts !!};
    var last30Payments = {!! $last30Payments !!};
    var trendLabels = {!! $trendLabels !!};
    var trendReceipts = {!! $trendReceipts !!};
    var trendPayments = {!! $trendPayments !!};
    var branchNames = {!! $branchNames !!};
    var branchAmounts = {!! $branchAmounts !!};
    var todayReceipts = {{ $s['todaysReceipts'] }};
    var todayExpenses = {{ $s['todaysExpenses'] }};

    function initCharts() {
        // ── 30-day Cash Flow ──
        new ApexCharts(document.getElementById('{{ $widgetId }}_flow'), {
            series: [
                { name: 'Receipts', data: last30Receipts },
                { name: 'Payments', data: last30Payments },
            ],
            chart: { type: 'area', height: 220, toolbar: { show: false } },
            colors: ['#10b981', '#f43f5e'],
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: .35, opacityTo: .05 } },
            xaxis: { categories: last30Labels, labels: { rotate: -45, style: { fontSize: '10px' } }, tickAmount: 10 },
            yaxis: { labels: { style: { fontSize: '11px' } } },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
            legend: { fontSize: '12px' },
        }).render();

        // ── Today Donut ──
        new ApexCharts(document.getElementById('{{ $widgetId }}_donut'), {
            series: [todayReceipts, todayExpenses],
            chart: { type: 'donut', height: 220 },
            labels: ['Receipts', 'Expenses'],
            colors: ['#10b981', '#f43f5e'],
            legend: { position: 'bottom', fontSize: '12px' },
            dataLabels: { enabled: true, formatter: (val) => Math.round(val) + '%' },
            plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'Today', formatter: () => (todayReceipts + todayExpenses).toLocaleString() } } } } },
            stroke: { width: 0 },
        }).render();

        // ── Branch Cash on Hand ──
        new ApexCharts(document.getElementById('{{ $widgetId }}_branch'), {
            series: [{ name: 'Cash on Hand', data: branchAmounts }],
            chart: { type: 'bar', height: 230, toolbar: { show: false } },
            plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '55%' } },
            colors: ['#10b981'],
            xaxis: { categories: branchNames, labels: { style: { fontSize: '11px' } } },
            dataLabels: { enabled: true, style: { fontSize: '11px' } },
            grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
        }).render();

        // ── 6-Month Trend ──
        new ApexCharts(document.getElementById('{{ $widgetId }}_trend'), {
            series: [
                { name: 'Receipts', data: trendReceipts },
                { name: 'Payments', data: trendPayments },
            ],
            chart: { type: 'bar', height: 230, toolbar: { show: false } },
            plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
            colors: ['#6366f1', '#f59e0b'],
            xaxis: { categories: trendLabels, labels: { style: { fontSize: '11px' } } },
            yaxis: { labels: { style: { fontSize: '11px' } } },
            dataLabels: { enabled: false },
            grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
            legend: { fontSize: '12px' },
        }).render();
    }

    if (typeof ApexCharts !== 'undefined') {
        initCharts();
    } else {
        var scriptTag = document.createElement('script');
        scriptTag.src = '{{ asset("admin/assets/js/apexcharts/apexcharts.min.js") }}';
        scriptTag.onload = initCharts;
        document.head.appendChild(scriptTag);
    }
})();
</script>
@endif
