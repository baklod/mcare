@extends('mail.official-layout', ['title' => $title])

@section('content')
    <tr>
        <td style="padding:4px 0 16px;">
            <h1 style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:20px;font-weight:normal;line-height:1.4;color:#0f172a;">{{ $heading }}</h1>
        </td>
    </tr>
    <tr>
        <td style="padding:16px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
        </td>
    </tr>
    <tr>
        <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;color:#334155;">
            <p style="margin:0 0 12px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;">Dear {{ $recipientName }},</p>
            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;">{{ $intro }}</p>
        </td>
    </tr>
    @if (filled($enrollmentNumber))
        <tr>
            <td style="padding:16px 0;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
            </td>
        </tr>
        <tr>
            <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;color:#334155;">
                <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:normal;color:#64748b;">Enrollment number</p>
                <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:18px;font-weight:normal;letter-spacing:0.04em;color:#0f172a;">{{ $enrollmentNumber }}</p>
            </td>
        </tr>
    @endif
    @if (filled($adminNotes))
        <tr>
            <td style="padding:16px 0;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
            </td>
        </tr>
        <tr>
            <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;color:#334155;">
                <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:normal;color:#64748b;">Administrator note</p>
                <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;color:#0f172a;">{{ $adminNotes }}</p>
            </td>
        </tr>
    @endif
    <tr>
        <td style="padding:16px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
        </td>
    </tr>
    <tr>
        <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;">
            <a href="{{ $actionUrl }}" style="color:#5b21b6;font-family:Arial,Helvetica,sans-serif;font-weight:normal;text-decoration:underline;">{{ $actionLabel }}</a>
        </td>
    </tr>
    @if (filled($secondaryActionUrl) && filled($secondaryActionLabel))
        <tr>
            <td style="padding:16px 0;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
            </td>
        </tr>
        <tr>
            <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;">
                <a href="{{ $secondaryActionUrl }}" style="color:#5b21b6;font-family:Arial,Helvetica,sans-serif;font-weight:normal;text-decoration:underline;">{{ $secondaryActionLabel }}</a>
            </td>
        </tr>
    @endif
    @if (filled($closing))
        <tr>
            <td style="padding:16px 0;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
            </td>
        </tr>
        <tr>
            <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:normal;color:#64748b;">
                <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:normal;">{{ $closing }}</p>
            </td>
        </tr>
    @endif
@endsection
