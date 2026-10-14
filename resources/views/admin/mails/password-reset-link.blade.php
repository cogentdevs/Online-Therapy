<!doctype html>
<html lang="en">

<body style="margin:0;background:#f5f4f1;color:#25262a;font-family:Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="padding:32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                    style="max-width:600px;margin:0 auto;background:#ffffff;border-top:5px solid #3f6ca1;">
                    <tr>
                        <td style="padding:32px;">
                            <h1 style="margin:0 0 16px;font-size:24px;">Reset your admin password</h1>
                            <p style="line-height:1.6;">Hello {{ $user->name }},</p>
                            <p style="line-height:1.6;">We received a request to reset your Admin Portal password.</p>
                            <p style="margin:28px 0;">
                                <a href="{{ $resetUrl }}"
                                    style="display:inline-block;padding:12px 20px;background:#3f6ca1;color:#ffffff;text-decoration:none;">Reset Password</a>
                            </p>
                            <p style="line-height:1.6;">This link expires in {{ $expiresInMinutes }} minutes. If you did not request this, no action is needed.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
