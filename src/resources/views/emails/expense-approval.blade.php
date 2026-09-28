@php
    $td = 'border:1px solid #d5d9e0;padding:7px 9px;font-size:13px;color:#17233c;';
    $th = 'border:1px solid #d5d9e0;padding:7px 9px;font-size:12px;color:#17233c;background:#f3f5f8;text-align:left;';
    $label = 'color:#7b8794;font-size:12px;padding:3px 0;width:140px;vertical-align:top;';
    $value = 'color:#17233c;font-size:13px;padding:3px 0;font-weight:600;vertical-align:top;';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Expense Memo - {{ $expense->expense_no }}</title>
</head>
<body style="margin:0;padding:0;background:#eef1f5;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#eef1f5;padding:24px 12px;">
    <tr>
        <td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:680px;background:#ffffff;border:1px solid #d5d9e0;border-radius:6px;">
                {{-- Company header --}}
                <tr>
                    <td style="padding:22px 24px 14px;border-bottom:2px solid #17233c;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="vertical-align:top;">
                                    <div style="font-size:20px;font-weight:700;color:#17233c;">{{ config('acc-sfl.company.name') }}</div>
                                    @if(config('acc-sfl.company.subtitle'))
                                        <div style="font-size:12px;color:#555;">({{ config('acc-sfl.company.subtitle') }})</div>
                                    @endif
                                    <div style="font-size:12px;color:#555;margin-top:2px;">{{ config('acc-sfl.company.address') }}</div>
                                    @if(config('acc-sfl.company.mobile') || config('acc-sfl.company.email'))
                                        <div style="font-size:12px;color:#555;">
                                            {{ collect([config('acc-sfl.company.mobile'), config('acc-sfl.company.email')])->filter()->implode(', ') }}
                                        </div>
                                    @endif
                                </td>
                                <td style="vertical-align:top;text-align:right;white-space:nowrap;">
                                    <div style="display:inline-block;background:#17233c;color:#ffffff;font-size:12px;font-weight:700;letter-spacing:1px;padding:5px 12px;">EXPENSE MEMO</div>
                                    <div style="font-size:12px;color:#555;margin-top:8px;">No: <strong style="color:#17233c;">{{ $expense->expense_no }}</strong></div>
                                    <div style="font-size:12px;color:#555;">Date: <strong style="color:#17233c;">{{ $expense->expense_date?->format('d.m.Y') }}</strong></div>
                                    <div style="font-size:12px;color:#b7791f;font-weight:700;margin-top:4px;">PENDING APPROVAL</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Meta --}}
                <tr>
                    <td style="padding:16px 24px 6px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr><td style="{{ $label }}">Paid to (Company)</td><td style="{{ $value }}">{{ $expense->company_name ?: '-' }}</td></tr>
                            <tr>
                                <td style="{{ $label }}">Receiver</td>
                                <td style="{{ $value }}">
                                    {{ $expense->receiver_name ?: '-' }}
                                    @if($expense->receiver_mobile) ({{ $expense->receiver_mobile }}) @endif
                                </td>
                            </tr>
                            @if($expense->employee)
                                <tr><td style="{{ $label }}">Employee</td><td style="{{ $value }}">{{ $expense->employee->name }}</td></tr>
                            @endif
                            <tr><td style="{{ $label }}">Branch</td><td style="{{ $value }}">{{ $expense->branch->name ?? '-' }}</td></tr>
                            <tr><td style="{{ $label }}">Account</td><td style="{{ $value }}">{{ $expense->account->name ?? '-' }}</td></tr>
                            <tr><td style="{{ $label }}">Payment Method</td><td style="{{ $value }}">{{ $expense->paymentMethod->name ?? '-' }}</td></tr>
                            @if($expense->invoice)
                                <tr><td style="{{ $label }}">Invoice</td><td style="{{ $value }}">{{ $expense->invoice }}</td></tr>
                            @endif
                        </table>
                    </td>
                </tr>

                {{-- Items --}}
                <tr>
                    <td style="padding:10px 24px;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                            <thead>
                                <tr>
                                    <th style="{{ $th }}width:30px;">#</th>
                                    <th style="{{ $th }}">Particular</th>
                                    <th style="{{ $th }}text-align:right;">Qty</th>
                                    <th style="{{ $th }}">UOM</th>
                                    <th style="{{ $th }}text-align:right;">Rate</th>
                                    <th style="{{ $th }}text-align:right;">Amount (Tk)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expense->details as $detail)
                                    <tr>
                                        <td style="{{ $td }}">{{ $loop->iteration }}</td>
                                        <td style="{{ $td }}">
                                            {{ $detail->particular->name ?? '-' }}
                                            @if($detail->particular?->masterParticular)
                                                <div style="font-size:11px;color:#7b8794;">{{ $detail->particular->masterParticular->name }}</div>
                                            @endif
                                            @if($detail->description)
                                                <div style="font-size:11px;color:#7b8794;">{{ $detail->description }}</div>
                                            @endif
                                            @if($detail->invoice)
                                                <div style="font-size:11px;color:#7b8794;">Invoice: {{ $detail->invoice }}</div>
                                            @endif
                                        </td>
                                        <td style="{{ $td }}text-align:right;">{{ number_format((float) $detail->qty, 2) }}</td>
                                        <td style="{{ $td }}">{{ $detail->uom ?: '-' }}</td>
                                        <td style="{{ $td }}text-align:right;">{{ number_format((float) $detail->rate, 2) }}</td>
                                        <td style="{{ $td }}text-align:right;">{{ number_format((float) $detail->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td style="{{ $td }}">1</td>
                                        <td style="{{ $td }}">{{ strip_tags((string) $expense->description) ?: '-' }}</td>
                                        <td style="{{ $td }}text-align:right;">-</td>
                                        <td style="{{ $td }}">-</td>
                                        <td style="{{ $td }}text-align:right;">-</td>
                                        <td style="{{ $td }}text-align:right;">{{ number_format((float) $expense->total_amount, 2) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5" style="{{ $th }}text-align:right;font-size:13px;">Total</th>
                                    <th style="{{ $th }}text-align:right;font-size:14px;">{{ number_format((float) $expense->total_amount, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                        <div style="font-size:12px;color:#555;margin-top:8px;">
                            <strong style="color:#17233c;">Taka in word:</strong> <em>{{ $amountInWords }}</em>
                        </div>
                    </td>
                </tr>

                @if($expense->description && $expense->details->isNotEmpty())
                    <tr>
                        <td style="padding:4px 24px 8px;font-size:12px;color:#555;">
                            <strong style="color:#17233c;">Description:</strong> {{ strip_tags((string) $expense->description) }}
                        </td>
                    </tr>
                @endif

                {{-- Footer / action --}}
                <tr>
                    <td style="padding:14px 24px 22px;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="border-top:1px dashed #c3c9d2;">
                            <tr>
                                <td style="padding-top:12px;font-size:12px;color:#555;vertical-align:top;">
                                    Recorded by: <strong style="color:#17233c;">{{ $expense->creator->name ?? 'N/A' }}</strong><br>
                                    Recorded at: {{ $expense->created_at?->format('d.m.Y h:i A') }}
                                    @if($expense->attachments->isNotEmpty() || $expense->attachment)
                                        <br>Attachments: {{ $expense->attachments->count() + ($expense->attachment ? 1 : 0) }} (view in system)
                                    @endif
                                </td>
                                <td style="padding-top:12px;text-align:right;vertical-align:top;">
                                    <a href="{{ $approvalUrl }}" style="display:inline-block;background:#1769e0;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:5px;font-size:13px;font-weight:700;">Review &amp; Approve</a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:16px 0 0;font-size:11px;color:#a0aab8;">
                            Sign in with an account that has expense approval permission to review this request.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
