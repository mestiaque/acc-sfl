@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Expenses') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 p-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fa-solid fa-money-bill-wave mr-2 text-warning"></i>Expenses</h4>
            <div>
                <a href="{{ route('acc-sfl.expenses.print', request()->query()) }}" target="_blank" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-print mr-1"></i> Print
                </a>
                <a href="{{ route('acc-sfl.expenses.export', request()->query()) }}" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-file-excel mr-1"></i> Export Excel
                </a>
                @can('ac_expense.import')
                <a href="{{ route('acc-sfl.import-template.download') }}" class="btn btn-light btn-sm d-none">
                    <i class="fa-solid fa-download mr-1"></i> Download Import Template
                </a>
                <a href="{{ route('acc-sfl.expenses.import') }}" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-file-import mr-1"></i> Expense Import
                </a>
                @endcan
                @can('ac_expense.add')
                <a href="{{ route('acc-sfl.expenses.create') }}" class="btn btn-primary btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-plus"></i> Add Expense
                </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @include('acc-sfl::admin.partials.alerts')

            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-3 mb-2">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search expense no., company, receiver">
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
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">Payment Method</label>
                    <select name="payment_method_id" class="form-control form-control-sm">
                        <option value="">All Methods</option>
                        @foreach($paymentMethods as $method)
                        <option value="{{ $method->id }}" @selected((string) request('payment_method_id') === (string) $method->id)>{{ $method->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">Master Particular</label>
                    <select name="master_particular_id" class="form-control form-control-sm">
                        <option value="">All</option>
                        @foreach($particulars as $master)
                        <option value="{{ $master->id }}" @selected((string) request('master_particular_id') === (string) $master->id)>{{ $master->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="form-label mb-1">Particular</label>
                    <select name="particular_id" class="form-control form-control-sm">
                        <option value="">All Particulars</option>
                        @foreach($particulars as $master)
                        <optgroup label="{{ $master->name }}">
                            @foreach($master->particulars as $particular)
                            <option value="{{ $particular->id }}" @selected((string) request('particular_id') === (string) $particular->id)>{{ $particular->code }} - {{ $particular->name }}</option>
                            @endforeach
                        </optgroup>
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
                    <label class="form-label mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">Status</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All Statuses</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                        <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <button class="btn btn-secondary btn-sm w-100">Filter</button>
                </div>
                <div class="col-md-2 mb-2">
                    <a href="{{ route('acc-sfl.expenses.index') }}" class="btn btn-light btn-sm w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive mt-2">
                <table id="expensesTable" class="table table-bordered table-sm mb-0" style="width:100%">
                    <thead>
                        <tr>
                            <th>Expense No.</th>
                            <th>Date</th>
                            <th>Branch</th>
                            <th>Account</th>
                            <th>Particular</th>
                            <th>Payment Method</th>
                            <th>Employee</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expenses as $expense)
                        @php
                            $detail = $expense->details->first();
                        @endphp
                        <tr>
                            <td>{{ $expense->expense_no }}</td>
                            <td>{{ $expense->expense_date->format('d M Y') }}</td>
                            <td>{{ $expense->branch->name }}</td>
                            <td>{{ $expense->account->name }}</td>
                            <td>{{ $detail && $detail->particular ? $detail->particular->code.' - '.$detail->particular->name : '-' }}</td>
                            <td>{{ $expense->paymentMethod->name }}</td>
                            <td>{{ $expense->employee->name ?? '-' }}</td>
                            <td>{{ number_format($expense->total_amount, 2) }}</td>
                            <td>
                                <span class="badge {{ ['pending' => 'badge-warning', 'approved' => 'badge-success', 'rejected' => 'badge-danger'][$expense->status] ?? 'badge-secondary' }} p-1">{{ ucfirst($expense->status) }}</span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn-custom btn-view-expense" title="View" data-url="{{ route('acc-sfl.expenses.show', $expense) }}">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <a class="btn-custom" title="Print Slip" target="_blank" href="{{ route('acc-sfl.expenses.slip', $expense) }}">
                                    <i class="fa-solid fa-print"></i>
                                </a>
                                @can('ac_expense.approve')
                                @if($expense->status === 'pending')
                                <button type="button" class="btn-custom success" title="Approve" data-toggle="modal" data-target="#approveExpenseModal"
                                    data-action="{{ route('acc-sfl.expenses.approve', $expense) }}">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <button type="button" class="btn-custom danger" title="Reject" data-toggle="modal" data-target="#rejectExpenseModal"
                                    data-action="{{ route('acc-sfl.expenses.reject', $expense) }}">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                                @endif
                                @endcan
                                @can('ac_expense.edit')
                                @if($expense->status === 'pending')
                                <a class="btn-custom yellow" title="Edit" href="{{ route('acc-sfl.expenses.edit', $expense) }}">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                @else
                                <button type="button" class="btn-custom yellow" title="Edit" data-toggle="modal" data-target="#editExpenseModal"
                                    data-action="{{ route('acc-sfl.expenses.update', $expense) }}"
                                    data-company="{{ $expense->company_name }}" data-receiver-name="{{ $expense->receiver_name }}"
                                    data-receiver-mobile="{{ $expense->receiver_mobile }}" data-invoice="{{ $expense->invoice }}"
                                    data-employee-id="{{ $expense->employee_id }}" data-description="{{ $expense->description }}">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                @endif
                                @endcan
                                @can('ac_expense.delete')
                                @if($expense->status !== 'approved')
                                <button type="button" class="btn-custom danger" title="Delete" data-toggle="modal" data-target="#deleteExpenseModal"
                                    data-action="{{ route('acc-sfl.expenses.destroy', $expense) }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                @endif
                                @endcan
                                @can('ac_expense.force_delete')
                                @if($expense->status === 'approved')
                                <button type="button" class="btn-custom danger" title="Force Delete (reverses posted transaction)" data-toggle="modal" data-target="#forceDeleteExpenseModal"
                                    data-action="{{ route('acc-sfl.expenses.force-delete', $expense) }}">
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
            {{ $expenses->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

{{-- Edit Modal — financial fields are locked once a transaction has posted; only metadata is editable --}}
<div class="modal fade" id="editExpenseModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="editExpenseForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Expense</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Date, branch, account, payment method and line items are locked after the entry posts to the ledger. Only company/receiver/description/attachment can be updated.</p>
                    <div class="form-group">
                        <label>Company Name</label>
                        <input type="text" name="company_name" id="edit_expense_company" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Receiver Name</label>
                        <input type="text" name="receiver_name" id="edit_expense_receiver_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Receiver Mobile</label>
                        <input type="text" name="receiver_mobile" id="edit_expense_receiver_mobile" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Invoice</label>
                        <input type="text" name="invoice" id="edit_expense_invoice" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Employee</label>
                        <select name="employee_id" id="edit_expense_employee" class="form-control">
                            <option value="">-- None --</option>
                            @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->employee_id }} - {{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="edit_expense_description" class="form-control" rows="3" data-tinymce="1"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Attachment (upload to replace)</label>
                        <input type="file" name="attachment" class="form-control-file">
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

{{-- View Modal (content loaded via AJAX from the show route) --}}
<div class="modal fade" id="viewExpenseModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Expense Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="viewExpenseModalBody">
                <div class="text-center text-muted py-4">Loading...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@include('acc-sfl::admin.partials.delete-confirm-modal', ['modalId' => 'deleteExpenseModal', 'label' => 'expense'])
@include('acc-sfl::admin.partials.delete-confirm-modal', [
    'modalId' => 'forceDeleteExpenseModal',
    'title' => 'Confirm Force Delete',
    'warning' => 'This expense has already been approved and posted to the ledger. Force deleting it will reverse the posted transaction (restoring the account balance) and PERMANENTLY delete the expense — this cannot be undone.',
    'buttonLabel' => 'Force Delete',
])

{{-- Approve Modal --}}
<div class="modal fade" id="approveExpenseModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="approveExpenseForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Approve Expense</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Approving posts this expense to the ledger and deducts it from the account balance. Continue?</p>
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

{{-- Reject Modal --}}
<div class="modal fade" id="rejectExpenseModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="rejectExpenseForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Expense</h5>
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
@include('acc-sfl::admin.partials.select2-init')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.5/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    function acInitExpenseRichText(modal) {
        if (typeof tinymce === 'undefined') {
            return;
        }
        var selector = '#' + modal.id + ' textarea[data-tinymce="1"]';
        tinymce.remove(selector);
        tinymce.init({
            selector: selector,
            height: 150,
            menubar: false,
            branding: false,
            plugins: 'lists link code',
            toolbar: 'undo redo | bold italic underline | bullist numlist | link | code',
            statusbar: false,
        });
    }

    $(function () {
        $('#editExpenseModal').on('shown.bs.modal', function () {
            acInitExpenseRichText(this);
        });

        $('#approveExpenseModal').on('show.bs.modal', function (event) {
            $(this).find('form').attr('action', $(event.relatedTarget).data('action'));
        });

        $('#rejectExpenseModal').on('show.bs.modal', function (event) {
            $(this).find('form').attr('action', $(event.relatedTarget).data('action'));
        });

        $('.btn-view-expense').on('click', function () {
            var url = $(this).data('url');
            $('#viewExpenseModalBody').html('<div class="text-center text-muted py-4">Loading...</div>');
            $('#viewExpenseModal').modal('show');
            $.get(url, function (html) {
                $('#viewExpenseModalBody').html(html);
            }).fail(function () {
                $('#viewExpenseModalBody').html('<div class="text-center text-danger py-4">Failed to load details.</div>');
            });
        });

        $('#editExpenseModal').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget);
            $(this).find('form').attr('action', btn.data('action'));
            $('#edit_expense_company').val(btn.data('company'));
            $('#edit_expense_receiver_name').val(btn.data('receiver-name'));
            $('#edit_expense_receiver_mobile').val(btn.data('receiver-mobile'));
            $('#edit_expense_invoice').val(btn.data('invoice'));
            $('#edit_expense_employee').val(btn.data('employee-id') || '').trigger('change');
            $('#edit_expense_description').val(btn.data('description'));
        });
    });
</script>
@endpush
