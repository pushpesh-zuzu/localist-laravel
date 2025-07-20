<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Lead Matched for You</title>
    <style>
        body {
            margin: 0;
            background-color: #f1f2f4;
            font-family: 'Lato', Helvetica, Arial, sans-serif;
            font-size: 18px;
            font-weight: 510;
            line-height: 25px;
            color: #4a4a4a;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            padding: 32px 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 0 16px;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            max-height: 50px;
        }
        .card {
            background: #ffffff;
            padding: 32px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            margin-bottom: 20px;
        }
        h1 {
            font-size: 22px;
            font-weight: 600;
            color: #333;
            margin: 0 0 10px;
        }
        p {
            color: #61696d;
        }
        .section-header {
            background-color: #d8edf8;
            color: #1a588c;
            padding: 12px 20px;
            margin: -32px -32px 20px -32px;
            border-top-left-radius: 4px;
            border-top-right-radius: 4px;
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            background-color: #28c199;
            color: #ffffff !important;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            padding: 10px 18px;
            border-radius: 4px;
            margin-top: 20px;
        }
        .lead-detail {
            background-color: #f5f9fc;
            padding: 16px;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 16px;
        }
        .credit-box {
            background-color: #fdecea;
            color: #b91c1c;
            padding: 6px 12px;
            border-radius: 4px;
            display: inline-block;
            font-weight: bold;
            margin-top: 10px;
        }
        .highlight {
            color: #28c199;
        }
        .footer {
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        a {
            color: #007bff;
        }
        @media only screen and (max-width: 600px) {
            .card {
                padding: 24px 20px !important;
            }
            .btn {
                display: block;
                width: 100%;
                text-align: center;
                padding: 12px 0;
                font-size: 16px;
            }
            h1 {
                font-size: 20px !important;
            }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="email-container">

        <div class="logo-container">
            <img src="{{$baseUrl}}/assets/localist_logo.png" alt="Localist Logo" class="logo">
        </div>

        <div class="card">
            <h1>Hi {{ $name }}, you've got a new lead!</h1>
            <p>This customer is looking for <strong></strong>.</p>

            <div class="lead-detail">
                <strong>Lead Name:</strong> <br>
                <strong>Location ID:</strong> <br>
                <strong>Phone:</strong> <br>
                <strong>Email:</strong> <br>
                <strong>Details:</strong> <br>
                <strong>Timing:</strong>
            </div>

            <div class="credit-box">
                 Credits Required
            </div>

            @if($hasEnoughCredits)
                <a href="{{$baseUrl}}/leads" class="btn">Contact Lead Now</a>
            @else
                <a href="{{$baseUrl}}/mycredits" class="btn">Top Up Credits to Contact</a>
            @endif

            <p class="highlight" style="margin-top: 16px;">
                 other professionals have viewed this lead. Act fast!
            </p>
        </div>

        <div class="card">
            <div class="section-header">Need Help?</div>
            <p>Call us at
                <a href="tel:{{\App\Helpers\CustomHelper::setting_value('website_phone_number','+91 0000000000')}}">
                    {{\App\Helpers\CustomHelper::setting_value('website_phone_number','+91 0000000000')}}
                </a> or email
                <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','india@localist.com')}}">
                    {{\App\Helpers\CustomHelper::setting_value('website_email','india@localist.com')}}
                </a>.
            </p>
        </div>

        <div class="footer">
            Manage your email preferences <a href="{{$baseUrl}}/e-mail-notification">here</a>.<br>
            {{\App\Helpers\CustomHelper::setting_value('website_address','')}}
        </div>

    </div>
</div>
</body>
</html>
