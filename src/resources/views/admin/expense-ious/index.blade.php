@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Expense IOU') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 p-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fa-solid fa-file-invoice-dollar mr-2 text-warning"></i>Expense IOU</h4>
            <div>
                <a href="{{ route('acc-sfl.expense-ious.print', request()->query()) }}" target="_blank" class="btn btn-light btn-sm btn-icon" title="Print"><i class="fa-solid fa-print"></i></a>
                <a href="{{ route('acc-sfl.expense-ious.export', request()->query()) }}" class="btn btn-light btn-sm btn-icon" title="Export Excel"><i class="fa-solid fa-file-excel"></i></a>
                @can('ac_expense_iou.add')
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-toggle="modal" data-target="#createIouModal">
                    <i class="fa-solid fa-plus"></i> Issue IOU
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @include('acc-sfl::admin.partials.alerts')

            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-3 mb-2">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search IOU no.">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">Branch</label>
                    <select name="branch_id" class="form-control form-control-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">Account</label>
                    <select name="account_id" class="form-control form-control-sm">
                        <option value="">All Accounts</option>
                        @foreach($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) request('account_id') === (string) $account->id)>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="form-label mb-1">Employee</label>
                    <select name="employee_id" class="form-control form-control-sm">
                        <option value="">All Employees</option>
                        @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((string) request('employee_id') === (string) $employee->id)>{{ $employee->employee_id }} - {{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">Status</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All Status</option>
                        <option value="pending" @selected(request('status') === 'pending')>Waiting Approval</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                        <option value="Pending" @selected(request('status') === 'Pending')>Pending</option>
                        <option value="Adjusted" @selected(request('status') === 'Adjusted')>Adjusted</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control form-control-sm">
                </div>
                @include('acc-sfl::admin.partials.sort-filter', ['noLabel' => 'IOU No.'])
                <div class="col-md-2">
                    <button class="btn btn-secondary btn-sm btn-icon" title="Filter"><i class="fa-solid fa-filter"></i></button>
                    <a href="{{ route('acc-sfl.expense-ious.index') }}" class="btn btn-light btn-sm btn-icon" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
                </div>
            </form>

            <div class="table-responsive mt-2">
                <table id="iousTable" class="table table-bordered table-sm mb-0" style="width:100%">
                    <thead>
                        <tr>
                            <th>IOU No.</th>
                            <th>Issue Date</th>
                            <th>Branch</th>
                            <th>Account</th>
                            <th>Employee</th>
                            <th>Amount</th>
                            <th>Spent</th>
                            <th>Return / Extra</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expenseIous as $iou)
                        <tr>
                            <td>{{ $iou->iou_no }}</td>
                            <td>{{ $iou->issue_date->format('d M Y') }}</td>
                            <td>{{ $iou->branch->name }}</td>
                            <td>{{ $iou->account->name }}</td>
                            <td>{{ $iou->employee->name ?? '-' }}</td>
                            @php
                                $spent = (float) ($iou->approved_expense_total ?? 0);
                                $difference = $iou->status === 'Adjusted' ? (float) $iou->settlement_amount : (float) $iou->amount - $spent;
                            @endphp
                            <td>{{ number_format($iou->amount, 2) }}</td>
                            <td>
                                {{ number_format($spent, 2) }}
                                @if($iou->pending_expense_count)
                                <br><small class="text-warning">{{ $iou->pending_expense_count }} pending approval</small>
                                @endif
                            </td>
                            <td>
                                @if($difference > 0)
                                <span class="text-success">{{ number_format($difference, 2) }} {{ $iou->status === 'Adjusted' ? 'returned' : 'to return' }}</span>
                                @elseif($difference < 0)
                                <span class="text-danger">{{ number_format(abs($difference), 2) }} {{ $iou->status === 'Adjusted' ? 'extra paid' : 'extra to pay' }}</span>
                                @else
                                -
                                @endif
                            </td>
                            <td>
                                @if($iou->approval_status === 'pending')
                                    <span class="badge badge-info p-1" title="Cash leaves the account once approved">Waiting Approval</span>
                                @elseif($iou->approval_status === 'rejected')
                                    <span class="badge badge-danger p-1" title="{{ $iou->approval_remarks }}">Rejected</span>
                                @else
                                    <span class="badge {{ $iou->status === 'Adjusted' ? 'badge-success' : 'badge-warning' }} p-1">{{ $iou->status }}</span>
                                @endif
                            </td>
                            <td class="text-right" width="150">
                                @if($iou->approval_status === 'pending')
                                    @if(view()->exists('components.approval-remind'))<x-approval-remind :model="$iou" />@endif
                                    @can('ac_expense_iou.approve')
                                    <button type="button" class="btn-custom success" title="Approve" data-toggle="modal" data-target="#approveIouModal"
                                        data-action="{{ route('acc-sfl.expense-ious.approve', $iou) }}" data-no="{{ $iou->iou_no }}" data-amount="{{ number_format($iou->amount, 2) }}">
                                        <i class="fa-solid fa-check-double"></i>
                                    </button>
                                    <button type="button" class="btn-custom danger" title="Reject" data-toggle="modal" data-target="#rejectIouModal"
                                        data-action="{{ route('acc-sfl.expense-ious.reject', $iou) }}" data-no="{{ $iou->iou_no }}">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                    @endcan
                                @endif
                                @can('ac_expense_iou.edit')
                                    @if($iou->status === 'Pending' && $iou->approval_status === 'approved')
                                    <button type="button" class="btn-custom success" data-toggle="modal" data-target="#adjustIouModal"
                                        title="{{ $iou->pending_expense_count ? 'Approve or reject its pending expenses first' : 'Adjust' }}" @disabled($iou->pending_expense_count)
                                        data-action="{{ route('acc-sfl.expense-ious.adjust', $iou) }}" data-no="{{ $iou->iou_no }}"
                                        data-amount="{{ (float) $iou->amount }}" data-spent="{{ $spent }}"
                                        data-payment-method-id="{{ $iou->payment_method_id }}"
                                        data-adjust-date="{{ $iou->adjust_date?->toDateString() }}">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                    @endif
                                @endcan
                                <button type="button" class="btn-custom success" title="View" data-toggle="modal" data-target="#viewIouModal"
                                    data-no="{{ $iou->iou_no }}" data-issue-date="{{ $iou->issue_date->format('d M Y') }}"
                                    data-adjust-date="{{ $iou->adjust_date?->format('d M Y') ?? '-' }}"
                                    data-branch="{{ $iou->branch->name }}" data-account="{{ $iou->account->name }}"
                                    data-employee="{{ $iou->employee->name ?? '-' }}" data-payment-method="{{ $iou->paymentMethod->name }}"
                                    data-amount="{{ number_format($iou->amount, 2) }}" data-status="{{ $iou->status }}"
                                    data-description="{{ $iou->description }}"
                                    data-attachments="{{ json_encode($iou->galleryFiles()) }}"
                                    data-expenses="{{ $iou->expenses->map(fn ($expense) => [
                                        'no' => $expense->expense_no,
                                        'date' => $expense->expense_date->format('d M Y'),
                                        'particulars' => $expense->details->map(fn ($d) => $d->particular?->name)->filter()->unique()->implode(', '),
                                        'amount' => (float) $expense->total_amount,
                                        'status' => $expense->status,
                                        'url' => route('acc-sfl.expenses.index', ['search' => $expense->expense_no]),
                                    ])->values()->toJson() }}">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                @can('ac_expense_iou.edit')
                                <button type="button" class="btn-custom yellow" title="Edit" data-toggle="modal" data-target="#editIouModal"
                                    data-action="{{ route('acc-sfl.expense-ious.update', $iou) }}"
                                    data-status="{{ $iou->status }}"
                                    data-issue-date="{{ $iou->issue_date->format('Y-m-d') }}"
                                    data-amount="{{ $iou->amount }}"
                                    data-employee-id="{{ $iou->employee_id }}"
                                    data-description="{{ $iou->description }}">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                @endcan
                                @can('ac_expense_iou.delete')
                                @if($iou->status !== 'Adjusted')
                                <button type="button" class="btn-custom danger" title="Delete" data-toggle="modal" data-target="#deleteIouModal"
                                    data-action="{{ route('acc-sfl.expense-ious.destroy', $iou) }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                @endif
                                @endcan
                                @can('ac_expense_iou.force_delete')
                                @if($iou->status === 'Adjusted')
                                <button type="button" class="btn-custom danger d-none" title="Force Delete (reverses posted transactions)" data-toggle="modal" data-target="#forceDeleteIouModal"
                                    data-action="{{ route('acc-sfl.expense-ious.force-delete', $iou) }}">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                                @endif
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $expenseIous->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

{{-- Create (Issue) Modal --}}
<div class="modal fade" id="createIouModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('acc-sfl.expense-ious.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Issue Expense IOU</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label>Issue Date <span class="text-danger">*</span></label>
                            <input type="date" name="issue_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Branch <span class="text-danger">*</span></label>
                            <select name="branch_id" class="form-control" required>
                                <option value="">-- Select --</option>
                                @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($branches->count() === 1)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Account <span class="text-danger">*</span></label>
                            <select name="account_id" class="form-control" required @disabled($accounts->count() === 1)>
                                <option value="">-- Select --</option>
                                @foreach($accounts as $account)
                                <option value="{{ $account->id }}" @selected($accounts->count() === 1)>{{ $account->name }}</option>
                                @endforeach
                            </select>
                            @if($accounts->count() === 1)
                            <input type="hidden" name="account_id" value="{{ $accounts->first()->id }}">
                            @endif
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method_id" class="form-control" required>
                                <option value="">-- Select --</option>
                                @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-control" required>
                                <option value="">-- Select --</option>
                                @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->employee_id }} - {{ $employee->name }}{{ $employee->department ? ' ('.$employee->department->name.(($employee->designation) ? ' / '.$employee->designation->name : '').')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="col-12 form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12 form-group">
                            <label>Attachments</label>
                            <input type="file" name="attachments[]" class="form-control-file" multiple>
                            <small class="form-text text-muted">You can select multiple files.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Modal — financial fields locked once issued; only metadata editable --}}
<div class="modal fade" id="editIouModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <form method="POST" id="editIouForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Expense IOU</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <p class="text-muted small" id="edit_iou_locked_note">Branch, account and payment method are locked after issue.</p>
                        </div>
                        <div class="col-12">
                            <p class="text-muted small" id="edit_iou_adjusted_note" style="display:none;">This IOU has been adjusted — only the description can be updated.</p>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Issue Date <span class="text-danger" id="edit_iou_issue_date_req">*</span></label>
                            <input type="date" name="issue_date" id="edit_iou_issue_date" class="form-control">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Amount <span class="text-danger" id="edit_iou_amount_req">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="edit_iou_amount" class="form-control">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Employee <span class="text-danger" id="edit_iou_employee_req">*</span></label>
                            <select name="employee_id" id="edit_iou_employee_id" class="form-control">
                                <option value="">-- Select --</option>
                                @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->employee_id }} - {{ $employee->name }}{{ $employee->department ? ' ('.$employee->department->name.(($employee->designation) ? ' / '.$employee->designation->name : '').')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 form-group">
                            <label>Description</label>
                            <textarea name="description" id="edit_iou_description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12 form-group">
                            <label>Attachments</label>
                            <input type="file" name="attachments[]" class="form-control-file" multiple>
                            <small class="form-text text-muted">You can select multiple files.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-sm">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Adjust Modal --}}
<div class="modal fade" id="adjustIouModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="adjustIouForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Adjust IOU <span id="adjust_iou_no"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Adjust Date <span class="text-danger">*</span></label>
                        <input type="date" name="adjust_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <table class="table table-sm mb-2">
                        <tr><th>IOU Amount</th><td class="text-right" id="adjust_iou_amount"></td></tr>
                        <tr><th>Spent (approved expenses)</th><td class="text-right" id="adjust_iou_spent"></td></tr>
                        <tr><th id="adjust_iou_result_label"></th><td class="text-right font-weight-bold" id="adjust_iou_result"></td></tr>
                    </table>
                    <div class="form-group" id="adjust_iou_payment_method_group">
                        <label id="adjust_iou_payment_method_label">Payment Method</label>
                        <select name="settlement_payment_method_id" id="adjust_iou_payment_method_id" class="form-control">
                            @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <p class="text-muted small mb-0" id="adjust_iou_note"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success btn-sm">Mark Adjusted</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View Modal --}}
<div class="modal fade" id="viewIouModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Expense IOU Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm">
                    <tr><th>IOU No.</th><td id="view_iou_no"></td></tr>
                    <tr><th>Issue Date</th><td id="view_iou_issue_date"></td></tr>
                    <tr><th>Adjust Date</th><td id="view_iou_adjust_date"></td></tr>
                    <tr><th>Branch</th><td id="view_iou_branch"></td></tr>
                    <tr><th>Account</th><td id="view_iou_account"></td></tr>
                    <tr><th>Employee</th><td id="view_iou_employee"></td></tr>
                    <tr><th>Payment Method</th><td id="view_iou_payment_method"></td></tr>
                    <tr><th>Amount</th><td id="view_iou_amount"></td></tr>
                    <tr><th>Status</th><td id="view_iou_status"></td></tr>
                    <tr><th>Description</th><td id="view_iou_description"></td></tr>
                    <tr><th>Expenses</th><td id="view_iou_expenses"></td></tr>
                    <tr><th>Attachments</th><td id="view_iou_attachments" style="min-width:280px;"></td></tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@include('acc-sfl::admin.partials.delete-confirm-modal', ['modalId' => 'deleteIouModal', 'label' => 'expense IOU'])
@include('acc-sfl::admin.partials.delete-confirm-modal', [
    'modalId' => 'forceDeleteIouModal',
    'title' => 'Confirm Force Delete',
    'warning' => 'This IOU has already been adjusted and posted to the ledger. Force deleting it will reverse its posted transactions (restoring the account balance) and PERMANENTLY delete the IOU — this cannot be undone.',
    'buttonLabel' => 'Force Delete',
])
{{-- Approve / Reject IOU --}}
<div class="modal fade" id="approveIouModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Approve IOU <span data-iou-no></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Approving posts the advance of <strong data-iou-amount></strong> to the ledger — it leaves the account now. Continue?</p>
                    <div class="form-group mb-0">
                        <label>Remarks (optional)</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success btn-sm"><i class="fa-solid fa-check mr-1"></i> Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="rejectIouModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject IOU <span data-iou-no></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-0">
                        <label>Remarks <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control" rows="2" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-xmark mr-1"></i> Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
@include('acc-sfl::admin.partials.attachment-gallery-assets')
@include('acc-sfl::admin.partials.select2-init')
<script>
    $(function () {
        $('#approveIouModal, #rejectIouModal').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget);
            $(this).find('form').attr('action', btn.data('action'));
            $(this).find('[data-iou-no]').text(btn.data('no'));
            $(this).find('[data-iou-amount]').text(btn.data('amount'));
        });
        $('#editIouModal').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget);
            $(this).find('form').attr('action', btn.data('action'));
            $('#edit_iou_description').val(btn.data('description'));

            var isPending = btn.data('status') === 'Pending';
            $('#edit_iou_issue_date').val(btn.data('issue-date'));
            $('#edit_iou_amount').val(btn.data('amount'));
            $('#edit_iou_employee_id').val(btn.data('employee-id')).trigger('change');

            $('#edit_iou_issue_date, #edit_iou_amount, #edit_iou_employee_id').prop('disabled', !isPending);
            $('#edit_iou_issue_date_req, #edit_iou_amount_req, #edit_iou_employee_req').toggle(isPending);
            $('#edit_iou_locked_note').toggle(isPending);
            $('#edit_iou_adjusted_note').toggle(!isPending);
        });

        function acIouEntries(amount, spent) {
            return 'Posts Debit ' + amount.toFixed(2) + ' (advance cleared)'
                + (spent > 0 ? ' and Credit ' + spent.toFixed(2) + ' (expenses)' : '') + '.';
        }

        $('#adjustIouModal').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget);
            $('#adjustIouForm').attr('action', btn.data('action'));
            $('#adjust_iou_no').text(btn.data('no'));
            // A reopened legacy IOU keeps its old adjust date - pre-fill it, else today.
            $('#adjustIouForm input[name="adjust_date"]').val(btn.data('adjust-date') || '{{ now()->toDateString() }}');

            var amount = parseFloat(btn.data('amount')) || 0;
            var spent = parseFloat(btn.data('spent')) || 0;
            var difference = Math.round((amount - spent) * 100) / 100;
            $('#adjust_iou_amount').text(amount.toFixed(2));
            $('#adjust_iou_spent').text(spent.toFixed(2));
            $('#adjust_iou_payment_method_id').val(String(btn.data('payment-method-id')));
            $('#adjust_iou_payment_method_group').toggle(difference !== 0);

            if (difference > 0) {
                $('#adjust_iou_result_label').text('Employee returns');
                $('#adjust_iou_result').text(difference.toFixed(2)).removeClass('text-danger').addClass('text-success');
                $('#adjust_iou_payment_method_label').text('Returned via');
                $('#adjust_iou_note').text(acIouEntries(amount, spent) + ' Net: ' + difference.toFixed(2) + ' added back to the account.');
            } else if (difference < 0) {
                $('#adjust_iou_result_label').text('Extra to pay employee');
                $('#adjust_iou_result').text(Math.abs(difference).toFixed(2)).removeClass('text-success').addClass('text-danger');
                $('#adjust_iou_payment_method_label').text('Paid via');
                $('#adjust_iou_note').text(acIouEntries(amount, spent) + ' Net: the extra ' + Math.abs(difference).toFixed(2) + ' is paid out of the account.');
            } else {
                $('#adjust_iou_result_label').text('Difference');
                $('#adjust_iou_result').text('0.00').removeClass('text-success text-danger');
                $('#adjust_iou_note').text(acIouEntries(amount, spent) + ' Net: no change, fully spent.');
            }
        });

        $('#viewIouModal').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget);
            $('#view_iou_no').text(btn.data('no'));
            $('#view_iou_issue_date').text(btn.data('issue-date'));
            $('#view_iou_adjust_date').text(btn.data('adjust-date'));
            $('#view_iou_branch').text(btn.data('branch'));
            $('#view_iou_account').text(btn.data('account'));
            $('#view_iou_employee').text(btn.data('employee'));
            $('#view_iou_payment_method').text(btn.data('payment-method'));
            $('#view_iou_amount').text(btn.data('amount'));
            $('#view_iou_status').text(btn.data('status'));
            $('#view_iou_description').text(btn.data('description') || '-');

            $('#view_iou_attachments').html(acGalleryHtml(btn.data('attachments') || []));

            // Expenses recorded "Against IOU" for this IOU; only approved ones count towards Spent.
            var expenses = btn.data('expenses') || [];
            var $expensesCell = $('#view_iou_expenses').empty();
            if (!expenses.length) {
                $expensesCell.text('No expense recorded against this IOU yet.');
            } else {
                var badges = { pending: 'badge-warning', approved: 'badge-success', rejected: 'badge-danger' };
                var $table = $('<table class="table table-sm table-bordered mb-0"><thead><tr><th>Expense No.</th><th>Date</th><th>Particular</th><th class="text-right">Amount</th><th>Status</th></tr></thead><tbody></tbody></table>');
                var approvedTotal = 0;
                expenses.forEach(function (expense) {
                    if (expense.status === 'approved') { approvedTotal += expense.amount; }
                    $('<tr>')
                        .append($('<td>').append($('<a>', { href: expense.url, target: '_blank', text: expense.no })))
                        .append($('<td>').text(expense.date))
                        .append($('<td>').text(expense.particulars || '-'))
                        .append($('<td class="text-right">').text(expense.amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })))
                        .append($('<td>').append($('<span class="badge p-1">').addClass(badges[expense.status] || 'badge-secondary').text(expense.status.charAt(0).toUpperCase() + expense.status.slice(1))))
                        .appendTo($table.find('tbody'));
                });
                $table.append($('<tfoot>').append($('<tr>')
                    .append($('<th colspan="3" class="text-right">').text('Approved total'))
                    .append($('<th class="text-right">').text(approvedTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })))
                    .append('<th></th>')));
                $expensesCell.append($table);
            }
        });
    });
</script>
@endpush
