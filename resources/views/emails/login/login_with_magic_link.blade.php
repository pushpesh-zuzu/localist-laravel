<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Welcome to Localists</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f2f2f2;
    }
    .container {
      max-width: 600px;
      margin: 20px auto;
      background-color: #ffffff;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .header {
      text-align: center;
      padding: 20px;
    }
    .header img {
      max-width: 100px;
    }
    .image-section img {
      width: 100%;
      height: auto;
    }
    .content {
      padding: 20px;
      color: #333333;
    }
    .content h1 {
      font-size: 24px;
      margin-bottom: 20px;
    }
    .content p {
      font-size: 16px;
      line-height: 1.5;
    }
    .button {
      display: inline-block;
      margin-top: 20px;
      padding: 10px 20px;
      background-color: #1f6bf0;
      color: #ffffff !important;
      text-decoration: none;
      border-radius: 5px;
      font-weight: bold;
    }
    .username {
      font-weight: bold;
      color: #1f6bf0;
    }

    .card {
            background: #ffffff;
            padding: 32px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            margin-bottom: 20px;
        }

         .section-header {
            background-color: #d8edf8;
            color: #1a588c;
            padding: 12px 20px;
            margin: -32px -11px 20px -12px;
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
            .btn { width: 100% !important; font-size: 16px !important; }
            h1 { font-size: 20px !important; }
            .section-header { font-size: 15px !important; padding: 12px 16px !important; }
        }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
       <img src="{{ $baseUrl }}/assets/localist_logo_1.png" alt="Localists Logo" style="max-height: 50px;">
    </div>
    <div class="content">
      <p>Hi  <strong style="color: #333;">{{ ucfirst($name) }}</strong>,</p>
               
      <p>Use the link below to log in to your account </p>
      <a href="{{ $baseUrl }}/en/gb/login?client_id={{base64_encode($token)}}" style="display: block; background-color: #00afe3; color: #ffffff !important; text-decoration: none; font-size: 16px; font-weight: bold; padding: 14px; border-radius: 4px; margin-top: 20px; text-align: center;">Log In Now</a>
    </div>
    
    <div class="card">
                <div class="section-header">Help</div>
                <p>If you have any questions, please reach out to us at
                  <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}">{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}</a>.</p>
                <p>Kind Regards,<br>Localists Team</p>
            </div>

            <div class="footer">
                Manage your email preferences <a href="{{$baseUrl}}/settings/notifications/e-mail-notification">here</a>.<br>
                {{\App\Helpers\CustomHelper::setting_value('website_address','')}}
            </div>
      </div>
  </div>
</body>
</html>
