<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>SabiHub</title>
</head>
<body style="margin:0; padding:0; background-color:#F4F6F7; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F4F6F7;">
        <tr>
            <td align="center" style="padding:24px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px; margin:0 auto;">
                    {{-- Header band --}}
                    <tr>
                        <td align="center" style="background-color:#013D47; border-radius:12px 12px 0 0; padding:28px 24px;">
                            <span style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; font-size:24px; font-weight:700; letter-spacing:0.5px; color:#ffffff;">SabiHub</span>
                        </td>
                    </tr>
                    {{-- Content card --}}
                    <tr>
                        <td style="background-color:#ffffff; padding:32px 28px; border-radius:0 0 12px 12px; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; font-size:16px; line-height:1.6; color:#1f2933;">
                            @yield('content')
                        </td>
                    </tr>
                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="padding:20px 24px; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; font-size:12px; line-height:1.5; color:#8a99a5;">
                            SabiHub &middot; School management for Nigerian schools<br>
                            This is an automated message, please do not reply.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
