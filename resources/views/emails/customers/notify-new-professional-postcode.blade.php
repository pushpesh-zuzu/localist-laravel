<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Localists – Notify Customer New Professional in Postcode</title>
</head>

<body style="margin:0;padding:0;background:#f4f8fb;font-family:Arial,Helvetica,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" bgcolor="#f4f8fb">
        <tr>
            <td align="center" style="padding:30px 10px;">
                <table width="600" cellpadding="0" cellspacing="0" role="presentation"
                    style="background:#ffffff;overflow:hidden; box-shadow:0 6px 18px rgba(0,0,0,0.08);">
                    <tr>
                        <td align="center" style="padding:20px;">
                            <table width="100%">
                                <tr>
                                    <td bgcolor="#00AFE3" height="40" align="center" style="border-radius:5px;">
                                        <img src="{{$baseUrl}}/assets/localist_logo_1.png" height="26" alt="Localists">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:15px 40px 10px;color:#253238;">
                            <p style="margin:0 0 12px;font-size:18px;">
                                Hi, <strong>{{ ucfirst($customerName) }}</strong>,
                            </p>

                            <p style="margin:0 0 18px;font-size:15px;line-height:1.5;">
                                <strong>Good news! We’ve just added a verified professional in your area who can help
                                    with your
                                    <span style="color:#00AFE3;font-weight:700;">{{ ucfirst($serviceName ?? 'service') }}</span> request.</strong>
                            </p>

                            <!-- BLUE INFO BOX -->
                            @if(!empty($postCode))
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:18px;">
                                <tr>
                                    <td bgcolor="#00AFE3" style="padding:16px;border-radius:8px;">
                                        <p style="margin:0;font-size:14px;color:#ffffff;font-weight:700;line-height:1.4;">
                                            Your quote request now matches a professional covering {{ $postCode }},
                                            and they’re ready to review and respond.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            @endif
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.5;">
                                To keep things moving, please log in and check the professional available for your quote.
                            </p>

                            <!-- CTA BUTTON (CORRECT POSITION) -->
                            <table align="center" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $baseUrl }}/en/gb/login"
                                            style="display:inline-block;background:#FF9A2F;color:#ffffff;text-decoration:none;padding:8px 22px;border-radius:30px;font-size:16px;font-weight:700;">
                                            View Professional
                                        </a>
                                    </td>
                                </tr>
                            </table>

                    <tr>
                        <td height="16"></td>
                    </tr>

            </td>
        </tr>

        <tr>
            <td align="center" bgcolor="#E9F6FB" style="padding:20px;">
                <p style="margin:0 0 6px;font-size:18px;font-weight:800;color:#253238;">
                    Need Help?
                </p>
                <p style="margin:0;font-size:14px;color:#253238;">
                    If you have any questions, please reach out to us at
                    <a href="mailto:contact@localists.com"
                        style="color:#00AFE3;text-decoration:none;font-weight:700;">
                        contact@localists.com
                    </a>
                </p>
            </td>
        </tr>

        <!-- FOOTER -->
        <tr>
            <td align="center" bgcolor="#131838" style="padding:12px;">
                <table cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td style="padding-right:8px;">
                            <img src="{{$siteUrl}}/public/images/globleimg.png"
                                width="18" height="18" style="display:block;">
                        </td>
                        <td style="font-size:13px;color:#ffffff;padding-right:10px;">
                            Localists.com
                        </td>
                        <td style="color:#ffffff;padding:0 10px;">|</td>
                        <td style="padding-right:8px;">
                            <img src="{{$siteUrl}}/public/images/vectorimg.png"
                                width="18" height="14" style="display:block;">
                        </td>
                        <td style="font-size:13px;">
                            <a href="mailto:contact@localists.com"
                                style="color:#ffffff;text-decoration:none;">
                                contact@localists.com
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

    </table>
    <!-- END CARD -->

    </td>
    </tr>
    </table>
</body>

</html>