<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Complete Your Registration on Localist</title>
  <style>
    body {
      margin: 0;
      background-color: #f1f2f4;
      font-family: 'Lato', Helvetica, Arial, sans-serif;
      font-size: 17px;
      line-height: 26px;
      color: #4a4a4a;
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
      color: #e67e22;
      margin-bottom: 16px;
      font-weight: bold;
    }
    p {
      color: #61696d;
    }
    .btn {
      display: inline-block;
      background-color: #28c199;
      color: #ffffff !important;
      text-decoration: none;
      font-size: 15px;
      font-weight: bold;
      padding: 12px 20px;
      border-radius: 4px;
      margin-top: 16px;
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
        <h1>Hello {{ $name }}, complete your registration</h1>
        <p class="highlight">You're just one step away from connecting with more buyers on Localist!</p>
        <p>We noticed you started signing up but didn't finish. Sellers who complete their profile can:</p>
        <ul>
          <li>✅ Access premium leads instantly</li>
          <li>✅ Appear in more buyer searches</li>
          <li>✅ Track engagement and conversions</li>
        </ul>
        <p>Finish registering and unlock your full potential on Localist.</p>
        <a href="{{$baseUrl}}/resume-registration?email={{$email}}" class="btn">Complete My Registration</a>
      </div>

      <div class="card">
        <h2>Why Localist?</h2>
        <p>Thousands of buyers are looking for trusted sellers like you every day. Don’t miss out on leads just because your profile is incomplete.</p>
        <p>With your name, phone, and email already saved, you can pick up right where you left off!</p>
      </div>

      <div class="card">
        <h2>Need Help?</h2>
        <p>Have questions or need support? We’re here for you.</p>
        <p>Call us:
          <a href="tel:{{\App\Helpers\CustomHelper::setting_value('website_phone_number','+91 0000000000')}}">
            {{\App\Helpers\CustomHelper::setting_value('website_phone_number','+91 0000000000')}}
          </a><br>
          Or email us at
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
