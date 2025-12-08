
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>A Verified Professional Is Now Available</title>
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

        /* UPDATED BUTTON FIX */
        .btn {
    display: block;
    max-width: 260px; /* reduced width */
    width: 100%;
    text-align: center;
    background-color: #3399ff;
    color: #ffffff !important;
    text-decoration: none;
    font-size: 16px;
    font-weight: bold;
    padding: 12px 0;
    border-radius: 4px;
    /* margin: 12px auto 0 auto; center the button */
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
            .btn { width: 100% !important; font-size: 16px !important; }
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
               <p>Hi <strong style="color: #333;">{{ ucfirst($customerName) }}</strong>,</p>
                <p> Good news! We’ve just added a verified professional in your area who can help with your 
              <b>{{ ucfirst($serviceName ?? 'your service') }}</b> request.</p>

              @if(!empty($postCode))
            <p style="margin: 0 0 20px;">
              Your quote request now matches a professional covering <b>{{ $postCode }}</b>, and they’re ready to review and respond.
            </p>
            @endif

            <p style="margin: 0 0 22px;">
              To keep things moving, please log in and check the professional available for your quote.
            </p>


           <p style="text-align: center; margin: 0; width: 100%;">
          <a href="{{ $baseUrl }}/en/gb/login" class="btn" style="display: inline-block;">
            View Professional
          </a>
        </p>
            </div>

            <div class="card">
                <div class="section-header">Help</div>
                <p>If you have any questions, please reach out to us at
                  <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}">{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}</a>.</p>
                <p>Kind Regards,<br>Localists Team</p>
            </div>

        </div>
    </div>
</body>
</html>


