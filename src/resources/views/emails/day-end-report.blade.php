<!doctype html>
<html><head><meta charset="utf-8"></head>
<body style="font-family:Arial,Helvetica,sans-serif;color:#222;margin:0;padding:16px;background:#fff;">
    <div style="max-width:900px;margin:0 auto;">
        <div style="text-align:center;border-bottom:2px solid #1a3c8c;padding-bottom:6px;margin-bottom:10px;">
            <div style="font-size:18px;font-weight:bold;">{{ config('acc-sfl.company.name') }}</div>
            <div style="font-size:12px;">{{ config('acc-sfl.company.address') }}</div>
            <div style="font-size:15px;font-weight:bold;margin-top:6px;">Day End Report — {{ $report['date']->format('d M Y') }}</div>
        </div>
        @include('acc-sfl::admin.reports.partials.day-end-report-table', ['report' => $report])
        <p style="font-size:12px;margin-top:14px;"><a href="{{ $url }}">Open in the system</a> · Sent {{ now()->format('d M Y h:i A') }}</p>
    </div>
</body></html>
