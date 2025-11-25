<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width,initial-scale=1.0" name="viewport">
  <title>A Verified Professional Is Now Available</title>
  <style>
    /* Email-safe resets */
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
            <td style="padding:25px 30px;">
              <p style="font-size: 22px; font-weight: 600; color: #333333;">Hi {{ ucfirst($customerName) }},</p>
              <p style="margin:0 0 12px;">    
                 Good news! We’ve just added a verified professional in your area who can help with your <b>{{ ucfirst($serviceName ?? 'your service') }}</b> request.
              </p>
              @if(!empty($postCode))
              <p style="margin:0 0 20px;">
              Your quote request now matches a professional covering <b>{{$postCode }}</b>, and they’re able to review and respond to it.
              </p>
              @endif
              <p style="margin:0 0 20px;">
                To keep things moving, please log in and check the professional available for your quote.
              </p>

              <p style="margin:0;text-align:center;">
                <a href="{{ $baseUrl }}/en/gb/login" 
                   style="background:#00AFE3;color:#fff;text-decoration:none;padding:10px 24px;border-radius:4px;font-size:14px;font-weight:bold;">
                  View Professional
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
                    <p style="margin-top:12px">If you need any assistance, our team is always here to help.
                      <a>
                        {{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}
                      </a>.
                    </p>

                    <p>Kind Regards,<br>Localists Team</p>
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
