<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome to Localists</title>
    <style>
        body {
            margin: 0;
            background-color: #f1f2f4;
            font-family: 'Lato',Helvetica,Arial,sans-serif;
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
            color: ##61696d;

        }

        .btn {
            display: inline-block;
            background-color: #3399ff;
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
                <h1>Welcome to Localists, {{ $name }}</h1>
                <div class="highlight">We're excited to start helping you grow your business!</div>
                <p>We'll now email you targeted leads from new customers. Ensure you get the right leads by confirming your lead preferences now.</p>
                <a href="{{$baseUrl}}/leads/settings" class="btn">Confirm lead preferences</a>
            </div>


            <div class="card">
                <div class="section-header">Your account</div>
                <p>You can log in to your account and manage your leads anytime:</p>
                <p>
                  Email: {{$email}} <br/>
                  Password: <strong> {{$password}}</strong>
                </p>
                <a href="{{$baseUrl}}/login" class="btn">Log in to Localists</a>
            </div>

            <div class="card">
                <div class="section-header">Sevice and Locations</div>
                <p>You have registered for following service(s) having {{$jobs}} jobs.</p>
                <ul>
                    @foreach($services as $s)
                        <li>{{$s}}</li>
                    @endforeach
                </ul>
                <p>You can change anytime form <a href="{{$baseUrl}}/leads/settings">My Services</a> page</p>
            </div>

            <div class="card">
                <div class="section-header">How We Work</div>
                <table style="width: 100%; font-size: 16px; color: #4a4a4a;">
                    <tr>
                        <td style="vertical-align: top; padding-right: 12px; width: 24px;">
                            <div style="background-color: #00adef; color: #fff; border-radius: 50%; width: 24px; height: 24px; text-align: center; font-size: 14px; line-height: 24px;">1</div>
                        </td>
                        <td>
                            <strong>Customers tell us what they need</strong><br>
                            <span>Local customers share the services they’re looking for by answering key questions relating to the service.</span>
                        </td>
                    </tr>
                    <tr><td colspan="2" style="border-bottom: 1px solid #ddd; padding: 12px 0;"></td></tr>
                    <tr>
                        <td style="vertical-align: top; padding-right: 12px; width: 24px;">
                            <div style="background-color: #00adef; color: #fff; border-radius: 50%; width: 24px; height: 24px; text-align: center; font-size: 14px; line-height: 24px;">2</div>
                        </td>
                        <td>
                            <strong>Localists.com finds the right leads for you</strong><br>
                            <span>We match your business with leads that fit your services and location, delivered instantly to your inbox and dashboard.</span>
                        </td>
                    </tr>
                    <tr><td colspan="2" style="border-bottom: 1px solid #ddd; padding: 12px 0;"></td></tr>
                    <tr>
                        <td style="vertical-align: top; padding-right: 12px; width: 24px;">
                            <div style="background-color: #00adef; color: #fff; border-radius: 50%; width: 24px; height: 24px; text-align: center; font-size: 14px; line-height: 24px;">3</div>
                        </td>
                        <td>
                            <strong>You review and select your leads</strong><br>
                            <span>See full customer details straight away and choose the opportunities that work best for your business.</span>
                        </td>
                    </tr>
                    <tr><td colspan="2" style="border-bottom: 1px solid #ddd; padding: 12px 0;"></td></tr>
                    <tr>
                        <td style="vertical-align: top; padding-right: 12px; width: 24px;">
                            <div style="background-color: #00adef; color: #fff; border-radius: 50%; width: 24px; height: 24px; text-align: center; font-size: 14px; line-height: 24px;">4</div>
                        </td>
                        <td>
                            <strong>You connect with the customer directly</strong><br>
                            <span>Reach out by phone or email to introduce your services and secure new business.</span>
                        </td>
                    </tr>
                    <tr><td colspan="2" style="border-bottom: 1px solid #ddd; padding: 12px 0;"></td></tr>
                    <tr>
                        <td style="vertical-align: top; padding-right: 12px; width: 24px;">
                            <div style="background-color: #00adef; color: #fff; border-radius: 50%; width: 24px; height: 24px; text-align: center; font-size: 14px; line-height: 24px;">5</div>
                        </td>
                        <td>
                            <strong>You win new work — no hassle</strong><br>
                            <span>No hidden costs or long-term commitment. There are no commissions or extra costs — just a clear, simple way to grow your business through Localists.com.</span>
                        </td>
                    </tr>
                </table>
            </div>


            <div class="card">
                <div class="section-header">Important Pages</div>
                <ol>
                    <li><a href="{{$baseUrl}}/sellers/dashboard">Dashboard</a></li>
                    <li><a href="{{$baseUrl}}/sellers/leads">Leads</a></li>
                    <li><a href="{{$baseUrl}}/settings/profile/my-profile">My Profile</a></li>
                    <li><a href="{{$baseUrl}}/settings/leads/my-services">My Services</a></li>
                    <li><a href="{{$baseUrl}}/settings/billing/my-credits">My Credits</a></li>
                    <li><a href="{{$baseUrl}}/settings/billing/invoice-billing-details">Invoices & Billing Details</a></li>
                </ol>
                <p>You currently have <strong>0 credits</strong> in your account. To start receiving quality leads and allow our system to auto-bid on your behalf please purchase credits now.</p>
                <p><strong>Credits unlock opportunities. Don’t miss out!</strong><br>
                  Secure your next customer by topping up your credits today.</p>
                <a href="{{$baseUrl}}/mycredits" class="btn">Buy Credits</a>
                <p>You can also call our customer support team at
                  <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists')}}">{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists')}}</a>.</p>
                <p>Regards,<br>Localists Team</p>
            </div>

            <div class="footer">
                Manage your email preferences <a href="{{$baseUrl}}/e-mail-notification">here</a>.<br>
                {{\App\Helpers\CustomHelper::setting_value('website_address','')}}
            </div>

        </div>
    </div>
</body>
</html>
