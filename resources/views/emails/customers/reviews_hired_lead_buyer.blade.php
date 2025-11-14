<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width,initial-scale=1.0" name="viewport">
  <title>Recommended Pros</title>
  <style>
    /* Email-safe resets */
    body{margin:0;padding:0;background:#f1f2f4;-webkit-font-smoothing:antialiased;}
    table{border-collapse:collapse !important;}
    img{border:0;display:block;height:auto;line-height:100%;outline:none;text-decoration:none;max-width:100%;}
    .ExternalClass{width:100%}.ExternalClass *{line-height:100%}
    /* Mobile */
    @media only screen and (max-width:600px){
      .email-container{width:100% !important;padding:0 12px !important;}
      .card-pad{padding:16px !important;}
      .stack{display:block !important;width:100% !important;}
      .right{text-align:left !important;}
    }
  </style>
</head>
<body style="margin:0;padding:0;background:#f1f2f4;">
  <!-- Outer wrapper -->
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f1f2f4">
    <tr>
      <td align="center">

        <!-- Center column -->
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" class="email-container" style="max-width:600px;width:100%;margin:0 auto;font-family:'Lato',Helvetica,Arial,sans-serif;">

          <!-- Logo -->
          <tr>
            <td align="center" style="padding:16px;">
              <img src="{{ $baseUrl }}/assets/localist_logo_1.png" alt="Localists Logo" style="max-height:45px;margin:0 auto;">
            </td>
          </tr>

          <!-- Main card -->
           <tr>
            <td style="padding:25px 30px;color:#333;">
              <p style="margin:0 0 8px;font-size:16px;color:#00AFE3;font-weight:bold;">Hi {{ $customerName }},</p>
              <p style="margin:0 0 12px;font-size:14px;">
                It’s been 24 hours since you posted your <b>{{ $serviceName }}</b> request, and no professionals have placed a bid yet.
              </p>
              <p style="margin:0 0 20px;font-size:14px;">
                Add more details or photos to help local pros understand your job better.
              </p>

              <p style="margin:0;text-align:center;">
                <a href="" 
                   style="background:#00AFE3;color:#fff;text-decoration:none;padding:10px 24px;border-radius:4px;font-size:14px;font-weight:bold;">
                  Add Details
                </a>
              </p>
            </td>
          </tr>

          <!-- Help strip -->
          <tr>
            <td style="padding:10px 16px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#d8edf8" style="border-radius:6px;">
                <tr>
                  <td style="padding:19px 16px;">
                    <p style="margin-top:12px">You can also call our customer support team at
                      <a>
                        {{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}
                      </a>.
                    </p>

                    <p>Regards,<br>Localists Team</p>
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
