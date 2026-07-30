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
                <a href="{{ route('acc-sfl.expense-ious.print', request()->query()) }}" target="_blank" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-print mr-1"></i> Print
                </a>
                <a href="{{ route('acc-sfl.expense-ious.export', request()->query()) }}" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-file-excel mr-1"></i> Export Excel
                </a>
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
                <div class="col-md-3">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search IOU no.">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Branch</label>
                    <select name="branch_id" class="form-control form-control-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Status</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All Status</option>
                        <option value="Pending" @selected(request('status') === 'Pending')>Pending</option>
                        <option value="Adjusted" @selected(request('status') === 'Adjusted')>Adjusted</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-secondary btn-sm w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('acc-sfl.expense-ious.index') }}" class="btn btn-light btn-sm w-100">Reset</a>
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
                            <td>{{ $iou->employee->name ?? $iou->receiver_name ?? '-' }}</td>
                            <td>{{ number_format($iou->amount, 2) }}</td>
                            <td>
                                <span class="badge {{ $iou->status === 'Adjusted' ? 'badge-success' : 'badge-warning' }} p-1">{{ $iou->status }}</span>
                            </td>
                            <td class="text-right" width="150">
                                @can('ac_expense_iou.edit')
                                    @if($iou->status === 'Pending')
                                    <button type="button" class="btn-custom success" title="Adjust" data-toggle="modal" data-target="#adjustIouModal"
                                        data-action="{{ route('acc-sfl.expense-ious.adjust', $iou) }}" data-no="{{ $iou->iou_no }}">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                    @endif
                                @endcan
                                <button type="button" class="btn-custom" title="View" data-toggle="modal" data-target="#viewIouModal"
                                    data-no="{{ $iou->iou_no }}" data-issue-date="{{ $iou->issue_date->format('d M Y') }}"
                                    data-adjust-date="{{ $iou->adjust_date?->format('d M Y') ?? '-' }}"
                                    data-branch="{{ $iou->branch->name }}" data-account="{{ $iou->account->name }}"
                                    data-employee="{{ $iou->employee->name ?? '-' }}" data-payment-method="{{ $iou->paymentMethod->name }}"
                                    data-amount="{{ number_format($iou->amount, 2) }}" data-status="{{ $iou->status }}"
                                    data-description="{{ $iou->description }}" data-receiver-name="{{ $iou->receiver_name }}"
                                    data-receiver-mobile="{{ $iou->receiver_mobile }}">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                @can('ac_expense_iou.edit')
                                <button type="button" class="btn-custom yellow" title="Edit" data-toggle="modal" data-target="#editIouModal"
                                    data-action="{{ route('acc-sfl.expense-ious.update', $iou) }}"
                                    data-description="{{ $iou->description }}" data-receiver-name="{{ $iou->receiver_name }}"
                                    data-receiver-mobile="{{ $iou->receiver_mobile }}">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                @endcan
                                @can('ac_expense_iou.delete')
                                <button type="button" class="btn-custom danger" title="Delete" data-toggle="modal" data-target="#deleteIouModal"
                                    data-action="{{ route('acc-sfl.expense-ious.destroy', $iou) }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
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
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('acc-sfl.expense-ious.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Issue Expense IOU</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Issue Date <span class="text-danger">*</span></label>
                        <input type="date" name="issue_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="form-group">
                        <label>Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-control" required>
                            <option value="">-- Select --</option>
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($branches->count() === 1)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
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
                    <div class="form-group">
                        <label>Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method_id" class="form-control" required>
                            <option value="">-- Select --</option>
                            @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Employee (optional)</label>
                        <select name="employee_id" class="form-control">
                            <option value="">-- None --</option>
                            @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->employee_id }} - {{ $employee->name }}{{ $employee->department ? ' ('.$employee->department->name.(($employee->designation) ? ' / '.$employee->designation->name : '').')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Receiver Name</label>
                        <input type="text" name="receiver_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Receiver Mobile</label>
                        <input type="text" name="receiver_mobile" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
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
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="editIouForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Expense IOU</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Account, amount and dates are locked after issue. Only receiver/description can be updated.</p>
                    <div class="form-group">
                        <label>Receiver Name</label>
                        <input type="text" name="receiver_name" id="edit_iou_receiver_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Receiver Mobile</label>
                        <input type="text" name="receiver_mobile" id="edit_iou_receiver_mobile" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="edit_iou_description" class="form-control" rows="2"></textarea>
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
                    <p class="text-muted small mb-0">This marks the IOU as Adjusted/closed and logs an audit entry in Transactions.</p>
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
    <div class="modal-dialog" role="document">
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
                    <tr><th>Receiver</th><td id="view_iou_receiver"></td></tr>
                    <tr><th>Description</th><td id="view_iou_description"></td></tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@include('acc-sfl::admin.partials.delete-confirm-modal', ['modalId' => 'deleteIouModal', 'label' => 'expense IOU'])
@endsection

@push('js')
@include('acc-sfl::admin.partials.select2-init')
<script>
    $(function () {
        $('#editIouModal').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget);
            $(this).find('form').attr('action', btn.data('action'));
            $('#edit_iou_receiver_name').val(btn.data('receiver-name'));
            $('#edit_iou_receiver_mobile').val(btn.data('receiver-mobile'));
            $('#edit_iou_description').val(btn.data('description'));
        });

        $('#adjustIouModal').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget);
            $('#adjustIouForm').attr('action', btn.data('action'));
            $('#adjust_iou_no').text(btn.data('no'));
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
            var receiver = btn.data('receiver-name') || '-';
            if (btn.data('receiver-mobile')) { receiver += ' (' + btn.data('receiver-mobile') + ')'; }
            $('#view_iou_receiver').text(receiver);
            $('#view_iou_description').text(btn.data('description') || '-');
        });
    });
</script>
@endpush
