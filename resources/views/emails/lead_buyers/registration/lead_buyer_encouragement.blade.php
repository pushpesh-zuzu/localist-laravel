<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Boost Your Sales with Auto-Buy</title>
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
        .highlight {
            color: #00afe3;
            margin-bottom: 16px;
        }
        p {
            color: #4a4a4a;
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
        }
        .section-header {
            background-color: #d8edf8;
            color: #1a588c;
            padding: 12px 20px;
            margin: -32px -32px 20px -32px;
            border-top-left-radius: 4px;
            border-top-right-radius: 4px;
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
            .section-header {
                font-size: 15px !important;
                padding: 12px 16px !important;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="email-container">

            <div class="logo-container">
                <img src="{{$baseUrl}}/assets/localist_logo.png" alt="Localists Logo" class="logo">
            </div>

            <div class="card">
                <h1>Hi {{ $name }}, ready to unlock more sales?</h1>
                <div class="highlight">Auto-Bid is helping Localists sellers increase sales by up to <strong>35%</strong>!</div>
                <p>Right now, Auto-Bid is turned <strong>off</strong> on your account — which means you might be missing out on top leads.</p>
                <p>Sellers using Auto-Bid get:</p>
                <ul>
                    <li>✅ Up to 35% more sales</li>
                    <li>✅ Faster conversions with automatic matching</li>
                    <li>✅ Hands-free lead purchases 24/7</li>
                </ul>
                <p>Don't miss out on high-intent customers. Enable Auto-Bid and let Localists do the work for you!</p>
                <a href="{{$baseUrl}}/mycredits" style="display: block; background-color: #00afe3; color: #ffffff !important; text-decoration: none; font-size: 16px; font-weight: bold; padding: 14px; border-radius: 4px; margin-top: 20px; text-align: center;"  class="btn">Enable Auto-Bid Now</a>
            </div>

            <div class="card">
                <div class="section-header">How Auto-Bid Works</div>
                <p>Once enabled, Auto-Bid uses your service filters to automatically secure matching leads the moment they come in. No need to log in, no missed opportunities.</p>
                <p>Just add credits and let the system keep your business ahead of the competition — even while you're offline.</p>
                <a href="{{$baseUrl}}/mycredits" style="display: block; background-color: #00afe3; color: #ffffff !important; text-decoration: none; font-size: 16px; font-weight: bold; padding: 14px; border-radius: 4px; margin-top: 20px; text-align: center;" class="btn">Top Up Credits</a>
            </div>

            <div class="card">
                <div class="section-header">Need Help?</div>
                <p>Our team is here to help you make the most of Localists. Email us at
                    <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists')}}">{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists')}}</a>.
                </p>
                <p>Let’s grow your business together 🚀</p>
                <p>— The Localists Team</p>
            </div>

            <div class="footer">
                Manage your email preferences <a href="{{$baseUrl}}/e-mail-notification">here</a>.<br>
                {{\App\Helpers\CustomHelper::setting_value('website_address','')}}
            </div>

        </div>
    </div>
</body>
</html>
