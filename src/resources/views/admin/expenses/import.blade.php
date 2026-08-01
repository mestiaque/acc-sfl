@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Import Expenses') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 p-4">
    @include('acc-sfl::admin.partials.alerts')

    @include('acc-sfl::admin.partials.excel-import-page', [
        'title' => 'Import Expense from Excel',
        'backUrl' => route('acc-sfl.expenses.index'),
        'previewUrl' => route('acc-sfl.expenses.import.preview'),
        'saveUrl' => route('acc-sfl.expenses.import.save'),
        'recheckUrl' => route('acc-sfl.expenses.import.recheck'),
        'columns' => [
            ['key' => 'date', 'label' => 'Date'],
            ['key' => 'branch', 'label' => 'Branch'],
            ['key' => 'account', 'label' => 'Account'],
            ['key' => 'payment_method', 'label' => 'Payment Method'],
            ['key' => 'particular_code', 'label' => 'Particular Code'],
            ['key' => 'particular_name', 'label' => 'Particular Name'],
            ['key' => 'qty', 'label' => 'Qty', 'align' => 'right'],
            ['key' => 'rate', 'label' => 'Rate', 'align' => 'right'],
            ['key' => 'amount', 'label' => 'Amount', 'align' => 'right'],
            ['key' => 'invoice', 'label' => 'Invoice'],
            ['key' => 'uom', 'label' => 'UOM'],
            ['key' => 'receiver_name', 'label' => 'Receiver Name'],
            ['key' => 'receiver_mobile', 'label' => 'Receiver Mobile'],
            ['key' => 'company_name', 'label' => 'Company Name'],
            ['key' => 'description', 'label' => 'Description'],
        ],
    ])
</div>
@endsection
