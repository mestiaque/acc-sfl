@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Accounting Dashboard') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 p-4">
    @include('acc-sfl::admin.partials.alerts')

    <style>
        .ac-stat-card { background: #fff; border-radius: 12px; padding: 20px 18px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 12px rgba(0,0,0,.07); border: none; height: 100%; }
        .ac-stat-icon { width: 54px; height: 54px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
        .ac-stat-val { font-size: 24px; font-weight: 700; line-height: 1; margin-bottom: 3px; }
        .ac-stat-lbl { font-size: 12px; color: #888; font-weight: 500; text-transform: uppercase; letter-spacing: .5px; }
        .ac-card { background: #fff; border-radius: 12px; padding: 18px 20px; box-shadow: 0 2px 12px rgba(0,0,0,.07); height: 100%; }
        .ac-section-title { font-size: 13px; font-weight: 700; color: #444; text-transform: uppercase; letter-spacing: 1px; border-left: 3px solid #6366f1; padding-left: 10px; margin-bottom: 16px; }
        .ac-quick-btn { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 10px; background: #f8f9ff; border: 1px solid #e8eaf0; color: #444; font-size: 13px; font-weight: 500; text-decoration: none; transition: all .2s; }
        .ac-quick-btn:hover { background: #6366f1; color: #fff; border-color: #6366f1; }
        .ac-quick-btn i { width: 20px; text-align: center; }
    </style>

    <div class="d-flex align-items-center justify-content-between mb-3 mt-1">
        <h4 class="mb-0" style="font-size:17px;font-weight:700;">
            <i class="fa-solid fa-gauge mr-2" style="color:#6366f1;"></i>Accounting Dashboard
        </h4>
        <div>
            <a href="{{ route('acc-sfl.reports.cash-flow.monthly') }}" class="btn btn-sm btn-outline-secondary" style="font-size:12px;">
                <i class="fa-solid fa-calendar-days mr-1"></i> Monthly Cash Flow
            </a>
            <a href="{{ route('acc-sfl.reports.cash-flow.yearly') }}" class="btn btn-sm btn-outline-secondary" style="font-size:12px;">
                <i class="fa-solid fa-calendar-week mr-1"></i> Yearly Overview
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="ac-stat-card">
                <div class="ac-stat-icon" style="background:#eef2ff;"><i class="fa-solid fa-sack-dollar" style="color:#6366f1;"></i></div>
                <div>
                    <div class="ac-stat-val" style="color:#6366f1;">{{ number_format($totalCashOnHand, 2) }}</div>
                    <div class="ac-stat-lbl">Total Cash on Hand</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ac-stat-card">
                <div class="ac-stat-icon" style="background:#ecfdf5;"><i class="fa-solid fa-hand-holding-dollar" style="color:#10b981;"></i></div>
                <div>
                    <div class="ac-stat-val" style="color:#10b981;">{{ number_format($todaysReceipts, 2) }}</div>
                    <div class="ac-stat-lbl">Today's Receipts</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ac-stat-card">
                <div class="ac-stat-icon" style="background:#fffbeb;"><i class="fa-solid fa-money-bill-wave" style="color:#f59e0b;"></i></div>
                <div>
                    <div class="ac-stat-val" style="color:#f59e0b;">{{ number_format($todaysExpenses, 2) }}</div>
                    <div class="ac-stat-lbl">Today's Expenses</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ac-stat-card">
                <div class="ac-stat-icon" style="background:#fff1f2;"><i class="fa-solid fa-file-invoice-dollar" style="color:#f43f5e;"></i></div>
                <div>
                    <div class="ac-stat-val" style="color:#f43f5e;">{{ $pendingIouCount }}</div>
                    <div class="ac-stat-lbl">Pending IOUs</div>
                    <div class="text-muted" style="font-size:11px;">{{ number_format($pendingIouAmount, 2) }} outstanding</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="ac-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="ac-section-title mb-0">Recent Transactions</div>
                    <a href="{{ route('acc-sfl.transactions.index') }}" style="font-size:12px;color:#6366f1;text-decoration:none;">View All &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Account</th>
                                <th>Debit</th>
                                <th>Credit</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $transaction)
                            <tr>
                                <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                                <td>{{ $transaction->transaction_type }}</td>
                                <td>{{ $transaction->account->name }}</td>
                                <td class="text-success">{{ $transaction->debit > 0 ? number_format($transaction->debit, 2) : '-' }}</td>
                                <td class="text-danger">{{ $transaction->credit > 0 ? number_format($transaction->credit, 2) : '-' }}</td>
                                <td>{{ number_format($transaction->balance, 2) }}</td>
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
            <div class="ac-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="ac-section-title mb-0">Account Balances</div>
                    <a href="{{ route('acc-sfl.accounts.index') }}" style="font-size:12px;color:#6366f1;text-decoration:none;">Manage &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th>Branch</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accountBalances as $account)
                            <tr>
                                <td>{{ $account->name }}</td>
                                <td>{{ $account->branch->name }}</td>
                                <td>{{ number_format($account->current_balance, 2) }}</td>
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

    <div class="row g-3">
        <div class="col-12">
            <div class="ac-card">
                <div class="ac-section-title">Quick Links</div>
                <div class="d-flex flex-wrap gap-2">
                    @can('ac_branch.list')<a href="{{ route('acc-sfl.branches.index') }}" class="ac-quick-btn"><i class="fa-solid fa-code-branch"></i> Branches</a>@endcan
                    @can('ac_account.list')<a href="{{ route('acc-sfl.accounts.index') }}" class="ac-quick-btn"><i class="fa-solid fa-wallet"></i> Accounts</a>@endcan
                    @can('ac_payment_method.list')<a href="{{ route('acc-sfl.payment-methods.index') }}" class="ac-quick-btn"><i class="fa-solid fa-credit-card"></i> Payment Methods</a>@endcan
                    @can('ac_balance_receive.list')<a href="{{ route('acc-sfl.balance-receives.index') }}" class="ac-quick-btn"><i class="fa-solid fa-hand-holding-dollar"></i> Balance Receive</a>@endcan
                    @can('ac_expense.list')<a href="{{ route('acc-sfl.expenses.index') }}" class="ac-quick-btn"><i class="fa-solid fa-money-bill-wave"></i> Expenses</a>@endcan
                    @can('ac_expense_iou.list')<a href="{{ route('acc-sfl.expense-ious.index') }}" class="ac-quick-btn"><i class="fa-solid fa-file-invoice-dollar"></i> Expense IOU</a>@endcan
                    @can('ac_transaction.list')<a href="{{ route('acc-sfl.transactions.index') }}" class="ac-quick-btn"><i class="fa-solid fa-right-left"></i> Transactions</a>@endcan
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
