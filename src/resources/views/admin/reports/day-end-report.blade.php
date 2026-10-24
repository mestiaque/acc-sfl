@extends(adminTheme().'layouts.app')

@section('title')
    <title>{{ websiteTitle('Day End Report') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1">
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4 class="mb-0"><i class="fa-solid fa-cash-register mr-2 text-success"></i>Day End Report</h4>
            <div>
                <a href="{{ route('acc-sfl.reports.day-end.print', request()->query()) }}" target="_blank" class="btn btn-light btn-sm btn-icon" title="Print"><i class="fa-solid fa-print"></i></a>
                <form method="POST" action="{{ route('acc-sfl.reports.day-end.send', ['date' => $report['date']->toDateString()]) }}" class="d-inline" onsubmit="return confirm('Mail this day end report now?');">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-envelope mr-1"></i> Send Mail Now</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            @include('acc-sfl::admin.partials.alerts')

            <form method="GET" class="row align-items-end mb-2">
                <div class="col-md-3 mb-2">
                    <label class="form-label mb-1">Date</label>
                    <input type="date" name="date" value="{{ $report['date']->toDateString() }}" max="{{ today()->toDateString() }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-primary btn-sm btn-icon" title="Filter"><i class="fa-solid fa-filter"></i></button>
                    <a href="{{ route('acc-sfl.reports.day-end.index') }}" class="btn btn-light btn-sm btn-icon" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
                </div>
            </form>

            <p class="small text-muted mb-2">
                Mailed every day at {{ config('acc-sfl.day_end_report_at', '21:00') }}
                {{ $mailEnabled ? 'to: ' . ($recipients->implode(', ') ?: 'nobody yet — give users the "Day End Report" permission') : '(mail is OFF in Settings > Mail Notifications)' }}.
            </p>

            <div class="table-responsive">
                @include('acc-sfl::admin.reports.partials.day-end-report-table', ['report' => $report])
            </div>
        </div>
    </div>
</div>
@endsection
