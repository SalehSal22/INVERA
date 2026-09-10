<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $appName }} Verification Code</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f5f7; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7; padding: 40px 0;">
<tr>
<td align="center">

<table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">

<!-- Header / Wordmark -->
<tr>
<td align="center" style="background-color:#0f172a; padding: 32px 24px;">
<span style="font-size:24px; font-weight:700; color:#ffffff; letter-spacing:0.5px;">
{{ $appName }}
</span>
</td>
</tr>

<!-- Body -->
<tr>
<td style="padding: 40px 40px 24px 40px;">
<h1 style="margin:0 0 16px 0; font-size:20px; color:#111827; font-weight:600;">
Verify your account
</h1>
<p style="margin:0 0 24px 0; font-size:15px; line-height:1.6; color:#4b5563;">
Hello,<br>
Use the verification code below to complete your sign up. This code expires in <strong>{{ $ttlMinutes }} minutes</strong>.
</p>

<!-- OTP Box -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
<tr>
<td align="center" style="background-color:#f3f4f6; border-radius:8px; padding: 20px;">
<span style="font-size:32px; font-weight:700; letter-spacing:8px; color:#0f172a;">
{{ $otp }}
</span>
</td>
</tr>
</table>

<p style="margin:0; font-size:14px; line-height:1.6; color:#9ca3af;">
If you did not request this code, you can safely ignore this email.
</p>
</td>
</tr>

<!-- Footer -->
<tr>
<td style="padding: 24px 40px; border-top: 1px solid #e5e7eb;">
<p style="margin:0; font-size:13px; color:#9ca3af; text-align:center;">
Thanks,<br>
{{ $appName }} Support
</p>
</td>
</tr>

<tr>
<td style="padding: 16px 40px; background-color:#f9fafb;">
<p style="margin:0; font-size:12px; color:#9ca3af; text-align:center;">
This message was sent by {{ $appName }} as part of account security.
</p>
</td>
</tr>

</table>

</td>
</tr>
</table>
</body>
</html>