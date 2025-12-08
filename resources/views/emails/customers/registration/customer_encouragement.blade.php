<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complete Your Registration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            margin: 0;
            background-color: #f1f2f4;
            font-family: 'Lato', Helvetica, Arial, sans-serif !important;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            width: 100%;
            margin: auto;
            background: #ffffff;
        }
        .card {
            background: #ffffff;
            padding: 24px;
        }
        h1 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin: 0 0 10px;
            text-align: center;
        }
        p {
            font-size: 16px;
            line-height: 23px;
            /* color: #4a4a4a; */
            margin: 0 0 14px;
        }
        .highlight {
            color: #00afe3;
            margin-bottom: 14px;
            font-size: 16px;
            text-align: center;
        }
        .section-header {
            background-color: #d8edf8;
            color: #1a588c;
            padding: 12px 18px;
            font-size: 16px;
            font-weight: 600;
        }
        ul {
            padding-left: 0;
            list-style: none;
        }
        li {
            font-size: 16px;
            margin-bottom: 6px;
        }
        .footer {
            text-align: center;
            padding: 15px;
            font-size: 12px;
            color: #666;
        }

        /* MOBILE RESPONSIVE FIX */
        @media only screen and (max-width: 600px) {
            .card {
                padding: 20px !important;
            }
            h1 {
                font-size: 20px !important;
            }
            p {
                font-size: 15px !important;
            }
            .mobile-btn {
                font-size: 16px !important;
                padding: 14px !important;
            }
        }
    </style>
</head>

<body>

<table width="100%" cellpadding="0" cellspacing="0" bgcolor="#f1f2f4" style="padding:20px 0;">
    <tr>
        <td align="center">

            <table class="container" cellpadding="0" cellspacing="0">

                <!-- LOGO -->
                <tr>
                    <td align="center" style="padding: 20px;">
                        <img src="{{$baseUrl}}/assets/localist_logo_1.png" alt="Localists Logo" style="max-width:150px;">
                    </td>
                </tr>

                <!-- MAIN CARD -->
                <tr>
                    <td class="card">

                        <p style="text-align:center;">Hi <strong style="color: #333;">{{ ucfirst($name) }}</strong>,</p>
                        <p style="text-align:center;">You’re almost there!</p>

                        <div class="highlight">Just one more step to activate your Localists account.</div>

                        <p>
                            We noticed you started signing up but didn’t finish. Complete your registration to get access to top local professionals — you're just 1 step away.
                        </p>

                        <p>By registering, you can:</p>

                        <ul>
                            <li>✅ Connect with verified local professionals</li>
                            <li>✅ Quickly get responses to your service requests</li>
                            <li>✅ Choose the best provider based on your needs</li>
                        </ul>

                        <!-- BUTTON FIX (Email-Safe Table Structure) -->
                       <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin-top:20px; width:100%;">
                        <tr>
                            <td align="center">
                                <a 
                                    href="{{$baseUrl}}"
                                    style="
                                        background-color:#00afe3;
                                        color:#ffffff;
                                        text-decoration:none;
                                        font-size:16px;
                                        font-weight:bold;
                                        padding:14px 0;
                                        display:inline-block;
                                        border-radius:4px;
                                        width:260px;        /* FIXED BUTTON WIDTH */
                                        max-width:100%;     /* AUTO-SHRINK ON SMALL SCREENS */
                                        text-align:center;
                                        box-sizing:border-box;
                                    "
                                >
                                    Complete Registration
                                </a>
                            </td>
                        </tr>
                    </table>

                    </td>
                </tr>

                <!-- HELP SECTION -->
                <tr>
                    <td style="padding:0;">

                        <div class="section-header">Need Help?</div>

                        <div style="padding:20px;">
                            <p>
                                Our team is here to assist you. Email us at
                                <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}">
                                    {{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}
                                </a>.
                            </p>

                            <p>We’re excited to help you grow 🚀</p>
                            <p>Kind Regards,<br>Localists Team</p>
                        </div>

                    </td>
                </tr>

                <!-- FOOTER -->
                <tr>
                    <td class="footer">
                        Manage your email preferences 
                        <a href="{{$baseUrl}}/user/notification">here</a><br>
                        {{\App\Helpers\CustomHelper::setting_value('website_address','')}}
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
