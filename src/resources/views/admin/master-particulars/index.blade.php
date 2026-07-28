@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Master Particulars') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 p-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fa-solid fa-list-check mr-2 text-primary"></i>Master Particulars</h4>
            <div>
                <a href="{{ route('acc-sfl.master-particulars.print', request()->query()) }}" target="_blank" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-print mr-1"></i> Print
                </a>
                <a href="{{ route('acc-sfl.master-particulars.export', request()->query()) }}" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-file-excel mr-1"></i> Export Excel
                </a>
                @can('ac_master_particular.add')
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-toggle="modal" data-target="#createMasterParticularModal">
                    <i class="fa-solid fa-plus"></i> Add Master Particular
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @include('acc-sfl::admin.partials.alerts')

            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search name">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Type</label>
                    <select name="type" class="form-control form-control-sm">
                        <option value="">All Types</option>
                        <option value="debit" @selected(request('type') === 'debit')>Debit</option>
                        <option value="credit" @selected(request('type') === 'credit')>Credit</option>
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
                    <a href="{{ route('acc-sfl.master-particulars.index') }}" class="btn btn-light btn-sm w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive mt-2">
                <table id="masterParticularsTable" class="table table-bordered table-sm mb-0" style="width:100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Particulars Count</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($masterParticulars as $masterParticular)
                        <tr>
                            <td>{{ $masterParticular->name }}</td>
                            <td>
                                @if($masterParticular->type === 'debit')
                                    <span class="badge badge-info">Debit</span>
                                @else
                                    <span class="badge badge-warning">Credit</span>
                                @endif
                            </td>
                            <td>{{ $masterParticular->particulars_count }}</td>
                            <td>
                                <span class="badge {{ $masterParticular->is_active ? 'badge-success' : 'badge-secondary' }}">{{ $masterParticular->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn-custom" title="View" data-toggle="modal" data-target="#viewMasterParticularModal"
                                    data-name="{{ $masterParticular->name }}"
                                    data-description="{{ $masterParticular->description }}"
                                    data-type="{{ $masterParticular->type === 'debit' ? 'Debit' : 'Credit' }}"
                                    data-status="{{ $masterParticular->is_active ? 'Active' : 'Inactive' }}">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                @can('ac_master_particular.edit')
                                <button type="button" class="btn-custom yellow" title="Edit" data-toggle="modal" data-target="#editMasterParticularModal"
                                    data-action="{{ route('acc-sfl.master-particulars.update', $masterParticular) }}"
                                    data-name="{{ $masterParticular->name }}"
                                    data-description="{{ $masterParticular->description }}"
                                    data-type="{{ $masterParticular->type }}"
                                    data-active="{{ $masterParticular->is_active ? '1' : '0' }}">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                @endcan
                                @can('ac_master_particular.delete')
                                <button type="button" class="btn-custom danger" title="Delete" data-toggle="modal" data-target="#deleteMasterParticularModal"
                                    data-action="{{ route('acc-sfl.master-particulars.destroy', $masterParticular) }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $masterParticulars->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="createMasterParticularModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('acc-sfl.master-particulars.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Master Particular</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-control" required>
                            <option value="debit">Debit (increases cash — e.g. receipts)</option>
                            <option value="credit">Credit (decreases cash — e.g. expenses)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="is_active" value="0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="create_master_particular_is_active" name="is_active" value="1" checked>
                            <label class="custom-control-label" for="create_master_particular_is_active">Active</label>
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
<div class="modal fade" id="editMasterParticularModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="editMasterParticularForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Master Particular</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_master_particular_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="edit_master_particular_description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Type <span class="text-danger">*</span></label>
                        <select name="type" id="edit_master_particular_type" class="form-control" required>
                            <option value="debit">Debit (increases cash — e.g. receipts)</option>
                            <option value="credit">Credit (decreases cash — e.g. expenses)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="is_active" value="0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="edit_master_particular_is_active" name="is_active" value="1">
                            <label class="custom-control-label" for="edit_master_particular_is_active">Active</label>
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
<div class="modal fade" id="viewMasterParticularModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Master Particular Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm">
                    <tr><th>Name</th><td id="view_master_particular_name"></td></tr>
                    <tr><th>Description</th><td id="view_master_particular_description"></td></tr>
                    <tr><th>Type</th><td id="view_master_particular_type"></td></tr>
                    <tr><th>Status</th><td id="view_master_particular_status"></td></tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@include('acc-sfl::admin.partials.delete-confirm-modal', ['modalId' => 'deleteMasterParticularModal', 'label' => 'master particular'])
@endsection

@push('js')
@include('acc-sfl::admin.partials.select2-init')
<script>
    $(function () {
        $('#editMasterParticularModal').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget);
            $(this).find('form').attr('action', btn.data('action'));
            $('#edit_master_particular_name').val(btn.data('name'));
            $('#edit_master_particular_description').val(btn.data('description'));
            $('#edit_master_particular_type').val(btn.data('type')).trigger('change');
            $('#edit_master_particular_is_active').prop('checked', btn.data('active') == 1);
        });

        $('#viewMasterParticularModal').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget);
            $('#view_master_particular_name').text(btn.data('name'));
            $('#view_master_particular_description').text(btn.data('description') || '-');
            $('#view_master_particular_type').text(btn.data('type'));
            $('#view_master_particular_status').text(btn.data('status'));
        });
    });
</script>
@endpush
