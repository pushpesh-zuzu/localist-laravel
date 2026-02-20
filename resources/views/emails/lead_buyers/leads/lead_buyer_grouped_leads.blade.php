<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>New Lead Matched for You</title>

  <style type="text/css">
    /* hide checkbox off-screen but keep it focusable/clickable via the label */
    input.ac-toggle {
      position: absolute !important;
      left: -9999px !important;
      top: auto !important;
      width: 1px !important;
      height: 1px !important;
      overflow: hidden !important;
      opacity: 0 !important;
      mso-hide: all;
    }

    /* collapsed by default for clients that support :checked */
    input.ac-toggle + label.ac-header + .accordion-content {
      max-height: 0 !important;
      overflow: hidden !important;
      padding: 0 16px !important;
      transition: max-height .35s ease !important;
      -webkit-transition: max-height .35s ease !important;
    }

    /* expanded when checked */
    input.ac-toggle:checked + label.ac-header + .accordion-content {
      max-height: 900px !important;
      padding: 12px 16px 16px 16px !important;
      overflow: auto !important;
    }

    /* caret rotation - single static text "Details" remains the same */
    .ac-header .caret {
      display:inline-block;
      vertical-align:middle;
      margin-left:6px;
      transition: transform .18s ease;
    }
    input.ac-toggle:checked + label.ac-header .caret {
      transform: rotate(180deg) !important;
    }

    /* make label clickable area */
    label.ac-header {
      display:block;
      cursor:pointer;
      -webkit-tap-highlight-color: transparent;
    }

    /* small responsive tweak */
    @media only screen and (max-width:600px) {
      .email-container { width:100% !important; padding:0 12px !important; }
      h1 { font-size:18px !important; }
    }
  </style>
</head>
<body style="margin:0;padding:0;background:#f1f2f4;font-family:'Lato', Arial, Helvetica, sans-serif;color:#4a4a4a;">

  <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f1f2f4" role="presentation">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" border="0" role="presentation" style="width:600px;max-width:600px;margin:0 auto;" class="email-container">

          <!-- logo -->
          <tr>
            <td align="center" style="padding:12px 12px 8px 12px;">
              <img src="{{ $baseUrl }}/assets/localist_logo_1.png" alt="Localists Logo" style="display:block;max-height:45px;margin:0 auto;height:auto;">
            </td>
          </tr>

          <!-- header card -->
          <tr>
            <td align="center" style="padding:0 0 10px 0;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
                     style="max-width:560px;width:100%;margin:0 auto;background:#ffffff;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.08);overflow:hidden;">
                <tr>
                  <td style="padding:16px;">
                    <p style="text-align:center;">Hi <strong style="color: #222;">{{ ucfirst($name) }}</strong>,</p>
                    <p style="margin:8px 0 0 0;font-size:15px;line-height:1.4;color:#61696d;text-align:center;"><strong>You've got new leads waiting for you!</strong></p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- leads loop -->
          @foreach($leadDetailsList as $lead)
          <tr>
            <td align="center" style="padding:8px 0;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
                     style="max-width:560px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.06);margin:0 auto;">
                <tr>
                  <td style="padding:0;">
                    <div style="padding:0;margin:0;">

                      <!-- checkbox MUST be directly before label and accordion-content -->
                      <input type="checkbox" id="lead-{{ $lead['id'] }}" class="ac-toggle" />

                      <!-- header label -->
                      <label for="lead-{{ $lead['id'] }}" class="ac-header" style="display:block;cursor:pointer;padding:14px 16px;background:#f8fbfd;border-top-left-radius:8px;border-top-right-radius:8px;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
                          <tr>
                            <td valign="top" style="font-size:15px;font-weight:700;color:#222;padding-right:8px;">
                              {{ $lead['lead_name'] }}
                              <div style="font-size:13px;color:#666;font-weight:400;margin-top:4px;">{{ $lead['service_name'] }}</div>
                              <div style="margin-top:4px;font-size:13px;color:#666;font-weight:400;">✉️ {{ $lead['masked_email'] }}</div>
                            </td>

                            <td valign="top" align="right" style="width:160px;font-size:13px;color:#666;">
                              <div style="font-size:14px;font-weight:700;color:#222;">{{ $lead['credit_score'] }} credits</div>
                              <div style="margin-top:4px;">📍 {{ $lead['postcode'] }}</div>
                              <div style="margin-top:4px;">📞 {{ $lead['masked_phone'] }}</div>

                              <!-- single static toggle text + rotating caret -->

                            </td>
                          </tr>
                        </table>
                      </label>

                      <!-- accordion content -->
                      <div class="accordion-content" style="max-height:none; overflow:visible;background:#ffffff;border-bottom-left-radius:8px;border-bottom-right-radius:8px;">
                        <div style="padding:12px 16px 16px 16px;border-top:1px solid #eee;">
                          <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="font-size:14px;color:#333;">
                            <tr><td style="padding-bottom:8px;">🏅 <strong>Credits to respond:</strong> {{ $lead['credit_score'] }}</td></tr>
                            <tr><td style="padding-bottom:8px;">📍 <strong>Postcode:</strong> {{ $lead['postcode'] }}</td></tr>
                            <tr><td style="padding-bottom:8px;">📞 <strong>Phone:</strong> {{ $lead['masked_phone'] }}</td></tr>
                            <tr><td style="padding-bottom:8px;">✉️ <strong>Email:</strong> {{ $lead['masked_email'] }}</td></tr>

                            <!-- tags row -->
                            <tr>
                              <td style="padding-top:6px;">
                                @if(!empty($lead['phone_verified']) && $lead['phone_verified'])
                                  <span style="display:inline-block;font-size:12px;padding:4px 8px;border-radius:12px;background:#f39ac3;color:#fff;margin-right:6px;">📞 Verified Phone</span>
                                @endif
                                @if(!empty($lead['has_additional_details']) && $lead['has_additional_details'])
                                  <span style="display:inline-block;font-size:12px;padding:4px 8px;border-radius:12px;background:#ccc;color:#333;margin-right:6px;">📋 Additional details</span>
                                @endif
                                @if(!empty($lead['is_urgent']) && $lead['is_urgent'])
                                  <span style="display:inline-block;font-size:12px;padding:4px 8px;border-radius:12px;background:#ffa07a;color:#000;margin-right:6px;">⏰ Urgent</span>
                                @endif
                                @if(!empty($lead['is_high_hiring']) && $lead['is_high_hiring'])
                                  <span style="display:inline-block;font-size:12px;padding:4px 8px;border-radius:12px;background:#90ee90;color:#000;margin-right:6px;">🚀 High hiring</span>
                                @endif
                              </td>
                            </tr>

                            <!-- CTA -->
                            <tr>
                              <td style="padding-top:12px;text-align:center;">
                                @if(!empty($lead['hasEnoughCredits']))
                                  <a href="{{ $postloginUrl }}/sellers/leads" style="display:inline-block;background:#00afe3;color:#fff;text-decoration:none;font-size:15px;font-weight:700;padding:10px 20px;border-radius:6px;">Contact Lead Now</a>
                                @else
                                  <a href="{{ $postloginUrl }}/settings/billing/my-credits" style="display:inline-block;background:#00afe3;color:#fff;text-decoration:none;font-size:15px;font-weight:700;padding:10px 20px;border-radius:6px;">Contact Lead Now</a>
                                @endif
                              </td>
                            </tr>

                          </table>
                        </div>
                      </div>
                      <!-- end accordion -->

                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          @endforeach

          <!-- help card -->
          <tr>
            <td align="center" style="padding-top:10px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
                     style="max-width:560px;width:100%;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                <tr>
                  <td style="padding:12px;background:#d8edf8;font-weight:600;color:#1a588c;">Need Help?</td>
                </tr>
                <tr>
                  <td style="padding:12px;font-size:14px;color:#555;">
                    Email us at <a href="mailto:{{ \App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com') }}">{{ \App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com') }}</a>
                   <p><br>Kind Regards,<br>Localists Team</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- footer -->
          <!-- <tr>
            <td align="center" style="padding:12px;font-size:12px;color:#888;">
              Manage your email preferences <a href="{{ $baseUrl }}/settings/notifications/e-mail-notification">here</a>.<br>
              {{ \App\Helpers\CustomHelper::setting_value('website_address','') }}
            </td>
          </tr> -->

          <tr>
            <td align="center" style="padding:12px;font-size:12px;color:#888;">
              <p style="margin:0; font-size:11px; line-height:16px; color:#111637;">
                Manage your email preferences
                <a href="{{ $baseUrl }}/settings/notifications/e-mail-notification"
                  style="color:#111637; text-decoration:underline;">here</a>
                or, click here to 
                <a href="{{ url('/api/unsubscribe-status-update/' . $userId . '/user') }}"
                  style="color:#00AFE3; text-decoration:underline;font-weight:600;">unsubscribe</a> and we will remove you from our emailing list.
              </p>
            </td>
          </tr>
          

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
