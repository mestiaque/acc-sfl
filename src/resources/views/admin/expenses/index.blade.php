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
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-toggle="modal" data-target="#createExpenseModal">
                    <i class="fa-solid fa-plus"></i> Add Expense
                </button>
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
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control form-control-sm">
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
                            <th>Total Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expenses as $expense)
                        @php($detail = $expense->details->first())
                        <tr>
                            <td>{{ $expense->expense_no }}</td>
                            <td>{{ $expense->expense_date->format('d M Y') }}</td>
                            <td>{{ $expense->branch->name }}</td>
                            <td>{{ $expense->account->name }}</td>
                            <td>{{ $detail && $detail->particular ? $detail->particular->code.' - '.$detail->particular->name : '-' }}</td>
                            <td>{{ $expense->paymentMethod->name }}</td>
                            <td>{{ number_format($expense->total_amount, 2) }}</td>
                            <td class="text-center">
                                <button type="button" class="btn-custom btn-view-expense" title="View" data-url="{{ route('acc-sfl.expenses.show', $expense) }}">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <a class="btn-custom" title="Print Slip" target="_blank" href="{{ route('acc-sfl.expenses.slip', $expense) }}">
                                    <i class="fa-solid fa-print"></i>
                                </a>
                                @can('ac_expense.edit')
                                <button type="button" class="btn-custom yellow" title="Edit" data-toggle="modal" data-target="#editExpenseModal"
                                    data-action="{{ route('acc-sfl.expenses.update', $expense) }}"
                                    data-company="{{ $expense->company_name }}" data-receiver-name="{{ $expense->receiver_name }}"
                                    data-receiver-mobile="{{ $expense->receiver_mobile }}" data-description="{{ $expense->description }}">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                @endcan
                                @can('ac_expense.delete')
                                <button type="button" class="btn-custom danger" title="Delete" data-toggle="modal" data-target="#deleteExpenseModal"
                                    data-action="{{ route('acc-sfl.expenses.destroy', $expense) }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
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

{{-- Create Modal --}}
<div class="modal fade" id="createExpenseModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('acc-sfl.expenses.store') }}" enctype="multipart/form-data" id="createExpenseForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Expense</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Expense Date <span class="text-danger">*</span></label>
                            <input type="date" name="expense_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Branch <span class="text-danger">*</span></label>
                            <select name="branch_id" class="form-control" required>
                                <option value="">-- Select --</option>
                                @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($branches->count() === 1)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
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
                        <div class="col-md-6 form-group">
                            <label>Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method_id" class="form-control" required>
                                <option value="">-- Select --</option>
                                @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Company Name</label>
                            <input type="text" name="company_name" class="form-control">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Receiver Name</label>
                            <input type="text" name="receiver_name" class="form-control">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Receiver Mobile</label>
                            <input type="text" name="receiver_mobile" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Particular <span class="text-danger">*</span></label>
                        <select name="items[0][particular_id]" class="form-control" required>
                            <option value="">-- Select --</option>
                            @foreach($particulars as $master)
                            <optgroup label="{{ $master->name }}">
                                @foreach($master->particulars as $particular)
                                <option value="{{ $particular->id }}">{{ $particular->code ? "{$particular->code} - " : '' }}{{ $particular->name }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Qty <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="items[0][qty]" class="form-control item-qty" value="1" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Rate <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="items[0][rate]" class="form-control item-rate" value="0" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Amount</label>
                            <input type="text" class="form-control item-amount" value="0.00" readonly>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Invoice</label>
                            <input type="text" name="items[0][invoice]" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>UOM</label>
                            <input type="text" name="items[0][uom]" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" data-tinymce="1"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Attachment</label>
                        <input type="file" name="attachment" class="form-control-file">
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
@endsection

@push('js')
@include('acc-sfl::admin.partials.select2-init')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.5/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    function acRecalculateExpenseTotal() {
        var qty = parseFloat($('#createExpenseModal .item-qty').val()) || 0;
        var rate = parseFloat($('#createExpenseModal .item-rate').val()) || 0;
        $('#createExpenseModal .item-amount').val((qty * rate).toFixed(2));
    }

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
        $('#createExpenseModal').on('input', '.item-qty, .item-rate', function () {
            acRecalculateExpenseTotal();
        });

        $('#createExpenseModal').on('hidden.bs.modal', function () {
            document.getElementById('createExpenseForm').reset();
            $(this).find('select').trigger('change');
            acRecalculateExpenseTotal();
        });

        $('#createExpenseModal, #editExpenseModal').on('shown.bs.modal', function () {
            acInitExpenseRichText(this);
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
            $('#edit_expense_description').val(btn.data('description'));
        });
    });
</script>
@endpush
