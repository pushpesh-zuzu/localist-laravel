<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Localists – Low Credits Alert</title>
</head>

<body style="margin:0;padding:0;background:#f4f8fb;font-family:Inter,Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" bgcolor="#f4f8fb">
    <tr>
      <td align="center" style="padding:30px 10px;">
        <table width="600" cellpadding="0" cellspacing="0"
          style="background:#ffffff;overflow:hidden;box-shadow:0 6px 18px rgba(0,0,0,0.08);">
          <tr>
            <td bgcolor="#00AFE3" align="center" style="padding:13px;">
              <img src="{{ $baseUrl }}/assets/localist_logo_1.png" height="28" alt="Localists"
                style="display:block;">
            </td>
          </tr>


          <tr>
            <td style="padding:28px 40px 20px;">

              <p style="margin:0 0 12px;font-size:16px;color:#253238;">
                Hi <strong>{{ ucfirst($name) }}</strong>,
              </p>

              <p style="margin:0 0 14px;font-size:12px; font-weight:500;">
                New jobs are waiting for you!
              </p>

              @if($credit_purchase)
              <p
                style="margin:0 0 14px;  font-family: 'Inter', Arial, Helvetica, sans-serif;  font-size:12px;  font-weight:600;  line-height:1.5;  letter-spacing:0;  color:#253238;">
                You haven't purchased any credit pack for 5 days and your balance is low.
                There are <strong>{{ $total_count }}</strong> jobs matching your preferences —
                but you can't bid on them.
              </p>

              @else
              <p
                style="margin:0 0 14px; font-family: 'Inter', Arial, Helvetica, sans-serif; font-size:12px; font-weight:600;line-height:1.5;letter-spacing:0;color:#253238;">

                You've missed out {{ $total_count }} potential jobs for your services
                with an average value of £ {{ $total_credt_sum }} in the last 7 days.
              </p>
              @endif



              <table width="100%" cellpadding="0" cellspacing="0"
                style="background:#E9F6FB;border-radius:12px;padding:18px;">
                <tr>
                  <td>

                    <!-- JOB ITEM 1 -->
                    @foreach ($leadDataList as $lead)
                    <table width="100%" cellpadding="0" cellspacing="0"
                      style="background:#ffffff;border-radius:10px;padding:14px;margin-bottom:12px;">
                      <tr>
                        <td width="36" valign="top" style="padding-top:2px;">
                          <img src="{{$siteUrl}}/public/images/icons/caticon.png" width="24" height="24" style="display:block;">
                        </td>
                        <td style="font-size:13px;color:#253238;font-weight:600;">
                          Total Jobs: {{ $lead['count'] }}
                        </td>
                      </tr>
                      <tr>
                        <td colspan="2" height="8"></td>
                      </tr>
                      <tr>
                        <td width="36" valign="top">
                          <img src="{{$siteUrl}}/public/images/icons/jobicon.png" width="24" height="24" style="display:block;">
                        </td>
                        <td style="font-size:12px;color:#253238;font-weight:600;">
                          {{ $lead['category_name'] }}: £{{ number_format($lead['credit_sum'] ?? 0, 0) }}
                        </td>
                      </tr>
                    </table>
                    @endforeach
                    <!-- JOB ITEM 2 -->


                    <!-- BUTTON BELOW CARDS -->
                    <div style="text-align:center;margin-top:12px;">
                      @if ($credit_purchase)
                      <a href="{{ $baseUrl }}/settings/billing/my-credits"
                        style="background:#FF9933;  color:#FFFFFF;  text-decoration:none;  padding:8px 22px;  border-radius:30px;  font-size:15px;  font-weight:600;  border:1px solid #d1e8f5;display:inline-block;">
                        Contact Lead Now
                      </a>
                      @else
                      <a href="{{ $baseUrl }}/sellers/leads"
                        style="background:#FF9933;  color:#FFFFFF;  text-decoration:none;  padding:8px 22px;  border-radius:30px;  font-size:15px;  font-weight:600;  border:1px solid #d1e8f5;display:inline-block;">
                        Contact Lead Now
                      </a>
                      @endif
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- HELP -->
          <tr>
            <td align="center" bgcolor="#E9F6FB" style="padding:11px;">
              <p style="margin:0 0 6px;font-size:18px;font-weight:800;color:#253238;">
                Need Help?
              </p>
              <p style="margin:0;font-size:14px;color:#253238;">
                Email us at:
                <a href="mailto:contact@localists.com" style="color:#00AFE3;text-decoration:none;font-weight:700;">
                  contact@localists.com
                </a>
              </p>
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
        <!-- END CARD -->

      </td>
    </tr>
  </table>
</body>

</html>