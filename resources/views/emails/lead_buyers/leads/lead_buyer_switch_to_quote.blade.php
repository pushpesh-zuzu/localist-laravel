<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Switched Your Account</title>
  <style>
    body { margin:0; padding:0; background:#f1f2f4; -webkit-font-smoothing:antialiased; }
    table { border-collapse:collapse; }
    img { border:0; }
    a { color:#007bff; text-decoration:none; }

    .email-wrap { width:100%; background:#f1f2f4; padding:24px 0; }
    .email-container { width:100%; max-width:600px; margin:0 auto; }
    .card { background:#ffffff; border-radius:6px; box-shadow:0 1px 3px rgba(0,0,0,.06); overflow:hidden; }
    .logo { max-height:48px; display:block; margin:0 auto; }
    h1 { margin:0; font-size:20px; line-height:26px; color:#333; font-weight:600; text-align:center; }
    .lead-sub { color:#61696d; font-size:14px; margin-top:6px; }

    @media only screen and (max-width:600px) {
      .email-container { padding:0 12px !important; }
      h1 { font-size:18px; }
    }
  </style>
</head>
<body>
  <table role="presentation" width="100%" class="email-wrap">
    <tr>
      <td align="center">
        <table role="presentation" class="email-container" width="600" cellpadding="0" cellspacing="0">

          <!-- Logo -->
          <tr>
            <td align="center" style="padding:0 16px 20px 16px;">
              <img src="{{ $baseUrl }}/assets/localist_logo_1.png" alt="Localists" class="logo" width="180" style="max-width:100%; height:auto;">
            </td>
          </tr>

          <!-- MAIN WHITE CARD -->
          <tr>
            <td style="padding:0 16px;">
              <table role="presentation" width="100%" class="card">

                <!-- Greeting -->
                <tr>
                  <td style="padding:20px;">
                    <h1>Hi {{ $name }}</h1>
                    <div class="lead-sub">
                      This is to confirm that you've successfully switched from your Lead Buyer account to your Quote Customer account.
                    </div>
                  </td>
                </tr>

                <!-- Blue header -->
                <tr>
                  <td style="background:#00afe3; padding:12px 16px; color:#fff; font-weight:700; font-size:15px;">
                    <table role="presentation" width="100%">
                      <tr>
                        <td>You now have access to:</td>
                        <td style="width:36px; text-align:right;">
                          <img src="https://storage.googleapis.com/tagjs-prod.appspot.com/v1/c5dSX3CKrL/y90fsxpb_expires_30_days.png" width="24" alt="">
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- Features -->
                <tr>
                  <td style="padding:16px;">
                    <table role="presentation" width="100%">
                      <tr>
                        <td style="padding-bottom:12px;">
                          <table role="presentation">
                            <tr>
                              <td style="padding-right:10px;"><img src="https://storage.googleapis.com/tagjs-prod.appspot.com/v1/c5dSX3CKrL/z0j10uuw_expires_30_days.png" width="28" alt=""></td>
                              <td style="font-size:14px; font-weight:600; color:#000;">Request and view quotes</td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding-bottom:12px;">
                          <table role="presentation">
                            <tr>
                              <td style="padding-right:10px;"><img src="https://storage.googleapis.com/tagjs-prod.appspot.com/v1/c5dSX3CKrL/x6tfi8sq_expires_30_days.png" width="28" alt=""></td>
                              <td style="font-size:14px; font-weight:600; color:#000;">Communicate with professionals about pricing</td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <table role="presentation">
                            <tr>
                              <td style="padding-right:10px;"><img src="https://storage.googleapis.com/tagjs-prod.appspot.com/v1/c5dSX3CKrL/3kf8j838_expires_30_days.png" width="28" alt=""></td>
                              <td style="font-size:14px; font-weight:600; color:#000;">Track quote statuses</td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>

                    <!-- Return line INSIDE card -->
                    <div style="margin-top:18px; font-size:14px; color:#4a4a4a;">
                      Want to return to your Quote Customer account? Just click on your username and select Switch User
                    </div>
                  </td>
                </tr>

              </table>
            </td>
          </tr>

          <!-- Blue help band -->
          <tr>
            <td style="padding:0 16px 16px 16px;">
              <table role="presentation" width="100%" style="border-radius:6px; overflow:hidden;">
                <tr>
                  <td style="background:#00afe3; color:#fff; padding:14px; text-align:center; font-size:14px;">
                    If you didn’t make this change or need help, please email us at
                    <a href="mailto:contact@localists.com" style="color:#fff; text-decoration:underline;">contact@localists.com</a>.
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:0 16px 24px 16px;">
              <table role="presentation" width="100%" class="card" style="background:#111637; color:#fff;">
                <tr>
                  <td style="padding:12px 14px; font-size:13px;text-align:center">
                    Manage your email preferences <a href="{{ $baseUrl }}/settings/notifications/e-mail-notification" style="color:#fff; text-decoration:underline;">here</a>.
                  </td>

                </tr>
                <tr>
                  <td colspan="2" style="padding:0 14px 14px 14px; font-size:12px; color:#d0d4e0;text-align:center">
                    {{ \App\Helpers\CustomHelper::setting_value('website_address','') }}
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
