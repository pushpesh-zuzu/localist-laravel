<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Registration</title>
    <style>
        /* Basic resets */
        body {
            margin: 0;
            padding: 0;
            background: #f3fbfe;
            /* slightly softer */
            -webkit-text-size-adjust: 100%;
        }

        table {
            border-collapse: collapse;
        }

        img {
            display: block;
            border: 0;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        .container {
            width: 600px;
            max-width: 600px;
        }

        /* Typography */
        .body-text {
            font-family: Inter, Arial, Helvetica, sans-serif;
            color: #253238;
        }

        /* Responsive */
        @media only screen and (max-width:600px) {
            .container {
                width: 100% !important;
                max-width: 100% !important;
            }

            .pad-outer {
                padding-left: 35px !important;

            }

            .hide-mobile {
                display: none !important;
            }

            .benefit-td {
                display: block;
                width: 100% !important;
            }

            .benefit-table {
                width: 100% !important;
            }

            .title {
                font-size: 20px !important;
            }

            .greet {
                font-size: 16px !important;
            }

            .subtitle {
                font-size: 14px !important;
            }

            .cta-link {
                display: inline-block !important;
            }
        }

        /* Small utility classes */
        .center {
            text-align: center;
        }

        .bold {
            font-weight: 700;
        }
    </style>
</head>

<body>
    <table width="100%" bgcolor="#f3fbfe" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table class="container" bgcolor="#ffffff" cellpadding="0" cellspacing="0" style="max-width:600px;  overflow:hidden;">
                    <tr>
                        <td align="center" style="padding:20px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td bgcolor="#00AFE3" height="40" align="center" style="border-radius:5px;">
                                        <img src="{{$baseUrl}}/assets/localist_logo_1.png" height="26" alt="Localists">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="pad-outer body-text" style="padding:14px 48px 22px; color:#253238;">
                            <div style="font-size:16px; line-height:22px;" class="greet">Hi, <strong style="font-weight:800;">{{ ucfirst($name) }}</strong>,</div>
                            <div style="height:8px; font-size:0; line-height:0;">&nbsp;</div>
                            <div style="font-size:13px; font-weight:600; line-height:18px; color:#444; margin-top:4px;">You’re almost there!</div>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:0 18px 24px;">
                            <table width="100%" cellpadding="0" cellspacing="0" bgcolor="#e9f7fd" style="border-radius:8px;">
                                <tr>
                                    <td style="padding:22px 20px 26px;" class="body-text">
                                        <div class="center title" style="font-size:18px; font-weight:900; color:#253238; line-height:26px;">
                                            @if($variant === 'even')
                                            Just one more step to activate your Localists<br>account.
                                            @else
                                            Just one more step to complete your free quote<br> request.
                                            @endif
                                        </div>
                                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:14px;">
                                            <tr>
                                                <td align="center">
                                                    <table cellpadding="0" cellspacing="0" style="max-width:540px;">
                                                        <tr>
                                                            <td bgcolor="#00AFE3" style="padding:12px 18px; border-radius:6px; font-size:12px;  color:#ffffff; text-align:center; line-height:18px;">
                                                                @if($variant === 'even')
                                                                We noticed you started signing up but didn’t finish. Complete your registration to get access to top local professionals — you're just 1 step away.

                                                                @else

                                                                We noticed you started requesting a quote for {{ $serviceName ?? 'a service' }}, but didn’t finish.
                                                                Complete your free quote request now to receive responses from top local professionals — you’re just 1 step away.

                                                                @endif



                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                        <table width="100%" style="margin-top:14px;">
                                            <tr>
                                                <td height="8"></td>
                                            </tr>
                                        </table>
                                        <div class="center" style="font-size:14px; font-weight:800; color:#00AFE3; margin-top:6px;">
                                            @if($variant === 'even')
                                            By registering, you can:
                                            @else
                                            By completing your free quote request, you can:
                                            @endif
                                        </div>

                                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;">
                                            <tr>

                                                <!-- BENEFIT 1 -->
                                                <td width="33.33%" align="center" valign="top" style="padding:6px 4px;">
                                                    <img src="{{$siteUrl}}/public/images/group.png" width="64" height="64">
                                                    <div style="padding-top:8px; font-size:12px; line-height:16px; color:#253238;">
                                                        Connect with<br>verified local<br>professionals
                                                    </div>
                                                </td>

                                                <!-- BENEFIT 2 -->
                                                <td width="33.33%" align="center" valign="top" style="padding:6px 4px;">
                                                    <img src="{{$siteUrl}}/public/images/quickly-get.png" width="64" height="64">
                                                    <div style="padding-top:8px; font-size:12px; line-height:16px; color:#253238;">
                                                        Quickly get<br>responses to your<br>service requests
                                                    </div>
                                                </td>

                                                <!-- BENEFIT 3 -->
                                                <td width="33.33%" align="center" valign="top" style="padding:6px 4px;">
                                                    <img src="{{$siteUrl}}/public/images/choose-the-best.png" width="64" height="64">
                                                    <div style="padding-top:8px; font-size:12px; line-height:16px; color:#253238;">
                                                        Choose the best<br>provider based on<br>your needs
                                                    </div>
                                                </td>

                                            </tr>
                                        </table>



                                        <!-- CTA BUTTON -->
                                        <table align="center" cellpadding="0" cellspacing="0" style="margin-top:18px;">
                                            <tr>
                                                <td bgcolor="#ff9933" style="padding:8px 22px; border-radius:100px;">
                                                    <a class="cta-link" href="{{$baseUrl}}" style="font-size:15px; font-weight:800; color:#ffffff; text-decoration:none; display:inline-block;">
                                                        @if($variant === 'even')
                                                        Complete Registration
                                                        @else
                                                        Complete My Free Quote Request
                                                        @endif
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>

                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- HELP -->
                    <tr>
                        <td align="center" style="padding:18px 20px 10px;">
                            <div class="body-text" style="font-size:12px; font-weight:600; color:#253238; line-height:18px;">

                                @if($variant === 'even')
                                Our team is here to assist you. Email us at <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}" style="color:#00AFE3; text-decoration:none;">{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}</a>
                                @else
                                Our team is here to help. <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}" style="color:#00AFE3; text-decoration:none;">{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}</a>
                                @endif



                            </div>
                        </td>
                    </tr>

                    <!-- EMAIL STRIP -->
                    <tr>
                        <td align="center" bgcolor="#e9f7fd" style="padding:16px 30px; font-family:Inter,Arial,sans-serif; font-size:12px; font-weight:700; color:#00afe3;">
                            <span style="display:inline-block; padding-bottom:1px; color:#253238; font-weight:700;">


                                @if($variant === 'even')
                                We’re excited to help you grow
                                @else
                                We’re excited to help you find the right professional.

                                @endif

                            </span>
                        </td>
                    </tr>

                    <!-- DARK FOOTER -->
                    <tr>
                        <td align="center" bgcolor="#131838" style="padding:9px 18px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center">
                                <tr>
                                    <!-- Globe -->
                                    <td valign="middle" style="padding-right:8px;">
                                        <img src="{{$siteUrl}}/public/images/globleimg.png" width="19" height="19" alt="" style="display:block;">
                                    </td>

                                    <!-- Website text -->
                                    <td valign="middle" style="font-size:13px; line-height:18px; color:#ffffff; font-family:Inter, Arial, sans-serif; padding-right:10px;">
                                        Localists.com
                                    </td>

                                    <!-- Divider -->
                                    <td valign="middle" style="padding:0 10px; font-size:13px; line-height:18px; color:#ffffff;">|</td>

                                    <!-- Email icon -->
                                    <td valign="middle" style="padding-right:8px;">
                                        <img src="{{$siteUrl}}/public/images/vectorimg.png" width="18" height="14" alt="" style="display:block;">
                                    </td>

                                    <!-- Email text -->
                                    <td valign="middle" style="font-size:13px; line-height:18px; font-family:Inter, Arial, sans-serif;">
                                        <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}" style="color:#ffffff; text-decoration:none;">
                                            {{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>