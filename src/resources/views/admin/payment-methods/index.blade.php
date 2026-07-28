@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Payment Methods') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 p-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fa-solid fa-credit-card mr-2 text-primary"></i>Payment Methods</h4>
            <div>
                <a href="{{ route('acc-sfl.payment-methods.print', request()->query()) }}" target="_blank" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-print mr-1"></i> Print
                </a>
                <a href="{{ route('acc-sfl.payment-methods.export', request()->query()) }}" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-file-excel mr-1"></i> Export Excel
                </a>
                @can('ac_payment_method.add')
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-toggle="modal" data-target="#createPaymentMethodModal">
                    <i class="fa-solid fa-plus"></i> Add Payment Method
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @include('acc-sfl::admin.partials.alerts')

            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search name">
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
                    <a href="{{ route('acc-sfl.payment-methods.index') }}" class="btn btn-light btn-sm w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive mt-2">
                <table id="paymentMethodsTable" class="table table-bordered table-sm mb-0" style="width:100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paymentMethods as $paymentMethod)
                        <tr>
                            <td>{{ $paymentMethod->name }}</td>
                            <td>
                                <span class="badge {{ $paymentMethod->is_active ? 'badge-success' : 'badge-secondary' }}">{{ $paymentMethod->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn-custom" title="View" data-toggle="modal" data-target="#viewPaymentMethodModal"
                                    data-name="{{ $paymentMethod->name }}"
                                    data-status="{{ $paymentMethod->is_active ? 'Active' : 'Inactive' }}">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                @can('ac_payment_method.edit')
                                <button type="button" class="btn-custom yellow" title="Edit" data-toggle="modal" data-target="#editPaymentMethodModal"
                                    data-action="{{ route('acc-sfl.payment-methods.update', $paymentMethod) }}"
                                    data-name="{{ $paymentMethod->name }}"
                                    data-active="{{ $paymentMethod->is_active ? '1' : '0' }}">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                @endcan
                                @can('ac_payment_method.delete')
                                <button type="button" class="btn-custom danger" title="Delete" data-toggle="modal" data-target="#deletePaymentMethodModal"
                                    data-action="{{ route('acc-sfl.payment-methods.destroy', $paymentMethod) }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $paymentMethods->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="createPaymentMethodModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('acc-sfl.payment-methods.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Payment Method</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="is_active" value="0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="create_payment_method_is_active" name="is_active" value="1" checked>
                            <label class="custom-control-label" for="create_payment_method_is_active">Active</label>
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
<div class="modal fade" id="editPaymentMethodModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="editPaymentMethodForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Payment Method</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_payment_method_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="is_active" value="0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="edit_payment_method_is_active" name="is_active" value="1">
                            <label class="custom-control-label" for="edit_payment_method_is_active">Active</label>
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
<div class="modal fade" id="viewPaymentMethodModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Method Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm">
                    <tr><th>Name</th><td id="view_payment_method_name"></td></tr>
                    <tr><th>Status</th><td id="view_payment_method_status"></td></tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@include('acc-sfl::admin.partials.delete-confirm-modal', ['modalId' => 'deletePaymentMethodModal', 'label' => 'payment method'])
@endsection

@push('js')
@include('acc-sfl::admin.partials.select2-init')
<script>
    $(function () {
        $('#editPaymentMethodModal').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget);
            $(this).find('form').attr('action', btn.data('action'));
            $('#edit_payment_method_name').val(btn.data('name'));
            $('#edit_payment_method_is_active').prop('checked', btn.data('active') == 1);
        });

        $('#viewPaymentMethodModal').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget);
            $('#view_payment_method_name').text(btn.data('name'));
            $('#view_payment_method_status').text(btn.data('status'));
        });
    });
</script>
@endpush
