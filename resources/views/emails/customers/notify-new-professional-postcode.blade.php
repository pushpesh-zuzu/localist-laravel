<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>A Verified Professional Is Now Available</title>

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

    table { border-collapse: collapse !important; }
    img { border: 0; display: block; height: auto; line-height: 100%; max-width: 100%; }

    .email-container {
        max-width: 600px;
        width: 100%;
        margin: 0 auto;
    }

    /* ★ CLEAN NORMAL BUTTON (desktop) ★ */
    .btn {
        background: #00AFE3;
        color: #fff !important;
        text-decoration: none;
        padding: 10px 24px;
        border-radius: 4px;
        font-size: 15px;
        font-weight: bold;
        display: inline-block;
        text-align: center;
    }

    /* ★ Mobile Fixes (no full width button) ★ */
    @media only screen and (max-width: 600px) {
        .email-container { padding: 0 12px !important; }
        .card-pad { padding: 16px !important; }

        .btn {
            font-size: 15px !important;
            padding: 12px 20px !important;
            width: auto !important;
            display: inline-block !important; /* Prevent full width */
        }
    }
  </style>
</head>

<body style="margin:0; padding:0; background:#f1f2f4;">

<!-- Full Page Wrapper -->
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" bgcolor="#f1f2f4">
  <tr>
    <td align="center">

      <!-- Email Body -->
      <table role="presentation" width="600" cellspacing="0" cellpadding="0" class="email-container">

        <!-- Logo -->
        <tr>
          <td align="center" style="padding: 18px;">
            <img src="{{ $baseUrl }}/assets/localist_logo_1.png" alt="Localists Logo" style="max-height:45px;">
          </td>
        </tr>

        <!-- Main Card -->
        <tr>
          <td style="padding: 25px 30px; background: #ffffff; border-radius: 6px;">
            
            <p style="font-size: 22px; font-weight: 600; color: #333333; margin:0 0 12px;">
              Hi {{ ucfirst($customerName) }},
            </p>

            <p style="margin: 0 0 12px;">
              Good news! We’ve just added a verified professional in your area who can help with your 
              <b>{{ ucfirst($serviceName ?? 'your service') }}</b> request.
            </p>

            @if(!empty($postCode))
            <p style="margin: 0 0 20px;">
              Your quote request now matches a professional covering <b>{{ $postCode }}</b>, and they’re ready to review and respond.
            </p>
            @endif

            <p style="margin: 0 0 22px;">
              To keep things moving, please log in and check the professional available for your quote.
            </p>

            <p style="text-align: center; margin: 0;">
              <a href="{{ $baseUrl }}/en/gb/login" class="btn">
                View Professional
              </a>
            </p>

          </td>
        </tr>

        <!-- Help Block -->
        <tr>
          <td style="padding: 16px;">
            <table role="presentation" width="100%" bgcolor="#d8edf8" style="border-radius: 6px;">
              <tr>
                <td style="padding: 19px 16px; font-size:16px; line-height:23px;">

                  <p style="margin: 0 0 10px;">
                    If you need any assistance, our team is here to help:  
                    <a href="mailto:{{ \App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com') }}" style="color:#1a588c;">
                      {{ \App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com') }}
                    </a>
                  </p>

                  <p style="margin: 0;">
                    Kind Regards,<br>
                    Localists Team
                  </p>

                </td>
              </tr>
            </table>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
