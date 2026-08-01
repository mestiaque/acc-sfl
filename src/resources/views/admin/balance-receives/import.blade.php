@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Import Balance Receive') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 p-4">
    @include('acc-sfl::admin.partials.alerts')

    @include('acc-sfl::admin.partials.excel-import-page', [
        'title' => 'Import Balance Receive from Excel',
        'backUrl' => route('acc-sfl.balance-receives.index'),
        'previewUrl' => route('acc-sfl.balance-receives.import.preview'),
        'saveUrl' => route('acc-sfl.balance-receives.import.save'),
        'recheckUrl' => route('acc-sfl.balance-receives.import.recheck'),
        'columns' => [
            ['key' => 'date', 'label' => 'Date'],
            ['key' => 'branch', 'label' => 'Branch'],
            ['key' => 'account', 'label' => 'Account'],
            ['key' => 'particular_code', 'label' => 'Particular Code'],
            ['key' => 'particular_name', 'label' => 'Particular Name'],
            ['key' => 'amount', 'label' => 'Amount', 'align' => 'right'],
            ['key' => 'description', 'label' => 'Description'],
        ],
    ])
</div>
@endsection
