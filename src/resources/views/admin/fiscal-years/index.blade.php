@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Fiscal Years') }}</title>
@endsection

@php
    $acFyMonths = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
        7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];
@endphp

@section('contents')
<div class="flex-grow-1 p-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fa-solid fa-calendar-days mr-2 text-secondary"></i>Fiscal Years</h4>
            <div>
                <a href="{{ route('acc-sfl.fiscal-years.print', request()->query()) }}" target="_blank" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-print mr-1"></i> Print
                </a>
                <a href="{{ route('acc-sfl.fiscal-years.export', request()->query()) }}" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-file-excel mr-1"></i> Export Excel
                </a>
                @can('ac_fiscal_year.add')
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-toggle="modal" data-target="#createFiscalYearModal">
                    <i class="fa-solid fa-plus"></i> Add Fiscal Year
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @include('acc-sfl::admin.partials.alerts')

            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search fiscal year">
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
                    <a href="{{ route('acc-sfl.fiscal-years.index') }}" class="btn btn-light btn-sm w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive mt-2">
                <table id="fiscalYearsTable" class="table table-bordered table-sm mb-0" style="width:100%">
                    <thead>
                        <tr>
                            <th>Fiscal Year</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fiscalYears as $fiscalYear)
                        <tr>
                            <td>{{ $fiscalYear->label }}</td>
                            <td>{{ $acFyMonths[$fiscalYear->start_month] }} {{ $fiscalYear->start_year }}</td>
                            <td>{{ $acFyMonths[$fiscalYear->end_month] }} {{ $fiscalYear->end_year }}</td>
                            <td>
                                <span class="badge {{ $fiscalYear->is_active ? 'badge-success' : 'badge-secondary' }}">{{ $fiscalYear->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="text-center">
                                @can('ac_fiscal_year.edit')
                                <button type="button" class="btn-custom yellow" title="Edit" data-toggle="modal" data-target="#editFiscalYearModal"
                                    data-action="{{ route('acc-sfl.fiscal-years.update', $fiscalYear) }}"
                                    data-start-month="{{ $fiscalYear->start_month }}"
                                    data-start-year="{{ $fiscalYear->start_year }}"
                                    data-end-month="{{ $fiscalYear->end_month }}"
                                    data-end-year="{{ $fiscalYear->end_year }}"
                                    data-active="{{ $fiscalYear->is_active ? '1' : '0' }}">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                @endcan
                                @can('ac_fiscal_year.delete')
                                <button type="button" class="btn-custom danger" title="Delete" data-toggle="modal" data-target="#deleteFiscalYearModal"
                                    data-action="{{ route('acc-sfl.fiscal-years.destroy', $fiscalYear) }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $fiscalYears->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="createFiscalYearModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('acc-sfl.fiscal-years.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Fiscal Year</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">The period from Start to End must span exactly 12 months.</p>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Start Month <span class="text-danger">*</span></label>
                            <select name="start_month" class="form-control" required>
                                <option value="">-- Select --</option>
                                @foreach($acFyMonths as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Start Year <span class="text-danger">*</span></label>
                            <input type="number" name="start_year" class="form-control" min="1900" max="9999" value="{{ now()->year }}" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>End Month <span class="text-danger">*</span></label>
                            <select name="end_month" class="form-control" required>
                                <option value="">-- Select --</option>
                                @foreach($acFyMonths as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>End Year <span class="text-danger">*</span></label>
                            <input type="number" name="end_year" class="form-control" min="1900" max="9999" value="{{ now()->year + 1 }}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="is_active" value="0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="create_fiscal_year_is_active" name="is_active" value="1" checked>
                            <label class="custom-control-label" for="create_fiscal_year_is_active">Active</label>
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
<div class="modal fade" id="editFiscalYearModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="editFiscalYearForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Fiscal Year</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">The period from Start to End must span exactly 12 months.</p>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Start Month <span class="text-danger">*</span></label>
                            <select name="start_month" id="edit_fiscal_year_start_month" class="form-control" required>
                                <option value="">-- Select --</option>
                                @foreach($acFyMonths as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Start Year <span class="text-danger">*</span></label>
                            <input type="number" name="start_year" id="edit_fiscal_year_start_year" class="form-control" min="1900" max="9999" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>End Month <span class="text-danger">*</span></label>
                            <select name="end_month" id="edit_fiscal_year_end_month" class="form-control" required>
                                <option value="">-- Select --</option>
                                @foreach($acFyMonths as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>End Year <span class="text-danger">*</span></label>
                            <input type="number" name="end_year" id="edit_fiscal_year_end_year" class="form-control" min="1900" max="9999" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="is_active" value="0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="edit_fiscal_year_is_active" name="is_active" value="1">
                            <label class="custom-control-label" for="edit_fiscal_year_is_active">Active</label>
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

@include('acc-sfl::admin.partials.delete-confirm-modal', ['modalId' => 'deleteFiscalYearModal', 'label' => 'fiscal year'])
@endsection

@push('js')
<script>
    $(function () {
        $('#editFiscalYearModal').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget);
            $(this).find('form').attr('action', btn.data('action'));
            $('#edit_fiscal_year_start_month').val(btn.data('start-month'));
            $('#edit_fiscal_year_start_year').val(btn.data('start-year'));
            $('#edit_fiscal_year_end_month').val(btn.data('end-month'));
            $('#edit_fiscal_year_end_year').val(btn.data('end-year'));
            $('#edit_fiscal_year_is_active').prop('checked', btn.data('active') == 1);
        });
    });
</script>
@endpush
