<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Complete Your Registration on Localists</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <style>
    body {
      margin: 0;
      background-color: #f1f2f4;
      font-family: 'Lato', Helvetica, Arial, sans-serif;
      font-size: 17px;
      line-height: 26px;
      color: #4a4a4a;
      -webkit-font-smoothing: antialiased;
    }
    img { border:0; display:block; }
    a { color: #007bff; }

    .btn {
      display: inline-block;
      background-color: #00afe3;
      color: #ffffff !important;
      text-decoration: none;
      font-size: 15px;
      font-weight: bold;
      padding: 12px 20px;
      border-radius: 4px;
      -webkit-text-size-adjust: none;
    }

    h1 { font-size:22px; font-weight:600; color:#333; margin:0 0 10px 0; text-align:center; font-family:Helvetica, Arial, sans-serif; }
    h2 { font-size:18px; font-weight:600; color:#333; margin:0 0 10px 0; font-family:Helvetica, Arial, sans-serif; }
    p { color:#4a4a4a; margin:0 0 12px 0; font-size:15px; line-height:1.5; font-family:Helvetica, Arial, sans-serif; }
    .highlight { color:#00afe3; margin-bottom:16px; font-weight:bold; text-align:center; }

    @media only screen and (max-width:600px) {
      .email-container { width:100% !important; padding:0 12px !important; }
      .card-td { padding:20px !important; }
      .btn { font-size:16px !important; padding:14px 20px !important; }
      h1 { font-size:20px !important; }
    }
  </style>
</head>
<body>
  <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f1f2f4" role="presentation">
    <tr>
      <td align="center">

        <table width="600" cellpadding="0" cellspacing="0" border="0" class="email-container" role="presentation" style="max-width:600px; width:100%;">
          <tr>
            <td style="padding:28px 16px 8px 16px; text-align:center;">
              <img src="{{$baseUrl}}/assets/localist_logo.png" alt="Localists Logo" style="max-height:50px; margin:0 auto;">
            </td>
          </tr>

          <!-- Main card -->
          <tr>
            <td align="center">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" role="presentation" style="border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden;">
                <tr>
                  <td class="card-td" style="padding:32px; font-family:Helvetica, Arial, sans-serif; color:#4a4a4a;">
                    <h1>Hi {{ $name }},</h1>
                    <p style="text-align:center; margin-top:8px;">Complete your registration – we’ve saved your spot!</p>

                    <p class="highlight">You're just one step away from connecting with more buyers on Localists!</p>

                    <p>We noticed you started signing up but didn't finish. Sellers who complete their profile can:</p>
                    <ul style="margin:0 0 12px 18px; color:#4a4a4a;">
                      <li>✅ Access premium leads instantly</li>
                      <li>✅ Appear in more buyer searches</li>
                      <li>✅ Track engagement and conversions</li>
                      <li>✅ View all leads for free</li>
                    </ul>

                    <p>Finish registering and unlock your full potential on Localists.</p>

                    <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="margin-top:18px;">
                      <tr>
                        <td align="center" style="padding:0 16px;">
                          <a href="{{$baseUrl}}/sellers/create/" class="btn">Join as a Professional</a>
                        </td>
                      </tr>
                    </table>

                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>

          <!-- Why Localists -->
          <tr>
            <td align="center">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" role="presentation" style="border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden;">
                <tr>
                  <td class="card-td" style="padding:32px; font-family:Helvetica, Arial, sans-serif; color:#4a4a4a;">
                    <h2>Why Localists?</h2>
                    <p>Thousands of buyers are looking for trusted sellers like you every day. Don’t miss out on leads just because your profile is incomplete.</p>
                    <p>With your name, phone, and email already saved, you can pick up right where you left off!</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>

          <!-- Need Help -->
          <tr>
            <td align="center">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" role="presentation" style="border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden;">
                <tr>
                  <td class="card-td" style="padding:32px; font-family:Helvetica, Arial, sans-serif; color:#4a4a4a;">
                    <h2>Need Help?</h2>
                    <p>Have questions or need support? We’re here for you.</p>
                    <p>Email us at
                      <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}">{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}</a>.
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr><td style="height:20px; line-height:20px; font-size:0;">&nbsp;</td></tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding:0 16px 40px 16px; font-family:Helvetica, Arial, sans-serif; color:#666; font-size:13px;">
              Manage your email preferences <a href="{{$baseUrl}}/settings/notifications/e-mail-notification">here</a>.<br>
              {{\App\Helpers\CustomHelper::setting_value('website_address','')}}
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>
</body>
</html>
