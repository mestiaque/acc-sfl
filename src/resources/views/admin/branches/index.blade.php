@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Branches') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fa-solid fa-code-branch mr-2 text-primary"></i>Branches</h4>
            <div>
                <a href="{{ route('acc-sfl.branches.print', request()->query()) }}" target="_blank" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-print mr-1"></i> Print
                </a>
                <a href="{{ route('acc-sfl.branches.export', request()->query()) }}" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-file-excel mr-1"></i> Export Excel
                </a>
                @can('ac_branch.add')
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-toggle="modal" data-target="#createBranchModal">
                    <i class="fa-solid fa-plus"></i> Add Branch
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @include('acc-sfl::admin.partials.alerts')

            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search name, code, branch head">
                </div>
                <div class="col-md-3">
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
                    <a href="{{ route('acc-sfl.branches.index') }}" class="btn btn-light btn-sm w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive mt-2">
                <table id="branchesTable" class="table table-bordered table-sm mb-0" style="width:100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Location</th>
                            <th>Branch Head</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($branches as $branch)
                        <tr>
                            <td>{{ $branch->name }}</td>
                            <td>{{ $branch->code }}</td>
                            <td>{{ $branch->location ?: '-' }}</td>
                            <td>{{ $branch->branch_head ?: '-' }}</td>
                            <td>
                                <span class="badge {{ $branch->is_active ? 'badge-success' : 'badge-secondary' }}">{{ $branch->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn-custom" title="View" data-toggle="modal" data-target="#viewBranchModal"
                                    data-name="{{ $branch->name }}" data-code="{{ $branch->code }}"
                                    data-location="{{ $branch->location }}" data-head="{{ $branch->branch_head }}"
                                    data-status="{{ $branch->is_active ? 'Active' : 'Inactive' }}">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                @can('ac_branch.edit')
                                <button type="button" class="btn-custom yellow" title="Edit" data-toggle="modal" data-target="#editBranchModal"
                                    data-action="{{ route('acc-sfl.branches.update', $branch) }}"
                                    data-name="{{ $branch->name }}" data-code="{{ $branch->code }}"
                                    data-location="{{ $branch->location }}" data-head="{{ $branch->branch_head }}"
                                    data-active="{{ $branch->is_active ? '1' : '0' }}">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                @endcan
                                @can('ac_branch.delete')
                                <button type="button" class="btn-custom danger" title="Delete" data-toggle="modal" data-target="#deleteBranchModal"
                                    data-action="{{ route('acc-sfl.branches.destroy', $branch) }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $branches->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="createBranchModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('acc-sfl.branches.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Branch</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Branch Head</label>
                        <input type="text" name="branch_head" class="form-control">
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="is_active" value="0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="create_branch_is_active" name="is_active" value="1" checked>
                            <label class="custom-control-label" for="create_branch_is_active">Active</label>
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
<div class="modal fade" id="editBranchModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="editBranchForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Branch</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_branch_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" id="edit_branch_code" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" id="edit_branch_location" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Branch Head</label>
                        <input type="text" name="branch_head" id="edit_branch_head" class="form-control">
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="is_active" value="0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="edit_branch_is_active" name="is_active" value="1">
                            <label class="custom-control-label" for="edit_branch_is_active">Active</label>
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
<div class="modal fade" id="viewBranchModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Branch Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm">
                    <tr><th>Name</th><td id="view_branch_name"></td></tr>
                    <tr><th>Code</th><td id="view_branch_code"></td></tr>
                    <tr><th>Location</th><td id="view_branch_location"></td></tr>
                    <tr><th>Branch Head</th><td id="view_branch_head"></td></tr>
                    <tr><th>Status</th><td id="view_branch_status"></td></tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@include('acc-sfl::admin.partials.delete-confirm-modal', ['modalId' => 'deleteBranchModal', 'label' => 'branch'])
@endsection

@push('js')
@include('acc-sfl::admin.partials.select2-init')
<script>
    $(function () {
        $('#editBranchModal').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget);
            $(this).find('form').attr('action', btn.data('action'));
            $('#edit_branch_name').val(btn.data('name'));
            $('#edit_branch_code').val(btn.data('code'));
            $('#edit_branch_location').val(btn.data('location'));
            $('#edit_branch_head').val(btn.data('head'));
            $('#edit_branch_is_active').prop('checked', btn.data('active') == 1);
        });

        $('#viewBranchModal').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget);
            $('#view_branch_name').text(btn.data('name'));
            $('#view_branch_code').text(btn.data('code'));
            $('#view_branch_location').text(btn.data('location') || '-');
            $('#view_branch_head').text(btn.data('head') || '-');
            $('#view_branch_status').text(btn.data('status'));
        });
    });
</script>
@endpush
