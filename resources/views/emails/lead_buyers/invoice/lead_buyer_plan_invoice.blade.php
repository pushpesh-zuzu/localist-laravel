<html>

<head>
  <meta charset="UTF-8">
  <meta content="width=device-width" name="viewport">
  <style>
    body {
      margin: 0;
      background-color: #f1f2f4;
      font-family: "Lato", Helvetica, Arial, sans-serif;
      font-size: 18px;
            font-weight: 510;
            line-height: 25px;
      line-height: 1.6;
      color: #4a4a4a;
      -webkit-font-smoothing: antialiased;
    }

    .email-wrap {
      width: 100%;
      background-color: #f1f2f4;
      padding: 32px 0;
    }

    .email-container {
      max-width: 600px;
      margin: 0 auto;
      padding: 0 16px;
    }

    .logo {
      max-height: 50px;
      display: block;
      margin: 0 auto 20px auto;
    }

    h1 {
      font-size: 22px;
      font-weight: 600;
      color: #333;
      margin: 0 0 10px 0;
    }

    .highlight {
      color: #00afe3;
      margin-bottom: 16px;
      font-size: 15px;
    }

    p {
      color: #61696d;
      margin: 0 0 12px 0;
      font-size: 15px;
      line-height: 1.5;
    }

    a {
      color: #007bff;
      text-decoration: none;
    }

    .btn {
      display: inline-block;
      background-color: #3399ff;
      color: #fff !important;
      text-decoration: none;
      font-size: 14px;
      font-weight: bold;
      padding: 10px 18px;
      border-radius: 4px;
    }

    @media only screen and (max-width: 600px) {
      .email-container {
        width: 100% !important;
        padding: 0 12px !important;
      }

      h1 {
        font-size: 20px !important;
      }

      .btn {
        font-size: 16px !important;
        padding: 12px 20px !important;
      }
    }
  </style>
</head>

<body>
  <table width="100%" cellpadding="0" cellspacing="0" border="0" class="email-wrap" bgcolor="#f1f2f4">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" border="0" class="email-container" style="max-width:600px; width:100%">
          <tr>
            <td style="padding:0 16px 8px 16px; text-align:center">
              <img src="{{$baseUrl}}/assets/localist_logo_1.png" alt="Localists Logo" class="logo">
            </td>
          </tr>

          <tr>
            <td align="center">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.04)">
                <tr>
                  <td style="padding:20px">
                    <h1>Your Invoice from Localists</h1>
                    <div class="highlight">Invoice #{{$invoice_number}} | Dated {{date('d/m/Y',strtotime($created_at))}}</div>
                    <p>Dear <strong>{{ $name }}</strong>,</p>
                    <p>Thank you for your payment. Below are the details of your invoice for your records.</p>

                    <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">

                    <!-- Invoice Summary -->
                    <table style="width:100%; border-collapse: collapse; font-size:15px;">
                      <tr>
                        <td style="color:#6de1a7; font-size:18px;">Invoice Date:</td>
                        <td style="text-align:right;">{{date('d/m/Y',strtotime($created_at))}}</td>
                      </tr>
                      <tr>
                        <td style="color:#6de1a7; font-size:18px;">Invoice Number:</td>
                        <td style="text-align:right;">{{$invoice_number}}</td>
                      </tr>
                      <tr>
                        <td style="color:#6de1a7; font-size:18px;">Status:</td>
                        <td style="text-align:right;"><strong style="color:#6de1a7;">PAID</strong></td>
                      </tr>
                    </table>

                    <!-- Invoice Details -->
                    <table style="width:100%; margin-top:30px; border-collapse: collapse; font-size:16px;">
                      <thead>
                        <tr style="border-bottom:2px solid #ddd;">
                          <th style="padding:5px 0;">DETAILS</th>
                          <th style="padding:10px 0;">PERIOD</th>
                          <th style="padding:10px 0; text-align:right;">PRICE</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr style="text-align:center;border-bottom:1px solid #eee;">
                          <td style="padding:10px 0;">{{$details}}</td>
                          <td style="padding:10px 0;">{{ $period }}</td>
                          <td style="padding:10px 0; text-align:right;">&pound;{{$amount}}</td>
                        </tr>
                      </tbody>
                    </table>

                    <!-- Summary -->
                    <div style="margin-top:30px; font-size:16px;">
                      <div style="display:flex; justify-content:space-between;">
                        <span>Subtotal</span>
                        <span>&pound;{{$amount}}</span>
                      </div>
                      <div style="display:flex; justify-content:space-between; margin-top:5px;">
                        <span>VAT (20%)</span>
                        <span>&pound;{{$vat}}</span>
                      </div>
                      <div style="display:flex; justify-content:space-between; margin-top:5px; font-weight:bold;">
                        <span>Total</span>
                        <span>&pound;{{$total_amount}}</span>
                      </div>
                    </div>

                    <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">

                    <p style="margin-top:12px">You can also call our customer support team at
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

          <tr>
            <td style="height:18px; font-size:0; line-height:18px">&nbsp;</td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding:0 16px 40px 16px; font-family:Helvetica, Arial, sans-serif; color:rgb(102, 102, 102); font-size:13px">
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