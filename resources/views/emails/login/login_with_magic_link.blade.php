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
       <img src="{{ $baseUrl }}/assets/localist_logo.png" alt="Localist Logo" style="max-height: 50px;">
    </div>
    <div class="content">
      <h1>Hi {{$name}}</h1>
      <p>Use the link below to log in to your account </p>
      <a href="{{ $baseUrl }}/login?clien_id={{base64_encode($token)}}" style="display: block; background-color: #00afe3; color: #ffffff !important; text-decoration: none; font-size: 16px; font-weight: bold; padding: 14px; border-radius: 4px; margin-top: 20px; text-align: center;">Log In Now</a>
    </div>
  </div>
</body>
</html>
