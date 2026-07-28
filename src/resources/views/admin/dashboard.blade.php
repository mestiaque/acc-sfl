@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Accounting Dashboard') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 p-4">
    @include('acc-sfl::admin.partials.alerts')
    @include('acc-sfl::admin.partials.dashboard-widget')
</div>
@endsection
