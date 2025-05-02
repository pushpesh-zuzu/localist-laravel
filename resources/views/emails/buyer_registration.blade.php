<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Welcome to Localist</title>
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
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <img src="{{base_path('assets/image/localist_logo.png')}}" alt="Localist Logo">
    </div>
    <div class="image-section">
      <img src="{{base_path('assets/image/hand_shake.jpg')}}" alt="Welcome Image">
    </div>
    <div class="content">
      <h1>Welcome to Localist</h1>
      <p>Hi {{$name}}, we’ve received your {{$service}} request and have already found 5 professionals that match your criteria. To view them, simply <a href="{{url(env('APP_URL') .'/signin')}}" target="_blank">sign into your account</a>.</p>
      <p>We’ll continue the search to find you the very best professionals in London, and you’ll start hearing from them shortly.</p>
      <p><strong>Please remember:</strong> Professionals on Localist pay to respond to you, so please let each of them know whether they’re right for the job.</p>
      <h3>Your Free Localist Account</h3>
      <p>We’ve created an account for you so that you can better manage this request as well as any future requests you may wish to place. Simply click the button below to log in:</p>
      <p>
        Username: <span class="username">{{$email}}</span> <br>
        Password: <span class="username">{{$password}}</span>
      </p>
      <p>
        Your otp for phone number virification is <span class="username">{{$otp}}</span> <br>
      </p>
      <a href="{{url(env('APP_URL') .'/signin')}}" class="button">Log in to Localist</a>
    </div>
  </div>
</body>
</html>
