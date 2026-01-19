<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Login Email</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background: #f4f6f8;
      -webkit-text-size-adjust: none;
      -ms-text-size-adjust: none;
      font-family: Inter, Arial, sans-serif;
    }

    img {
      border: 0;
      -ms-interpolation-mode: bicubic;
      display: block;
    }

    a {
      color: inherit;
      text-decoration: none;
    }

    @media screen and (max-width: 620px) {
      .container {
        width: 100% !important;
      }

      .inner {
        padding: 18px !important;
      }

      .cta-box {
        padding: 18px !important;
      }
    }
  </style>
</head>

<body>
  <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f4f6f8">
    <tr>
      <td align="center">

        <!-- Main card -->
        <table class="container" width="600" cellpadding="0" cellspacing="0" border="0"
          style="width:600px; max-width:600px; background:#ffffff; border-radius:8px; overflow:hidden;">

          <!-- Header -->
          <tr>
            <td align="center" style="padding:16px 0 0;">
              <table role="presentation" width="500" cellspacing="0" cellpadding="0" border="0">
                <tr>
                  <td height="40" valign="middle" align="center"
                    style="height:40px; background-color:#17B5E5; border-radius:5px; padding:0;">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" height="40">
                      <tr>
                        <td height="40" valign="middle" align="center">
                          <img src="{{$baseUrl}}/assets/localist_logo_1.png" alt="Localists" height="25"
                            style="display:block; height:25px; max-height:25px;" />
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Spacer above envelope -->
          <tr>
            <td height="22" style="font-size:0; line-height:0;">&nbsp;</td>
          </tr>

          <!-- Envelope image section -->
          <tr>
            <td align="center">
              <img src="{{$siteUrl}}/public/images/envelope.png" alt="Email Illustration" width="200" height="173"
                style="display:block; margin:0 auto;">
            </td>
          </tr>


          <!-- Greeting -->
          <tr>
            <td align="center" style="padding:1px 28px 8px;">
              <p style=" font-size:16px; line-height:22px; color:#131838;">
                Hi <strong style="color:#131838;">{{ ucfirst($name) }}</strong>,
              </p>
            </td>
          </tr>

          <!-- CTA panel -->
          <tr>
            <td align="center" style="padding:12px 20px 22px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                style="background:#e8f8fc; border-radius:8px;">
                <tr>
                  <td align="center" style="padding:32px 28px 24px;">

                    <!-- Heading -->
                    <p style=" margin:0 0 22px;  font-size:20px;   line-height:26px;  font-weight:700; color:#253238;font-family:Inter, Arial, sans-serif;">
                      Click below to log in to your account directly
                    </p>

                    <!-- Button -->
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                      <tr>
                        <td align="center" bgcolor="#ff9a2b" style=" border-radius:30px; padding:12px 32px; ">
                          <a href="{{ $baseUrl }}/en/gb/login?client_id={{base64_encode($token)}}" style="display:block;font-size:16px;line-height:20px;font-weight:700;color:#ffffff; text-decoration:none; font-family:Inter, Arial, sans-serif;">
                            Log In Now
                          </a>
                        </td>
                      </tr>
                    </table>

                  </td>
                </tr>
              </table>
            </td>
          </tr>


          <!-- Need Help -->
          <tr>
            <td align="center" style="padding:6px 28px 6px;">
              <p style="margin:0; font-size:16px; line-height:20px; font-weight:700; color:#17B5E5;">
                Need Help?
              </p>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:6px 28px 18px;">
              <p style="margin:0; font-size:12px; line-height:18px; font-weight:600; color:#131838;">
                If you have any questions, please reach out to us at
                <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}"
                  style="color:#17B5E5; text-decoration:none;">{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}</a>
              </p>
            </td>
          </tr>
          <tr>
            <td align="center" bgcolor="#131838" style="padding:12px 18px;">
              <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center">
                <tr>
                  <!-- Globe -->
                  <td valign="middle" style="padding-right:6px;">
                    <img src="{{$siteUrl}}/public/images/globleimg.png" width="19" height="19" alt="" style="display:block;">
                  </td>

                  <!-- Website text -->
                  <td valign="middle"
                    style="font-size:13px; line-height:18px; color:#ffffff; font-family:Inter, Arial, sans-serif;">
                    Localists.com
                  </td>

                  <!-- Divider -->
                  <td valign="middle" style="padding:0 10px; font-size:13px; line-height:18px; color:#ffffff;">
                    |
                  </td>

                  <!-- Email icon -->
                  <td valign="middle" style="padding-right:6px;">
                    <img src="{{$siteUrl}}/public/images/vectorimg.png" width="18" height="14" alt="" style="display:block;">
                  </td>

                  <!-- Email text -->
                  <td valign="middle" style="font-size:13px; line-height:18px; font-family:Inter, Arial, sans-serif;">
                    <a href="mailto:contact@localists.com" style="color:#ffffff; text-decoration:none;">
                      contact@localists.com
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:10px 22px 18px;">
              <p style="margin:0; font-size:11px; line-height:16px; color:#111637;">
                Manage your email preferences
                <a href="{{$baseUrl}}/settings/notifications/e-mail-notification" style="color:#111637; text-decoration:underline;">here</a>
              </p>
            </td>
          </tr>

        </table>
        <!-- End main card -->

      </td>
    </tr>
  </table>

</body>

</html>