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
            font-family: 'Lato', Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: auto;
            width: 100%;
            background: #ffffff;
        }
        .card {
            background: #ffffff;
            padding: 28px;
        }
        h1 {
            font-size: 22px;
            font-weight: 600;
            color: #333333;
            margin: 0 0 10px;
            text-align: center;
        }
        p {
            font-size: 16px;
            line-height: 24px;
            color: #4a4a4a;
            margin: 0 0 16px;
        }
        .highlight {
            color: #00afe3;
            margin-bottom: 16px;
            text-align: center;
            font-size: 16px;
        }
        .btn {
            display: block;
            background-color: #00afe3;
            color: #ffffff !important;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            padding: 14px;
            border-radius: 4px;
            text-align: center;
            width: 100%;
        }
        ul {
            padding-left: 0;
            list-style: none;
        }
        li {
            margin-bottom: 6px;
            font-size: 16px;
        }
        .section-header {
            background-color: #d8edf8;
            color: #1a588c;
            padding: 12px 18px;
            font-size: 16px;
            border-radius: 4px 4px 0 0;
            font-weight: 600;
        }
        .footer {
            text-align: center;
            padding: 15px;
            font-size: 12px;
            color: #666;
        }

        /* MOBILE FIXES */
        @media only screen and (max-width: 600px) {
            .card {
                padding: 20px !important;
            }
            h1 {
                font-size: 20px !important;
            }
            .btn {
                font-size: 16px;
                padding: 14px;
            }
        }
    </style>
</head>

<body>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f2f4; padding:20px 0;">
    <tr>
        <td align="center">

            <!-- MAIN CONTAINER -->
            <table class="container" cellpadding="0" cellspacing="0">
                
                <!-- LOGO -->
                <tr>
                    <td align="center" style="padding: 20px;">
                        <img src="{{$baseUrl}}/assets/localist_logo_1.png" alt="Localists Logo" style="max-width:150px;">
                    </td>
                </tr>

                <!-- CARD 1 -->
                <tr>
                    <td class="card">

                        <h1>Hi {{ $name }},</h1>
                        <p style="text-align:center; font-size:17px;">You’re almost there!</p>

                        <div class="highlight">
                            Just one more step to activate your Localists account.
                        </div>

                        <p>
                            We noticed you started signing up but didn’t finish. Complete your registration to get access to top local professionals — you're just 1 step away.
                        </p>

                        <p>By registering, you can:</p>

                        <ul>
                            <li>✅ Connect with verified local professionals</li>
                            <li>✅ Quickly get responses to your service requests</li>
                            <li>✅ Choose the best provider based on your needs</li>
                        </ul>

                        <a href="{{$baseUrl}}" class="btn">Complete Registration</a>

                    </td>
                </tr>

                <!-- CARD 2 -->
                <tr>
                    <td class="card" style="padding:0;">

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
