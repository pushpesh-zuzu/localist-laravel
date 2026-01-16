<!doctype html>
<html>

<head>
  <meta charset="UTF-8">
  <title>You've Purchased a Lead</title>
</head>

<body style="margin:0;padding:0;background:#f4f8fb;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td align="center" style="padding:30px 10px;">
        <table width="600" cellpadding="0" cellspacing="0"
          style="background:#ffffff;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.08);">
          <tr>
            <td align="center" style="padding:20px;">
              <table width="100%">
                <tr>
                  <td bgcolor="#00AFE3" height="40" align="center" style="border-radius:5px;">
                    <img src="{{$baseUrl}}/assets/localist_logo_1.png" height="26" alt="Localists">
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:10px 30px 2px;">
              <p style="margin:0;font-size:13px;color:#253238;">
                Dear <strong>{{ ucfirst($name) }}</strong>,
              </p>
            </td>
          </tr>

          <!-- THANK YOU TEXT -->
          <tr>
            <td align="center" style="padding:0 30px 14px;">
              <p style="margin:0;font-size:18px;font-weight:800;color:#00AFE3;letter-spacing:0.3px;line-height: 35px;">
                Thank you for your payment.
              </p>
            </td>
          </tr>


          <!-- INVOICE DETAILS SECTION -->
          <tr>
            <td align="center" style="padding:10px 10px;">
              <table width="100%" cellpadding="0" cellspacing="0"
                style="max-width:520px;background:#EAF8FC;border-radius:12px;">

                <!-- Header -->
                <tr>
                  <td align="center" style="background:#00AFE3;padding:12px 20px;border-radius:12px 12px 0 0;      font-size:14px;font-weight:800;color:#ffffff;line-height:20px;">
                    Below are the details of your invoice for your records.
                  </td>
                </tr>

                <!-- Content -->
                <tr>
                  <td style="padding:18px 22px;">
                    <table width="100%" cellpadding="0" cellspacing="0">

                      <!-- Row -->
                      <tr>
                        <td style="font-size:14px;color:#00AFE3;font-weight:700;line-height:22px;">
                          {{$details}}:
                        </td>
                        <td align="right" style="font-size:14px;color:#253238;font-weight:700;line-height:22px;">
                          &pound;{{$amount}}
                        </td>
                      </tr>

                      <tr>
                        <td height="6"></td>
                      </tr>

                      <tr>
                        <td style="font-size:14px;color:#00AFE3;line-height:22px;font-weight:500;">
                          Invoice Date:
                        </td>
                        <td align="right" style="font-size:14px;color:#253238;line-height:22px;font-weight:500;">
                          {{date('d/m/Y',strtotime($created_at))}}
                        </td>
                      </tr>


                      <tr>
                        <td height="6"></td>
                      </tr>

                      <tr>
                        <td style="font-size:14px;color:#00AFE3;line-height:22px;font-weight:500;">
                          Invoice Number:
                        </td>
                        <td align="right" style="font-size:14px;color:#253238;line-height:22px;font-weight:500;">
                          {{$invoice_number}}
                        </td>
                      </tr>

                      <tr>
                        <td height="6"></td>
                      </tr>

                      <tr>
                        <td style="font-size:14px;color:#00AFE3;line-height:22px;font-weight:500;">
                          Status:
                        </td>
                        <td align="right" style="font-family:Inter, Arial, sans-serif; font-size:14px;     font-weight:800;  /* Extra Bold */   line-height:15px;  color:#00AFE3;">
                          PAID
                        </td>
                      </tr>

                      <!-- Divider -->
                      <tr>
                        <td colspan="2" style="padding:14px 0;">
                          <div style="height:1px;background:#CDEEF7;"></div>
                        </td>
                      </tr>

                      <!-- Totals -->
                      <!-- Subtotal / VAT / Total Section -->
                      <tr>
                        <td
                          style="font-family:Inter, Arial, sans-serif;font-size:14px;color:#253238;line-height:20px;font-weight:500;">
                          Subtotal:
                        </td>
                        <td align="right"
                          style="font-family:Inter, Arial, sans-serif;font-size:14px;color:#253238;line-height:20px;font-weight:500;">
                          &pound;{{$amount}}
                        </td>
                      </tr>

                      <tr>
                        <td height="6"></td>
                      </tr>

                      <tr>
                        <td
                          style="font-family:Inter, Arial, sans-serif;font-size:14px;color:#253238;line-height:20px;font-weight:500;">
                          VAT (20%):
                        </td>
                        <td align="right"
                          style="font-family:Inter, Arial, sans-serif;font-size:14px;color:#253238;line-height:20px;font-weight:500;">
                          &pound;{{$vat}}
                        </td>
                      </tr>

                      <tr>
                        <td height="8"></td>
                      </tr>

                      <tr>
                        <td
                          style="font-family:Inter, Arial, sans-serif;font-size:14px;color:#253238;line-height:22px;font-weight:800;">
                          Total:
                        </td>
                        <td align="right"
                          style="font-family:Inter, Arial, sans-serif;font-size:14px;color:#253238;line-height:22px;font-weight:800;">
                          &pound;{{$total_amount}}
                        </td>
                      </tr>


                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>



          <!-- HELP SECTION -->
          <tr>
            <td align="center" style="padding:20px 30px; background:rgba(227, 246, 252, 0.5); border-radius:8px;">
              <div style="font-size:16px; font-weight:800; color:#253238; margin-bottom:8px; text-align:center;">
                Need Help?
              </div>
              <div style="font-size:12px; font-weight:600; color:#253238; line-height:18px; text-align:center;">
                Email us at:
                <a href="mailto:contact@localists.com" style="color:#00AFE3; text-decoration:none;">
                  contact@localists.com
                </a>
              </div>
            </td>
          </tr>

          <!-- FOOTER -->
          <tr>
            <td align="center" bgcolor="#131838" style="padding:9px 18px;">
              <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center">
                <tr>
                  <td valign="middle" style="padding-right:8px;">
                    <img src="{{$siteUrl}}/public/images/globleimg.png" width="19" height="19" alt=""
                      style="display:block;">
                  </td>
                  <td valign="middle"
                    style="font-size:13px; line-height:18px; color:#ffffff; font-family:Inter, Arial, sans-serif; padding-right:10px;">
                    Localists.com
                  </td>
                  <td valign="middle" style="padding:0 10px; font-size:13px; line-height:18px; color:#ffffff;">|</td>
                  <td valign="middle" style="padding-right:8px;">
                    <img src="{{$siteUrl}}/public/images/vectorimg.png" width="18" height="14" alt=""
                      style="display:block;">
                  </td>
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
            <td align="center" style="padding:12px 16px; background:#f4f6f8;">
              <p style="margin:0; font-size:11px; line-height:16px; color:#6b7280; text-align:center;">
                Click here to
                <a href="{{ url('/api/unsubscribe-status-update/' . $userId . '/user') }}"
                  style="color:#00AFE3; text-decoration:underline;font-weight:600;">
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