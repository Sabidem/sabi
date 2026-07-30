@extends('emails.layout')

@section('content')
    <p style="margin:0 0 16px;">Hi {{ $name }},</p>
    <p style="margin:0 0 20px;">Use the code below to reset your SabiHub password.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding:8px 0 24px;">
                <span style="display:inline-block; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; font-size:34px; font-weight:700; letter-spacing:10px; color:#013D47; background-color:#F4F6F7; border-radius:10px; padding:16px 24px;">{{ $code }}</span>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px;">This code expires in {{ $minutes }} minutes.</p>
    <p style="margin:0; color:#8a99a5; font-size:14px;">If you didn't request this, ignore this email.</p>
@endsection
