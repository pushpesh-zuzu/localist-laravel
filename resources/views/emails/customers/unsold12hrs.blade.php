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
              <img src="https://localists.com/assets/localist_logo.png" alt="Localists Logo" style="max-height:45px;margin:0 auto;">
            </td>
          </tr>

          <!-- Main card -->
          <tr>
            <td style="padding:0 16px 12px 16px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#FFFFFF" style="border-radius:6px;box-shadow:0 1px 3px rgba(0,0,0,.08);overflow:hidden;">

                <!-- Intro -->
                <tr>
                  <td class="card-pad" style="padding:24px 32px;color:#4a4a4a;">
                    <p style="margin:0 0 10px 0;font-size:16px;line-height:22px;color:#00AFE3;font-weight:700;">Hi {{ $name }},</p>
                    <p style="margin:0 0 10px 0;font-size:14px;line-height:20px;color:#000;font-weight:700;">We noticed you haven’t connected with a professional yet – we’re here to help!</p>
                    <p style="margin:0;font-size:14px;line-height:22px;color:#000;">
                      To make things easier, we’ve selected a few top-rated professionals who are ready to help with your <b>{{ $service_name }}</b> request.
                    </p>
                  </td>
                </tr>

                <!-- Feature chips -->
                <tr>
                  <td style="padding:0 0 8px 0;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#E3F6FC">
                      <tr>
                        <td style="padding:20px 24px;">
                          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                              <!-- Chip 1 -->
                              <td class="stack" align="center" valign="top" style="padding:8px;">
                                <img src="https://stratus.campaign-image.eu/images/3r4jt58wexpires30dayszcv1_zc_v1_237907000000625261.png" width="41" height="41" alt="" style="margin:0 auto -20px auto;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" bgcolor="#00AFE3" style="border-radius:7px;margin-top:8px;">
                                  <tr><td style="padding:13px 12px;text-align:center;"><span style="color:#fff;font-size:14px;font-weight:700;">Trusted by customers</span></td></tr>
                                </table>
                              </td>
                              <!-- Chip 2 -->
                              <td class="stack" align="center" valign="top" style="padding:8px;">
                                <img src="https://stratus.campaign-image.eu/images/k8j3jgw3expires30dayszcv1_zc_v1_237907000000625261.png" width="41" height="41" alt="" style="margin:0 auto -20px auto;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" bgcolor="#00AFE3" style="border-radius:7px;margin-top:8px;">
                                  <tr><td style="padding:13px 12px;text-align:center;"><span style="color:#fff;font-size:14px;font-weight:700;">Quick to respond</span></td></tr>
                                </table>
                              </td>
                              <!-- Chip 3 -->
                              <td class="stack" align="center" valign="top" style="padding:8px;">
                                <img src="https://stratus.campaign-image.eu/images/r7723utvexpires30dayszcv1_zc_v1_237907000000625261.png" width="41" height="41" alt="" style="margin:0 auto -20px auto;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" bgcolor="#00AFE3" style="border-radius:7px;margin-top:8px;">
                                  <tr><td style="padding:13px 12px;text-align:center;"><span style="color:#fff;font-size:14px;font-weight:700;">Highly rated for quality and reliability</span></td></tr>
                                </table>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- Section header -->
                <tr>
                  <td style="padding:23px 24px 6px 24px;">
                    <table role="presentation" align="center" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #00AFE3;border-radius:2px;">
                      <tr><td style="padding:6px 22px;"><span style="color:#00AFE3;font-weight:700;font-size:18px;line-height:22px;">Here are a few you might like:</span></td></tr>
                    </table>
                  </td>
                </tr>

                <!-- Sellers loop -->

                <!-- Sellers loop -->
                @foreach ($sellerDetails as $sellerDetail)
                <tr>
                <td style="padding:10px 24px 0 24px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <!-- Row 1: avatar + name + rating | service -->
                    <tr>
                        <!-- Left: avatar + name -->
                        <td class="stack" valign="top" style="width:70%; padding-right:8px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                            <td valign="top" style="padding-right:8px;">
                                @if(!empty($sellerDetail->profile_image))
                                <img
                                    src="{{ $baseUrl }}/admin/storage/app/public/images//users/{{ $sellerDetail->profile_image }}"
                                    width="24" height="24" >
                                @else
                                <img
                                    src="https://storage.googleapis.com/tagjs-prod.appspot.com/v1/WxmUAzbzpK/h2vyi0hf_expires_30_days.png"
                                    width="24" height="24" >
                                @endif
                            </td>
                            <td valign="top">
                                <div style="font-weight:700; font-size:12px; line-height:16px; color:#00AFE3; margin-bottom:6px;">
                                {{ $sellerDetail->name ? explode(' ', trim($sellerDetail->name))[0] : '' }}
                                </div>

                            </td>
                            </tr>
                        </table>
                        </td>

                        <!-- Right: service -->
                        <td class="stack right" valign="top" align="right"
                            style="width:30%; font-weight:700; font-size:12px; line-height:16px; color:#000;">
                        {{ $service_name }}
                        </td>
                    </tr>

                    <!-- Row 2: postcode + distance -->
                    <tr>
                        <td colspan="2" align="center"
                            style="padding-top:8px; font:400 11px/16px Helvetica,Arial,sans-serif; color:#000;">
                        @if(!empty($sellerDetail->postcode))
                            {{ explode(' ', trim($sellerDetail->postcode))[0] }}
                            &nbsp;&bull;&nbsp;
                        @endif
                        {{ $sellerDetail->distance }} miles away
                        </td>
                    </tr>
                    </table>
                </td>
                </tr>

                <!-- Divider -->
                <tr>
                <td style="padding:10px 24px 0 24px;">
                    <div style="border-bottom:1px solid #EFEFEF; height:1px; line-height:1px; font-size:0;">&nbsp;</div>
                </td>
                </tr>
                @endforeach


                <!-- End main card spacer -->
                <tr><td style="height:24px;line-height:24px;font-size:0;">&nbsp;</td></tr>
              </table>
            </td>
          </tr>

          <!-- Help strip -->
          <tr>
            <td style="padding:10px 16px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#d8edf8" style="border-radius:6px;">
                <tr>
                  <td align="center" style="padding:19px 16px;">
                    <div style="font:700 18px/22px Helvetica,Arial,sans-serif;color:#000;max-width:482px;">
                      Need help or have questions? contact@localists.com
                    </div>
                    <div style="font:400 12px/18px Helvetica,Arial,sans-serif;color:#000;margin-top:6px;">
                      – The Localists Team
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Optional footer (commented in your original) -->
          <!--
          <tr>
            <td align="center" style="padding:10px 12px;background:#111637;border-radius:4px;color:#fff;font:400 10px/14px Helvetica,Arial,sans-serif;">
              Manage your email preferences <a href="{{$baseUrl}}/user/notifications/e-mail-notification" style="color:#fff;text-decoration:underline;">here</a>.<br>
              {{\App\Helpers\CustomHelper::setting_value('website_address','')}}
            </td>
          </tr>
          -->

        </table>

      </td>
    </tr>
  </table>
</body>
</html>
