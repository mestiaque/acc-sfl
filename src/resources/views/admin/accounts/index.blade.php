@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Accounts') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 p-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fa-solid fa-wallet mr-2 text-primary"></i>Accounts</h4>
            <div>
                <a href="{{ route('acc-sfl.accounts.print', request()->query()) }}" target="_blank" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-print mr-1"></i> Print
                </a>
                <a href="{{ route('acc-sfl.accounts.export', request()->query()) }}" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-file-excel mr-1"></i> Export Excel
                </a>
                @can('ac_account.add')
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-toggle="modal" data-target="#createAccountModal">
                    <i class="fa-solid fa-plus"></i> Add Account
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @include('acc-sfl::admin.partials.alerts')

            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search name, employee ID, designation">
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
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-secondary btn-sm w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('acc-sfl.accounts.index') }}" class="btn btn-light btn-sm w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive mt-2">
                <table id="accountsTable" class="table table-bordered table-sm mb-0" style="width:100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Branch</th>
                            <th>Employee ID</th>
                            <th>Designation</th>
                            <th>Current Balance</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($accounts as $account)
                        <tr>
                            <td>{{ $account->name }}</td>
                            <td>{{ $account->branch->name }}</td>
                            <td>{{ $account->employee_id ?: '-' }}</td>
                            <td>{{ $account->designation ?: '-' }}</td>
                            <td>{{ number_format($account->current_balance, 2) }}</td>
                            <td>
                                <span class="badge {{ $account->is_active ? 'badge-success' : 'badge-secondary' }}">{{ $account->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn-custom" title="View" data-toggle="modal" data-target="#viewAccountModal"
                                    data-name="{{ $account->name }}"
                                    data-employee-id="{{ $account->employee_id }}"
                                    data-designation="{{ $account->designation }}"
                                    data-branch="{{ $account->branch->name }}"
                                    data-user="{{ $account->user->name ?? '-' }}"
                                    data-opening-balance="{{ number_format($account->opening_balance, 2) }}"
                                    data-current-balance="{{ number_format($account->current_balance, 2) }}"
                                    data-status="{{ $account->is_active ? 'Active' : 'Inactive' }}">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                @can('ac_account.edit')
                                <button type="button" class="btn-custom yellow" title="Edit" data-toggle="modal" data-target="#editAccountModal"
                                    data-action="{{ route('acc-sfl.accounts.update', $account) }}"
                                    data-name="{{ $account->name }}"
                                    data-employee-id="{{ $account->employee_id }}"
                                    data-designation="{{ $account->designation }}"
                                    data-branch-id="{{ $account->branch_id }}"
                                    data-user-id="{{ $account->user_id }}"
                                    data-current-balance="{{ number_format($account->current_balance, 2) }}"
                                    data-active="{{ $account->is_active ? '1' : '0' }}">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                @endcan
                                @can('ac_account.delete')
                                <button type="button" class="btn-custom danger" title="Delete" data-toggle="modal" data-target="#deleteAccountModal"
                                    data-action="{{ route('acc-sfl.accounts.destroy', $account) }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $accounts->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="createAccountModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('acc-sfl.accounts.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Account</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Employee ID</label>
                        <input type="text" name="employee_id" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Designation</label>
                        <input type="text" name="designation" class="form-control">
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
                        <label>Linked User (optional)</label>
                        <select name="user_id" class="form-control">
                            <option value="">-- None --</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Opening Balance <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="opening_balance" class="form-control" required>
                        <small class="form-text text-muted">Posts an automatic Opening Balance transaction when the account is created.</small>
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="is_active" value="0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="create_account_is_active" name="is_active" value="1" checked>
                            <label class="custom-control-label" for="create_account_is_active">Active</label>
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

{{-- Edit Modal (single reusable instance, populated via JS) --}}
<div class="modal fade" id="editAccountModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="editAccountForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Account</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_account_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Employee ID</label>
                        <input type="text" name="employee_id" id="edit_account_employee_id" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Designation</label>
                        <input type="text" name="designation" id="edit_account_designation" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" id="edit_account_branch_id" class="form-control" required>
                            <option value="">-- Select Branch --</option>
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Linked User (optional)</label>
                        <select name="user_id" id="edit_account_user_id" class="form-control">
                            <option value="">-- None --</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Current Balance (system-managed)</label>
                        <input type="text" id="edit_account_current_balance" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="is_active" value="0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="edit_account_is_active" name="is_active" value="1">
                            <label class="custom-control-label" for="edit_account_is_active">Active</label>
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

{{-- View Modal --}}
<div class="modal fade" id="viewAccountModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Account Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm">
                    <tr><th>Name</th><td id="view_account_name"></td></tr>
                    <tr><th>Employee ID</th><td id="view_account_employee_id"></td></tr>
                    <tr><th>Designation</th><td id="view_account_designation"></td></tr>
                    <tr><th>Branch</th><td id="view_account_branch"></td></tr>
                    <tr><th>Linked User</th><td id="view_account_user"></td></tr>
                    <tr><th>Opening Balance</th><td id="view_account_opening_balance"></td></tr>
                    <tr><th>Current Balance</th><td id="view_account_current_balance"></td></tr>
                    <tr><th>Status</th><td id="view_account_status"></td></tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@include('acc-sfl::admin.partials.delete-confirm-modal', ['modalId' => 'deleteAccountModal', 'label' => 'account'])
@endsection

@push('js')
@include('acc-sfl::admin.partials.select2-init')
<script>
    $(function () {
        $('#editAccountModal').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget);
            $(this).find('form').attr('action', btn.data('action'));
            $('#edit_account_name').val(btn.data('name'));
            $('#edit_account_employee_id').val(btn.data('employee-id'));
            $('#edit_account_designation').val(btn.data('designation'));
            $('#edit_account_branch_id').val(btn.data('branch-id')).trigger('change');
            $('#edit_account_user_id').val(btn.data('user-id')).trigger('change');
            $('#edit_account_current_balance').val(btn.data('current-balance'));
            $('#edit_account_is_active').prop('checked', btn.data('active') == 1);
        });

        $('#viewAccountModal').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget);
            $('#view_account_name').text(btn.data('name'));
            $('#view_account_employee_id').text(btn.data('employee-id') || '-');
            $('#view_account_designation').text(btn.data('designation') || '-');
            $('#view_account_branch').text(btn.data('branch'));
            $('#view_account_user').text(btn.data('user') || '-');
            $('#view_account_opening_balance').text(btn.data('opening-balance'));
            $('#view_account_current_balance').text(btn.data('current-balance'));
            $('#view_account_status').text(btn.data('status'));
        });
    });
</script>
@endpush
