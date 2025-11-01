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
        .wrapper { width: 100%; padding: 32px 0; }
        .email-container { max-width: 600px; margin: 0 auto; padding: 0 16px; }
        .logo-container { text-align: center; margin-bottom: 20px; }
        .logo { max-height: 50px; }
        .card {
            background: #ffffff;
            padding: 32px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            margin-bottom: 20px;
        }
        h1 { font-size: 22px; font-weight: 600; color: #333; margin: 0 0 10px; }
        .highlight { color: #00afe3; margin-bottom: 16px; }
        p { color: #61696d; }
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
        a { color: #007bff; }
        @media only screen and (max-width: 600px) {
            .card { padding: 24px 20px !important; }
            .btn { display: block; width: 100%; text-align: center; padding: 12px 0; font-size: 16px; }
            h1 { font-size: 20px !important; }
            .section-header { font-size: 15px !important; padding: 12px 16px !important; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="email-container">

            <div class="logo-container">
                <img src="{{$baseUrl}}/assets/localist_logo_1.png" alt="Localist Logo" class="logo">
            </div>

            <div class="card">
                <h1>Welcome to Localists, {{ $name }}</h1>
                <div class="highlight">Your account has been created successfully!</div>
                <p>You can now log in and start managing your requests and profile.</p>
            </div>

            <div class="card">
                <div class="section-header">Your Account Details</div>
                <p>Please use the following credentials to log in:</p>
                <p>
                  Email: {{$email}} <br/>
                  Password: <strong>{{$password}}</strong>
                </p>
                <a href="{{$baseUrl}}/login" class="btn">Log in to Localists</a>
            </div>

            <div class="card">
                <div class="section-header">Manage Your Requests</div>
                <p style="margin-bottom:0px">Create & manage your requests from  <a href="{{$baseUrl}}/buyers/create">My Request Panel</a></p>
            </div>

            <div class="card">
                <div class="section-header">Help</div>
                <p>If you have any questions, please reach out to us at
                  <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}">{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}</a>.</p>
                <p>Regards,<br>Localists Team</p>
            </div>

            <div class="footer">
                Manage your email preferences <a href="{{$baseUrl}}/settings/notifications/e-mail-notification">here</a>.<br>
                {{\App\Helpers\CustomHelper::setting_value('website_address','')}}
            </div>

        </div>
    </div>
</body>
</html>
