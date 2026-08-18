@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Balance Receive') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 p-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fa-solid fa-hand-holding-dollar mr-2 text-success"></i>Balance Receive</h4>
            <div>
                <a href="{{ route('acc-sfl.balance-receives.print', request()->query()) }}" target="_blank" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-print mr-1"></i> Print
                </a>
                <a href="{{ route('acc-sfl.balance-receives.export', request()->query()) }}" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-file-excel mr-1"></i> Export Excel
                </a>
                @can('ac_balance_receive.import')
                <a href="{{ route('acc-sfl.import-template.download') }}" class="btn btn-light btn-sm d-none">
                    <i class="fa-solid fa-download mr-1"></i> Download Import Template
                </a>
                <a href="{{ route('acc-sfl.balance-receives.import') }}" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-file-import mr-1"></i> Balance Import
                </a>
                @endcan
                @can('ac_balance_receive.add')
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-toggle="modal" data-target="#createReceiveModal">
                    <i class="fa-solid fa-plus"></i> Add Balance Receive
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @include('acc-sfl::admin.partials.alerts')

            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-3 mb-2">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search receive no.">
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
                    <a href="{{ route('acc-sfl.balance-receives.index') }}" class="btn btn-light btn-sm w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive mt-2">
                <table id="receivesTable" class="table table-bordered table-sm mb-0" style="width:100%">
                    <thead>
                        <tr>
                            <th>Receive No.</th>
                            <th>Date</th>
                            <th>Branch</th>
                            <th>Account</th>
                            <th>Particular</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($balanceReceives as $receive)
                        <tr>
                            <td>{{ $receive->receive_no }}</td>
                            <td>{{ $receive->receive_date->format('d M Y') }}</td>
                            <td>{{ $receive->branch->name }}</td>
                            <td>{{ $receive->account->name }}</td>
                            <td>{{ $receive->particular->code }} - {{ $receive->particular->name }}</td>
                            <td>{{ number_format($receive->amount, 2) }}</td>
                            <td>
                                <span class="badge {{ ['pending' => 'badge-warning', 'approved' => 'badge-success', 'rejected' => 'badge-danger'][$receive->status] ?? 'badge-secondary' }} p-1">{{ ucfirst($receive->status) }}</span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn-custom" title="View" data-toggle="modal" data-target="#viewReceiveModal"
                                    data-no="{{ $receive->receive_no }}" data-date="{{ $receive->receive_date->format('d M Y') }}"
                                    data-branch="{{ $receive->branch->name }}" data-account="{{ $receive->account->name }}"
                                    data-particular="{{ $receive->particular->code }} - {{ $receive->particular->name }}" data-amount="{{ number_format($receive->amount, 2) }}"
                                    data-description="{{ $receive->description }}"
                                    data-attachment="{{ $receive->attachment ? \Illuminate\Support\Facades\Storage::url($receive->attachment) : '' }}"
                                    data-creator="{{ $receive->creator->name ?? '-' }}">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                @can('ac_balance_receive.approve')
                                @if($receive->status === 'pending')
                                <button type="button" class="btn-custom success" title="Approve" data-toggle="modal" data-target="#approveReceiveModal"
                                    data-action="{{ route('acc-sfl.balance-receives.approve', $receive) }}">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <button type="button" class="btn-custom danger" title="Reject" data-toggle="modal" data-target="#rejectReceiveModal"
                                    data-action="{{ route('acc-sfl.balance-receives.reject', $receive) }}">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                                @endif
                                @endcan
                                @can('ac_balance_receive.edit')
                                <button type="button" class="btn-custom yellow" title="Edit" data-toggle="modal" data-target="#editReceiveModal"
                                    data-action="{{ route('acc-sfl.balance-receives.update', $receive) }}"
                                    data-description="{{ $receive->description }}">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                @endcan
                                @can('ac_balance_receive.delete')
                                @if($receive->status !== 'approved')
                                <button type="button" class="btn-custom danger" title="Delete" data-toggle="modal" data-target="#deleteReceiveModal"
                                    data-action="{{ route('acc-sfl.balance-receives.destroy', $receive) }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                @endif
                                @endcan
                                @can('ac_balance_receive.force_delete')
                                @if($receive->status === 'approved')
                                <button type="button" class="btn-custom danger d-none" title="Force Delete (reverses posted transaction)" data-toggle="modal" data-target="#forceDeleteReceiveModal"
                                    data-action="{{ route('acc-sfl.balance-receives.force-delete', $receive) }}">
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
            {{ $balanceReceives->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="createReceiveModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('acc-sfl.balance-receives.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Balance Receive</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Receive Date <span class="text-danger">*</span></label>
                        <input type="date" name="receive_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="form-group">
                        <label>Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-control" required>
                            <option value="">-- Select Branch --</option>
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($branches->count() === 1)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Account <span class="text-danger">*</span></label>
                        <select name="account_id" class="form-control" required @disabled($accounts->count() === 1)>
                            <option value="">-- Select Account --</option>
                            @foreach($accounts as $account)
                            <option value="{{ $account->id }}" @selected($accounts->count() === 1)>{{ $account->name }}</option>
                            @endforeach
                        </select>
                        @if($accounts->count() === 1)
                        <input type="hidden" name="account_id" value="{{ $accounts->first()->id }}">
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Particular <span class="text-danger">*</span></label>
                        <select name="particular_id" class="form-control" required>
                            <option value="">-- Select Particular --</option>
                            @foreach($particulars as $master)
                            <optgroup label="{{ $master->name }}">
                                @foreach($master->particulars as $particular)
                                <option value="{{ $particular->id }}">{{ $particular->code ? "{$particular->code} - " : '' }}{{ $particular->name }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
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
<div class="modal fade" id="editReceiveModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="editReceiveForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Balance Receive</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Date, branch, account, particular and amount are locked after the entry posts to the ledger. Only description/attachment can be updated.</p>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="edit_receive_description" class="form-control" rows="2"></textarea>
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

{{-- View Modal --}}
<div class="modal fade" id="viewReceiveModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Balance Receive Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm">
                    <tr><th>Receive No.</th><td id="view_receive_no"></td></tr>
                    <tr><th>Date</th><td id="view_receive_date"></td></tr>
                    <tr><th>Branch</th><td id="view_receive_branch"></td></tr>
                    <tr><th>Account</th><td id="view_receive_account"></td></tr>
                    <tr><th>Particular</th><td id="view_receive_particular"></td></tr>
                    <tr><th>Amount</th><td id="view_receive_amount"></td></tr>
                    <tr><th>Description</th><td id="view_receive_description"></td></tr>
                    <tr><th>Attachment</th><td id="view_receive_attachment"></td></tr>
                    <tr><th>Recorded By</th><td id="view_receive_creator"></td></tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@include('acc-sfl::admin.partials.delete-confirm-modal', ['modalId' => 'deleteReceiveModal', 'label' => 'balance receive'])
@include('acc-sfl::admin.partials.delete-confirm-modal', [
    'modalId' => 'forceDeleteReceiveModal',
    'title' => 'Confirm Force Delete',
    'warning' => 'This balance receive has already been approved and posted to the ledger. Force deleting it will reverse the posted transaction (restoring the account balance) and PERMANENTLY delete it — this cannot be undone.',
    'buttonLabel' => 'Force Delete',
])

{{-- Approve Modal --}}
<div class="modal fade" id="approveReceiveModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="approveReceiveForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Approve Balance Receive</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Approving posts this balance receive to the ledger and adds it to the account balance. Continue?</p>
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
<div class="modal fade" id="rejectReceiveModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="rejectReceiveForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Balance Receive</h5>
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
<script>
    $(function () {
        $('#approveReceiveModal').on('show.bs.modal', function (event) {
            $(this).find('form').attr('action', $(event.relatedTarget).data('action'));
        });

        $('#rejectReceiveModal').on('show.bs.modal', function (event) {
            $(this).find('form').attr('action', $(event.relatedTarget).data('action'));
        });

        $('#editReceiveModal').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget);
            $(this).find('form').attr('action', btn.data('action'));
            $('#edit_receive_description').val(btn.data('description'));
        });

        $('#viewReceiveModal').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget);
            $('#view_receive_no').text(btn.data('no'));
            $('#view_receive_date').text(btn.data('date'));
            $('#view_receive_branch').text(btn.data('branch'));
            $('#view_receive_account').text(btn.data('account'));
            $('#view_receive_particular').text(btn.data('particular'));
            $('#view_receive_amount').text(btn.data('amount'));
            $('#view_receive_description').text(btn.data('description') || '-');
            var attachment = btn.data('attachment');
            $('#view_receive_attachment').html(attachment ? '<a href="' + attachment + '" target="_blank">View file</a>' : '-');
            $('#view_receive_creator').text(btn.data('creator'));
        });
    });
</script>
@endpush
