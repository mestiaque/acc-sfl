@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Add Expense') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 p-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fa-solid fa-money-bill-wave mr-2 text-warning"></i>Add Expense</h4>
            <a href="{{ route('acc-sfl.expenses.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left mr-1"></i> Back</a>
        </div>
        <div class="card-body">
            @include('acc-sfl::admin.partials.alerts')

            <form method="POST" action="{{ route('acc-sfl.expenses.store') }}" enctype="multipart/form-data" id="createExpenseForm">
                @csrf
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
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Employee</label>
                        <select name="employee_id" class="form-control">
                            <option value="">-- None --</option>
                            @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->employee_id }} - {{ $employee->name }}{{ $employee->department ? ' ('.$employee->department->name.(($employee->designation) ? ' / '.$employee->designation->name : '').')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Invoice</label>
                        <input type="text" name="invoice" class="form-control">
                    </div>
                </div>

                @include('acc-sfl::admin.expenses.partials.items-table', ['particulars' => $particulars])

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3" data-tinymce="1"></textarea>
                </div>
                <div class="form-group">
                    <label>Attachment</label>
                    <input type="file" name="attachment" class="form-control-file">
                </div>

                <div class="text-right">
                    <a href="{{ route('acc-sfl.expenses.index') }}" class="btn btn-light btn-sm">Cancel</a>
                    <button type="submit" class="btn btn-primary btn-sm">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
@include('acc-sfl::admin.partials.select2-init')
@include('acc-sfl::admin.expenses.partials.item-scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.5/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    $(function () {
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                selector: '#createExpenseForm textarea[data-tinymce="1"]',
                height: 150,
                menubar: false,
                branding: false,
                plugins: 'lists link code',
                toolbar: 'undo redo | bold italic underline | bullist numlist | link | code',
                statusbar: false,
            });
        }
    });
</script>
@endpush
