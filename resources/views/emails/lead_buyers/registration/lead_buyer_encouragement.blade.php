<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Boost Your Sales with Auto-Buy</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <style>
    /* Base */
    body {
      margin: 0;
      background-color: #f1f2f4;
      font-family: 'Lato', Helvetica, Arial, sans-serif;
      font-size: 18px;
      line-height: 25px;
      color: #4a4a4a;
      -webkit-font-smoothing: antialiased;
    }
    img { border:0; display:block; }
    a { color: #007bff; }

    /* Button (inline on desktop; larger tap target on mobile) */
    .btn {
      display: inline-block;
      background-color: #00afe3;
      color: #ffffff !important;
      text-decoration: none;
      font-size: 14px;
      font-weight: 700;
      padding: 10px 18px;
      border-radius: 4px;
      -webkit-text-size-adjust: none;
    }

    h1 { font-size:22px; font-weight:600; color:#333; margin:0 0 10px 0; text-align:center; font-family:Helvetica, Arial, sans-serif; }
    p { color:#4a4a4a; margin:0 0 12px 0; font-size:15px; line-height:1.5; font-family:Helvetica, Arial, sans-serif; }
    .highlight { color:#00afe3; margin-bottom:16px;}

    @media only screen and (max-width:600px) {
      .email-container { width:100% !important; padding:0 12px !important; }
      .card-td { padding:20px !important; } /* smaller padding on mobile */
      .btn { font-size:16px !important; padding:12px 20px !important; } /* larger tap target but still inline */
      h1 { font-size:20px !important; }
    }
  </style>
</head>
<body>
  <!-- Outer wrapper (bgcolor for Yahoo) -->
  <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f1f2f4" role="presentation">
    <tr>
      <td align="center">

        <!-- Center column -->
        <table width="600" cellpadding="0" cellspacing="0" border="0" class="email-container" role="presentation" style="max-width:600px; width:100%;">
          <tr>
            <td style="padding:28px 16px 8px 16px; text-align:center;">
              <img src="{{$baseUrl}}/assets/localist_logo.png" alt="Localists Logo" style="max-height:50px; margin:0 auto;">
            </td>
          </tr>

          <!-- Promo card -->
          <tr>
            <td align="center">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" role="presentation" style="border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden;">
                <tr>
                  <td class="card-td" style="padding:32px; font-family:Helvetica, Arial, sans-serif; color:#4a4a4a;">
                    <h1>Hi {{ $name }}</h1>
                    <p style="text-align:center; margin-top:8px;">Ready to unlock more sales?</p>

                    <div class="highlight">Auto Bid is helping Localists sellers increase sales by up to <strong>35%</strong>!</div>

                    <p>Right now, Auto Bid is turned <strong>off</strong> on your account — which means you might be missing out on top leads.</p>

                    <p>Sellers using Auto Bid get:</p>
                    <ul style="margin:0 0 12px 18px; color:#4a4a4a;">
                      <li>✅ Up to 35% more sales</li>
                      <li>✅ Faster conversions with automatic matching</li>
                      <li>✅ Hands-free lead purchases 24/7</li>
                    </ul>

                    <p>Don't miss out on high-intent customers. Enable Auto Bid and let Localists do the work for you!</p>

                    <!-- Button with padded TD so it doesn't hug edges on mobile -->
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="margin-top:18px;">
                      <tr>
                        <td align="center" style="padding:0 16px;">
                          <a href="{{$baseUrl}}/settings/billing/my-credits" class="btn" style="display:inline-block;">Enable Auto Bid Now</a>
                        </td>
                      </tr>
                    </table>

                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>

          <!-- How Auto-Bid Works card -->
          <tr>
            <td align="center">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" role="presentation" style="border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden;">
                <tr>
                  <td bgcolor="#d8edf8" style="padding:12px 20px; color:#1a588c; font-weight:500; font-family:Helvetica, Arial, sans-serif;">
                    How Auto Bid Works
                  </td>
                </tr>
                <tr>
                  <td class="card-td" style="padding:32px; font-family:Helvetica, Arial, sans-serif; color:#4a4a4a;">
                    <p style="margin-top:0;">Once enabled, Auto Bid uses your service filters to automatically secure matching leads the moment they come in. No need to log in, no missed opportunities.</p>
                    <p>Just add credits and let the system keep your business ahead of the competition — even while you're offline.</p>

                    <!-- Secondary CTA -->
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="margin-top:16px;">
                      <tr>
                        <td align="center" style="padding:0 16px;">
                          <a href="{{$baseUrl}}/settings/billing/my-credits" class="btn" style="background-color:#28c199; display:inline-block;">Contact Lead Now</a>
                        </td>
                      </tr>
                    </table>

                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>

          <!-- Need Help card -->
          <tr>
            <td align="center">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" role="presentation" style="border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden;">
                <tr>
                  <td bgcolor="#d8edf8" style="padding:12px 20px; color:#1a588c; font-weight:500; font-family:Helvetica, Arial, sans-serif;">
                    Need Help?
                  </td>
                </tr>
                <tr>
                  <td class="card-td" style="padding:32px; font-family:Helvetica, Arial, sans-serif; color:#4a4a4a;">
                    <p style="margin-top:0;">Our team is here to help you make the most of Localists. Email us at
                      <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}">{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}</a>.
                    </p>
                    <p>Let’s grow your business together 🚀</p>
                    <p>— The Localists Team</p>
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
        <!-- end center column -->

      </td>
    </tr>
  </table>
</body>
</html>
