@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Edit Expense') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fa-solid fa-money-bill-wave mr-2 text-warning"></i>Edit Expense — {{ $expense->expense_no }}</h4>
            <a href="{{ route('acc-sfl.expenses.index') }}" class="btn btn-light btn-sm btn-icon" title="Back"><i class="fa-solid fa-arrow-left"></i></a>
        </div>
        <div class="card-body">
            @include('acc-sfl::admin.partials.alerts')

            <form method="POST" action="{{ route('acc-sfl.expenses.update', $expense) }}" enctype="multipart/form-data" id="editExpenseForm">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label>Expense Date <span class="text-danger">*</span></label>
                        <input type="date" name="expense_date" class="form-control" value="{{ $expense->expense_date->toDateString() }}" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Billing Date</label>
                        <input type="date" name="billing_date" class="form-control" value="{{ $expense->billing_date?->toDateString() }}">
                        <small class="text-muted">Date on the bill, if different.</small>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-control" required>
                            <option value="">-- Select --</option>
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) $expense->branch_id === (int) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Account <span class="text-danger">*</span></label>
                        <select name="account_id" id="expenseAccount" class="form-control" required>
                            <option value="">-- Select --</option>
                            @foreach($accounts as $account)
                            <option value="{{ $account->id }}" @selected((int) $expense->account_id === (int) $account->id)>{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method_id" class="form-control" required>
                            <option value="">-- Select --</option>
                            @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}" @selected((int) $expense->payment_method_id === (int) $method->id)>{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Company Name</label>
                        <input type="text" name="company_name" class="form-control" value="{{ $expense->company_name }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Receiver Name</label>
                        <input type="text" name="receiver_name" class="form-control" value="{{ $expense->receiver_name }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Receiver Mobile</label>
                        <input type="text" name="receiver_mobile" class="form-control" value="{{ $expense->receiver_mobile }}">
                    </div>
                @include('acc-sfl::admin.expenses.partials.iou-select', ['selectedIouId' => $expense->iou_id])
                    <div class="col-md-3 form-group">
                        <label>Employee <span class="text-danger d-none" id="expenseEmployeeRequired">* (required for Salary Advance)</span></label>
                        <select name="employee_id" id="expenseEmployee" class="form-control">
                            <option value="">-- None --</option>
                            @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((int) $expense->employee_id === (int) $employee->id)>{{ $employee->employee_id }} - {{ $employee->name }}{{ $employee->department ? ' ('.$employee->department->name.(($employee->designation) ? ' / '.$employee->designation->name : '').')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Invoice</label>
                        <input type="text" name="invoice" class="form-control" value="{{ $expense->invoice }}">
                    </div>
                </div>

                @include('acc-sfl::admin.expenses.partials.items-table', [
                    'particulars' => $particulars,
                    'details' => $expense->details,
                    'totalAmount' => $expense->total_amount,
                ])

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3" data-tinymce="1">{{ $expense->description }}</textarea>
                </div>
                <div class="form-group">
                    <label>Attachments</label>
                    <input type="file" name="attachments[]" class="form-control-file" multiple>
                    <small class="form-text text-muted">You can select multiple files.</small>

                    @if($expense->attachment || $expense->attachments->count())
                    <ul class="list-unstyled mt-2 mb-0">
                        @if($expense->attachment)
                        <li class="d-flex align-items-center justify-content-between border rounded px-2 py-1 mb-1">
                            <a href="{{ asset('storage/'.$expense->attachment) }}" target="_blank">{{ basename($expense->attachment) }}</a>
                        </li>
                        @endif
                        @foreach($expense->attachments as $file)
                        <li class="d-flex align-items-center justify-content-between border rounded px-2 py-1 mb-1">
                            <a href="{{ $file->file_url }}" target="_blank">{{ $file->original_name ?: $file->file_name }}</a>
                            {{-- Submits its own form below (outside this one) - a nested form tag would close the edit form early and leave Update outside it. --}}
                            <button type="submit" form="removeAttachmentForm{{ $file->id }}" class="btn btn-link btn-sm text-danger p-0 ml-2">Remove</button>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>

                <div class="text-right">
                    <a href="{{ route('acc-sfl.expenses.index') }}" class="btn btn-light btn-sm">Cancel</a>
                    <button type="submit" class="btn btn-primary btn-sm">Update Expense</button>
                </div>
            </form>

            @foreach($expense->attachments as $file)
            <form id="removeAttachmentForm{{ $file->id }}" action="{{ route('acc-sfl.expenses.attachments.destroy', [$expense, $file]) }}" method="POST" onsubmit="return confirm('Remove this attachment?')" class="d-none">
                @csrf
                @method('DELETE')
            </form>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('js')
@include('acc-sfl::admin.partials.select2-init')
@include('acc-sfl::admin.expenses.partials.item-scripts', ['nextIndex' => $expense->details->count()])
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.5/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    $(function () {
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                selector: '#editExpenseForm textarea[data-tinymce="1"]',
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
