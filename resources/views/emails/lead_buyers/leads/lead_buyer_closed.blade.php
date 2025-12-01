<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lead Closed</title>
  <style>
    body { margin:0; background-color:#f1f2f4; font-family:"Lato", Helvetica, Arial, sans-serif; color:#4a4a4a; -webkit-font-smoothing:antialiased; }
    .email-wrap { width:100%; background:#f1f2f4; padding:32px 0; }
    .email-container { max-width:600px; margin:0 auto; padding:0 16px; box-sizing:border-box; }
    .logo { max-height:50px; display:block; margin:0 auto 20px; max-width:100%; }
    .btn { display:inline-block; background:#00afe3; color:#fff !important; text-decoration:none; font-size:14px; font-weight:700; padding:12px 24px; border-radius:4px; font-family:Helvetica, Arial, sans-serif; line-height:1; }
    h1 { font-size:22px; font-weight:600; color:#333; margin:0 0 8px 0; font-family:Helvetica, Arial, sans-serif; }
    .highlight { color:#61696d; margin-bottom:12px; margin-top:32px; font-size:15px; font-family:Helvetica, Arial, sans-serif; }
    p { color:#61696d; margin:0 0 12px 0; font-family:Helvetica, Arial, sans-serif; font-size:15px; line-height:1.5; }
    a { color:#007bff; }
    .card { background:#fff; border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.04); padding:20px; margin-bottom:18px; }
    .section-header { background:#d8edf8; color:#1a588c; padding:12px 16px; font-weight:600; border-top-left-radius:4px; border-top-right-radius:4px; }
    .muted { color:#9aa1a6; }
    .lead-meta { margin-top:12px; background:#f5f9fc; padding:12px; border-radius:4px; font-size:15px; }
    .tag { display:inline-block; padding:5px 10px; margin:4px 4px 4px 0; border-radius:20px; font-size:12px; }
    @media only screen and (max-width:600px) {
      .email-container { width:100% !important; padding:0 12px !important; }
      .btn { font-size:16px !important; padding:12px 20px !important; display:block !important; width:100% !important; box-sizing:border-box !important; text-align:center !important; }
      h1 { font-size:20px !important; }
    }
  </style>
</head>
<body>
  <table width="100%" cellpadding="0" cellspacing="0" border="0" class="email-wrap" bgcolor="#f1f2f4">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" border="0" class="email-container" style="max-width:600px; width:100%;">

          <!-- Logo -->
          <tr>
            <td style="padding:0 16px 8px 16px; text-align:center;">
              <img src="{{ $baseUrl }}/assets/localist_logo_1.png" alt="Localists Logo" class="logo">
            </td>
          </tr>

          <!-- Main Card -->
          <tr>
            <td align="center">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                <tr>
                  <td style="padding:20px;">
                    <h1 style="text-align:center;">Hi {{ $name }}</h1>
                    <div class="highlight" style="text-align:center;"><strong>{{ $service_name }} lead is no longer available</strong></div>

                    <p style="margin-bottom:12px;color:#61696d;">
                      Don’t worry — new <strong>{{ $service_name }}</strong> leads are added regularly. We’ll notify you when a matching one becomes available.
                    </p>

                    <!-- tags -->
                    <div style="margin-bottom:12px;">
                      @if($phone_verified)<span class="tag" style="background-color:#f39ac3; color:#fff">📞 Verified Phone</span>@endif
                      @if($has_additional_details)<span class="tag" style="background-color:#e6e6e6; color:#333">📋 Additional details</span>@endif
                      @if($is_frequent_user)<span class="tag" style="background-color:#a0d8ef">🔁 Frequent user</span>@endif
                      @if($is_urgent)<span class="tag" style="background-color:#ffd9a6">⏰ Urgent</span>@endif
                      @if($is_high_hiring)<span class="tag" style="background-color:#d1f7d9">🚀 High hiring</span>@endif
                    </div>

                    <!-- Contact details -->
                    <div class="lead-meta" style="margin-top:12px; background:#f5f9fc; padding:12px; border-radius:4px;">
                      <div style="margin-bottom:8px;"><strong>🏅</strong> {{ $credit_score }} credits</div>
                      <div style="margin-bottom:6px;"><strong>📍</strong> {{ $postcode }}</div>
                      <div style="margin-bottom:6px;"><strong>📞</strong> {{ $masked_phone }}</div>
                      <div><strong>✉️</strong> {{ $masked_email }}</div>
                    </div>

                    <!-- === BULLETPROOF CTA: keep inside card and aligned left/center/right by changing td align === -->

                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:8px;">
                        <tr>
                            <td align="center">
                            <table cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                <td style="border-radius:4px; background:#007bff; text-align:center;">
                                    <a href="{{ $baseUrl }}/sellers/leads"
                                    style="display:inline-block; padding:12px 24px; font-size:14px; color:#ffffff; text-decoration:none; border-radius:4px; font-family:Arial, sans-serif;">
                                    See Similar Leads
                                    </a>
                                </td>
                                </tr>
                            </table>
                            </td>
                        </tr>
                        </table>

                    <!-- === end CTA === -->

                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr><td style="height:18px; font-size:0; line-height:18px;">&nbsp;</td></tr>

          <!-- Questions & Answers -->
          <tr>
            <td align="center">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden;">
                <tr>
                  <td class="section-header" style="background:#d8edf8; color:#1a588c; padding:12px 16px; font-weight:600;">
                    Details
                  </td>
                </tr>
                <tr>
                  <td style="padding:16px;">
                    @if(!empty($questionsAndAnswers))
                      @foreach ($questionsAndAnswers as $qa)
                        <p style="margin:8px 0 4px 0;"><strong>{{ $qa['question'] }}</strong></p>
                        <p style="margin:0 0 12px 0;">{{ $qa['answer'] }}</p>
                      @endforeach
                    @else
                      <p class="muted">No additional details provided.</p>
                    @endif
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr><td style="height:18px; font-size:0; line-height:18px;">&nbsp;</td></tr>

          <!-- Need Help -->
          <tr>
            <td align="center">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden;">
                <tr>
                  <td class="section-header" style="background:#d8edf8; color:#1a588c; padding:12px 16px; font-weight:600;">
                    Need Help?
                  </td>
                </tr>
                <tr>
                  <td style="padding:16px;">
                    <p class="muted">Email us at <a href="mailto:{{ \App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com') }}">{{ \App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com') }}</a>.</p>
                  <p><br>Kind Regards,<br>Localists Team</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr><td style="height:18px; font-size:0; line-height:18px;">&nbsp;</td></tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding:0 16px 40px 16px; font-family:Helvetica, Arial, sans-serif; color:#666; font-size:13px;">
              Manage your email preferences <a href="{{ $baseUrl }}/settings/notifications/e-mail-notification">here</a>.<br>
              {{ \App\Helpers\CustomHelper::setting_value('website_address','') }}
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
