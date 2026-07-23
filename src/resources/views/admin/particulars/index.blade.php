@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Particulars') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 p-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fa-solid fa-list mr-2 text-primary"></i>Particulars</h4>
            <div>
                <a href="{{ route('acc-sfl.particulars.print', request()->query()) }}" target="_blank" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-print mr-1"></i> Print
                </a>
                <a href="{{ route('acc-sfl.particulars.export', request()->query()) }}" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-file-excel mr-1"></i> Export Excel
                </a>
                @can('ac_particular.add')
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-toggle="modal" data-target="#createParticularModal">
                    <i class="fa-solid fa-plus"></i> Add Particular
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @include('acc-sfl::admin.partials.alerts')

            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search name, code">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Master Particular</label>
                    <select name="master_particular_id" class="form-control form-control-sm">
                        <option value="">All Master Particulars</option>
                        @foreach($masterParticulars as $masterParticular)
                        <option value="{{ $masterParticular->id }}" @selected((string) request('master_particular_id') === (string) $masterParticular->id)>{{ $masterParticular->name }}</option>
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
                    <a href="{{ route('acc-sfl.particulars.index') }}" class="btn btn-light btn-sm w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive mt-2">
                <table id="particularsTable" class="table table-bordered table-sm mb-0" style="width:100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Master Particular</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($particulars as $particular)
                        <tr>
                            <td>{{ $particular->name }}</td>
                            <td>{{ $particular->code ?: '-' }}</td>
                            <td>{{ $particular->masterParticular->name }}</td>
                            <td>
                                <span class="badge {{ $particular->is_active ? 'badge-success' : 'badge-secondary' }}">{{ $particular->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn-custom" title="View" data-toggle="modal" data-target="#viewParticularModal"
                                    data-name="{{ $particular->name }}"
                                    data-code="{{ $particular->code }}"
                                    data-description="{{ $particular->description }}"
                                    data-master-particular="{{ $particular->masterParticular->name }}"
                                    data-status="{{ $particular->is_active ? 'Active' : 'Inactive' }}">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                @can('ac_particular.edit')
                                <button type="button" class="btn-custom yellow" title="Edit" data-toggle="modal" data-target="#editParticularModal"
                                    data-action="{{ route('acc-sfl.particulars.update', $particular) }}"
                                    data-name="{{ $particular->name }}"
                                    data-code="{{ $particular->code }}"
                                    data-description="{{ $particular->description }}"
                                    data-master-particular-id="{{ $particular->master_particular_id }}"
                                    data-active="{{ $particular->is_active ? '1' : '0' }}">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                @endcan
                                @can('ac_particular.delete')
                                <button type="button" class="btn-custom danger" title="Delete" data-toggle="modal" data-target="#deleteParticularModal"
                                    data-action="{{ route('acc-sfl.particulars.destroy', $particular) }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $particulars->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="createParticularModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('acc-sfl.particulars.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Particular</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Master Particular <span class="text-danger">*</span></label>
                        <select name="master_particular_id" class="form-control" required>
                            <option value="">-- Select Master Particular --</option>
                            @foreach($masterParticulars as $masterParticular)
                            <option value="{{ $masterParticular->id }}">{{ $masterParticular->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Code (optional)</label>
                        <input type="text" name="code" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="is_active" value="0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="create_particular_is_active" name="is_active" value="1" checked>
                            <label class="custom-control-label" for="create_particular_is_active">Active</label>
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
<div class="modal fade" id="editParticularModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="editParticularForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Particular</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Master Particular <span class="text-danger">*</span></label>
                        <select name="master_particular_id" id="edit_particular_master_particular_id" class="form-control" required>
                            <option value="">-- Select Master Particular --</option>
                            @foreach($masterParticulars as $masterParticular)
                            <option value="{{ $masterParticular->id }}">{{ $masterParticular->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_particular_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Code (optional)</label>
                        <input type="text" name="code" id="edit_particular_code" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="edit_particular_description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="is_active" value="0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="edit_particular_is_active" name="is_active" value="1">
                            <label class="custom-control-label" for="edit_particular_is_active">Active</label>
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
<div class="modal fade" id="viewParticularModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Particular Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm">
                    <tr><th>Name</th><td id="view_particular_name"></td></tr>
                    <tr><th>Code</th><td id="view_particular_code"></td></tr>
                    <tr><th>Description</th><td id="view_particular_description"></td></tr>
                    <tr><th>Master Particular</th><td id="view_particular_master_particular"></td></tr>
                    <tr><th>Status</th><td id="view_particular_status"></td></tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@include('acc-sfl::admin.partials.delete-confirm-modal', ['modalId' => 'deleteParticularModal', 'label' => 'particular'])
@endsection

@push('js')
<script>
    $(function () {
        $('#editParticularModal').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget);
            $(this).find('form').attr('action', btn.data('action'));
            $('#edit_particular_master_particular_id').val(btn.data('master-particular-id'));
            $('#edit_particular_name').val(btn.data('name'));
            $('#edit_particular_code').val(btn.data('code'));
            $('#edit_particular_description').val(btn.data('description'));
            $('#edit_particular_is_active').prop('checked', btn.data('active') == 1);
        });

        $('#viewParticularModal').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget);
            $('#view_particular_name').text(btn.data('name'));
            $('#view_particular_code').text(btn.data('code') || '-');
            $('#view_particular_description').text(btn.data('description') || '-');
            $('#view_particular_master_particular').text(btn.data('master-particular'));
            $('#view_particular_status').text(btn.data('status'));
        });
    });
</script>
@endpush
