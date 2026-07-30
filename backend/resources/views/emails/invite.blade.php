@extends('emails.layout')

@section('content')
    <p style="margin:0 0 16px;">Hello,</p>
    <p style="margin:0 0 20px;">
        You've been invited to join{{ $schoolName ? ' ' . $schoolName . ' on' : '' }} SabiHub.
        Click the button below to accept your invitation and set up your account.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding:8px 0 24px;">
                <a href="{{ $acceptUrl }}" style="display:inline-block; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; font-size:16px; font-weight:600; color:#ffffff; background-color:#013D47; text-decoration:none; border-radius:8px; padding:14px 28px;">Accept invitation</a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px; font-size:14px; color:#8a99a5;">If the button doesn't work, copy and paste this link into your browser:</p>
    <p style="margin:0; font-size:14px; word-break:break-all;"><a href="{{ $acceptUrl }}" style="color:#013D47;">{{ $acceptUrl }}</a></p>
@endsection
