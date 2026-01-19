<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Localists – Leave Your Feedback</title>
</head>

<body style="margin:0;padding:0;background:#f4f8fb;font-family:Inter,Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" bgcolor="#f4f8fb">
    <tr>
      <td align="center" style="padding:30px 10px;">
        <table width="600" cellpadding="0" cellspacing="0"
          style="background:#ffffff;overflow:hidden;box-shadow:0 6px 18px rgba(0,0,0,0.08);">

          <!-- HEADER -->
          <tr>
            <td bgcolor="#00AFE3" align="center" style="padding:16px;">
              <img src="{{ $baseUrl }}/assets/localist_logo_1.png" height="30" alt="Localists"
                style="display:block;">
            </td>
          </tr>

          <!-- CONTENT -->
          <tr>
            <td style="padding:30px 40px 30px;">

              <p style="margin:0 0 14px;font-size:16px;color:#253238;">
                Hi <strong>{{ ucfirst($customerName) }}</strong>,
              </p>

              <p style="margin:0 0 14px;font-size:14px;color:#253238;line-height:1.6;">
                We hope your <strong>{{ ucfirst($serviceName) }}</strong> went smoothly.
              </p>

              <p style="margin:0 0 24px;font-size:14px;color:#253238;line-height:1.6;">
                We’d love to hear about your experience with
                <strong>{{ ucfirst($sellerName) }}</strong>, the professional you hired on Localists.
              </p>

              <!-- FEEDBACK BOX -->
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#E3F6FC;border-radius:14px;">
                <tr>
                  <td align="center" style="padding:26px;">

                    <p style="margin:0 0 18px;font-size:18px;font-weight:700;color:#253238;text-align:center;">
                      Your quick feedback helps us improve our service.
                    </p>

                    <!-- BUTTON -->
                    <table cellpadding="0" cellspacing="0" align="center">
                      <tr>
                        <td bgcolor="#FF9933" style="border-radius:30px;padding:10px 26px;">
                          <a href="{{ $reviewUrl }}"
                            style="color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;display:inline-block;font-family:Inter, Arial, Helvetica, sans-serif;">
                            Leave Your Feedback
                          </a>
                        </td>
                      </tr>
                    </table>

                  </td>
                </tr>
              </table>

            </td>
          </tr>

          <!-- HELP -->
          <tr>
            <td align="center" bgcolor="#E9F6FB" style="padding:18px 16px;">

              <p
                style=" margin:0 0 4px; font-family:Inter, Arial, Helvetica, sans-serif;  font-size:15px;   font-weight:500;  color:#253238; line-height:1.4;  text-align:center;">
                You can also call our customer support team at
              </p>

              <p
                style="margin:0;font-family:Inter, Arial, Helvetica, sans-serif;font-size:15px;font-weight:600;line-height:1.4;text-align:center;">
                <a href="mailto:contact@localists.com" style="color:#00AFE3;text-decoration:underline;font-weight:600;">
                  contact@localists.com
                </a>
              </p>

            </td>
          </tr>



          <!-- FOOTER -->
          <tr>
            <td align="center" bgcolor="#131838" style="padding:12px 18px;">
              <table cellspacing="0" cellpadding="0" border="0" align="center">
                <tr>
                  <td style="padding-right:8px;">
                    <img src="{{$siteUrl}}/public/images/globleimg.png" width="18" height="18"
                      style="display:block;">
                  </td>
                  <td style="font-size:13px;color:#ffffff;padding-right:10px;">
                    Localists.com
                  </td>
                  <td style="padding:0 10px;color:#ffffff;">|</td>
                  <td style="padding-right:8px;">
                    <img src="{{$siteUrl}}/public/images/vectorimg.png" width="18" height="14"
                      style="display:block;">
                  </td>
                  <td style="font-size:13px;">
                    <a href="mailto:contact@localists.com" style="color:#ffffff;text-decoration:none;">
                      contact@localists.com
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
      </td>
    </tr>
  </table>
</body>

</html>