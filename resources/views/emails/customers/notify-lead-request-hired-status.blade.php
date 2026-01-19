<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Localists – Quote Request Status</title>
</head>

<body style="margin:0;padding:0;background:#f4f8fb;font-family:Inter,Arial,Helvetica,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" bgcolor="#f4f8fb">
        <tr>
            <td align="center" style="padding:30px 10px;">
                <table width="600" cellpadding="0" cellspacing="0"
                    style="background:#ffffff;overflow:hidden;box-shadow:0 6px 18px rgba(0,0,0,0.08);">

                    <!-- HEADER -->
                    <tr>
                        <td bgcolor="#00AFE3" align="center" style="padding:14px;">
                            <img src="{{$baseUrl}}/assets/localist_logo_1.png"
                                height="28"
                                alt="Localists"
                                style="display:block;">
                        </td>
                    </tr>

                    <!-- CONTENT -->
                    <tr>
                        <td style="padding:30px 40px 20px;">

                            <p style="margin:0 0 12px;font-size:16px;color:#253238;">
                                Welcome back <strong>{{ ucfirst($customerName) }}</strong>,
                            </p>

                            <p style="margin:0 0 12px;font-size:14px;color:#00AFE3;font-weight:700;">
                                We hope your <strong>{{ ucfirst($serviceName ?? 'service') }}</strong> quote request is going well.
                            </p>

                            @if($nextStep == '4')
                            <p style="margin:0 0 12px;font-size:14px;color:#253238;line-height:1.6;">
                                Your quote request is now closed.
                            </p>

                            <p style="margin:0 0 20px;font-size:14px;color:#253238;line-height:1.6;">
                                To help us keep our platform accurate and improve the quality of professionals, could you please let us know the outcome of your request?
                            </p>
                            @else
                            <p style="margin:0 0 20px;font-size:14px;color:#253238;line-height:1.6;">
                                To help us keep our platform accurate and improve the quality of professionals,
                                could you please let us know the current status of your request?
                            </p>
                            @endif

                            <!-- OPTIONS BOX -->
                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="background:#E9F6FB;border-radius:12px;padding:20px;">
                                <tr>
                                    <td>

                                        <p style="margin:0 0 14px;font-size:16px;font-weight:700;color:#253238;">
                                            Please select one option below:
                                        </p>

                                        <!-- OPTION -->
                                        @if(!empty($sellers))
                                        @foreach($sellers as $seller)
                                        <table width="100%" cellpadding="0" cellspacing="0"
                                            style="background:#ffffff;border-radius:8px;margin-bottom:10px;">
                                            <tr>
                                                <td>
                                                    <a href="{{ url('/api/customer-lead-status-update/' . $leadId . '/' . $seller->id . '/' . $customerId . '/hired') }}"
                                                        style="display:block;padding:12px;font-size:13px;color:#000000; text-decoration:none;">
                                                        {{ ucfirst($seller->name) }}
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                        @endforeach
                                        @endif

                                        <table width="100%" cellpadding="0" cellspacing="0"
                                            style="background:#ffffff;border-radius:8px;margin-bottom:10px;">
                                            <tr>
                                                <td>
                                                    <a href="{{ url('/api/customer-lead-status-update/' . $leadId . '/0/' . $customerId . '/someone_else') }}"
                                                        style="display:block;padding:12px;font-size:13px;color:#000000;text-decoration:none;">
                                                        Someone not on Localists
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:20px 0 6px;text-align:center;font-size:16px;font-weight:700;color:#00AFE3;">
                                Your response helps us improve our service
                            </p>

                            <p style="margin:0;text-align:center;font-size:16px;color:#253238;font-weight:700;line-height: 30px;">
                                Thank you for choosing Localists.
                            </p>

                        </td>
                    </tr>

                    <!-- HELP -->
                    <tr>
                        <td align="center" bgcolor="#E9F6FB" style="padding:14px;">
                            <p style="margin:0 0 6px;font-size:16px;font-weight:800;color:#253238;">
                                Need Help?
                            </p>
                            <p style="margin:0;font-size:13px;color:#253238;">
                                Email us at:
                                <a href="mailto:contact@localists.com"
                                    style="color:#00AFE3;text-decoration:none;font-weight:700;">
                                    contact@localists.com
                                </a>
                            </p>
                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td align="center" bgcolor="#131838" style="padding:10px 18px;">
                            <table cellspacing="0" cellpadding="0" border="0" align="center">
                                <tr>
                                    <td style="padding-right:8px;">
                                        <img src="{{$siteUrl}}/public/images/globleimg.png"
                                            width="18" height="18" style="display:block;">
                                    </td>
                                    <td style="font-size:13px;color:#ffffff;padding-right:10px;">
                                        Localists.com
                                    </td>
                                    <td style="padding:0 10px;color:#ffffff;">|</td>
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

                    <tr>
                        <td align="center" style="padding:12px 16px; background:#f4f6f8;">
                            <p style="margin:0; font-size:11px; line-height:16px; color:#6b7280; text-align:center;">
                                Click here to 
                                <a href="{{ url('/api/unsubscribe-status-update/' . $customerId . '/user') }}"
                                    style="color:#00AFE3; text-decoration:underline; font-weight:600;">
                                    unsubscribe
                                </a> and we will remove you from our emailing list.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>