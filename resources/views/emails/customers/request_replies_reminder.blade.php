<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Welcome to Localists</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #f9fcfe;
      font-family: Arial, Helvetica, sans-serif;
    }

    table {
      border-collapse: collapse;
    }

    img {
      display: block;
      border: 0;
      outline: none;
    }

    @media only screen and (max-width: 480px) {
      .container {
        width: 100% !important;
      }

      .padding {
        padding: 15px !important;
      }

      .btn {
        font-size: 14px !important;
        padding: 10px 18px !important;
      }
    }
  </style>
</head>

<body>

  <table width="100%" cellpadding="0" cellspacing="0" bgcolor="#f9fcfe">
    <tr>
      <td align="center" style="padding:20px 10px;">
        <table width="600" class="container" cellpadding="0" cellspacing="0" bgcolor="#ffffff"
          style="border-radius:8px; overflow:hidden;">
          <tr>
            <td align="center" style="padding:20px;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td bgcolor="#00AFE3" height="40" align="center" style="border-radius:5px;">
                    <img src="{{$baseUrl}}/assets/localist_logo_1.png" height="26" alt="Localists">
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td class="padding" align="left" style="padding:20px 20px 20px 40px; font-family:Inter, Arial, sans-serif;">
              <div style="font-size:16px; color:#252832; font-weight:400;">
               Hi
                <span style="font-weight:700;">
                  {{ ucfirst($name) }},
                </span>
              </div>
              <div style="font-size:13px;
                color:#252832;
                padding-top:12px;">
                Want to speed things up? Requesting replies lets the top 5 available professionals know you’re ready to hear from them.
              </div>
            </td>
          </tr>
          <!-- LOGIN BOX -->
         
          <!-- YOUR REQUESTS -->
          <tr>
            <td align="center" style="padding:10px 20px;">
              <div style="font-size:16px; font-weight:800; color:#00afe3;">
                Your Requests
              </div>
            </td>
          </tr>

          <tr>
            <td align="center" style="padding:0 60px 12px;">
              <table width="100%" cellpadding="0" cellspacing="0" bgcolor="#00afe3" style="border-radius:6px;">
                <tr>
                  <td align="center"
                    style="padding:20px 25px; font-family:Inter,Arial,sans-serif; font-size:12px; font-weight:700; color:#ffffff; line-height:18px;">
                    Manage your requests from one place. You can request replies from the top 5 lead buyers in one
                    click:
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <!-- BUTTON -->
          <tr>
            <td align="center" style="padding-bottom:25px; font-size:0; line-height:0;">
              <a href="{{ url('/api/email-customer-request-top-five-matches/' . $leadId . '/' . $buyerId) }}"
                style="background:#ff8c2b;color:#ffffff;font-size:15px;font-weight:700;padding:8px 22px; border-radius:30px; text-decoration:none;display:inline-block;line-height:18px;">
                Request Quote From Top 5 Professionals
                <img src="{{$siteUrl}}/public/images/rocket.png" width="19" height="19" alt=""
                  style="display:inline-block; margin-top: 4px; vertical-align:middle; border:0;">
              </a>
            </td>
          </tr>


          <!-- MANAGE REQUESTS -->
          <tr>
            <td align="center" style="padding:0 20px 20px;">
              <div style="font-size:14px; font-weight:700; color:#00afe3;">
                 Manage everything from your Request Panel.
              </div>
              <div style="font-size:12px; color:#253238; margin-top:4px;">
                Create & manage your requests from <a href="{{$baseUrl}}/buyers/create" style="color:#00afe3;">My Request Panel</a>
              </div>
            </td>
          </tr>

          <!-- SUPPORT STRIP -->
          <tr>
            <td align="center" bgcolor="#e9f7fd"
              style="padding:16px; font-family:Inter, Arial, sans-serif;  font-size:14px;  font-weight:700;  color:#253238;line-height:18px;">

              If you have any questions, please reach out to us at
              <a href="mailto: {{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}"
                style="color:#00afe3; font-size:14px;font-weight:700; text-decoration:none;">
                {{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}
              </a>

            </td>
          </tr>


          <!-- FOOTER -->
          <tr>
            <td align="center" bgcolor="#131838" style="padding:8px 18px;">
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
                    <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}" style="color:#ffffff; text-decoration:none;">
                      {{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:12px 16px; background:#f4f6f8;">
              <p style="margin:0; font-size:11px; line-height:16px; color:#6b7280; text-align:center;">
               Click here to 
                <a href="{{ url('/api/unsubscribe-status-update/' . $userId . '/user') }}"
                  style="color:#00AFE3; text-decoration:underline; font-weight:600;">
                  unsubscribe
                </a> and we will remove you from our emailing list.
              </p>
            </td>
          </tr>
        </table>
        <!-- END CONTAINER -->

      </td>
    </tr>
  </table>

</body>

</html>