<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>New Lead Matched for You</title>
  <style>
    body { margin:0; padding:0; background-color:#f1f2f4; font-family:'Lato', Helvetica, Arial, sans-serif; color:#4a4a4a; -webkit-font-smoothing:antialiased; }
    img { border:0; display:block; max-width:100%; }
    a { color:#007bff; text-decoration:none; }
    .btn { display:inline-block; background:#00afe3; color:#fff !important; text-decoration:none; font-size:15px; font-weight:700; padding:10px 20px; border-radius:6px; }
    h1 { font-size:20px; font-weight:600; color:#222; margin:0; text-align:center; }
    p { margin:0; font-size:15px; line-height:1.4; color:#61696d; }

    /* Accordion (checkbox hack). Inputs are hidden; fallback shows content. */
    input.ac-toggle { display:none !important; mso-hide:all; }
    .accordion-content { max-height:none; overflow:visible; transition:max-height 0.35s ease; }
    input.ac-toggle + .accordion-header + .accordion-content { max-height:0; overflow:hidden; padding:0; }
    input.ac-toggle:checked + .accordion-header + .accordion-content { max-height:800px; overflow:auto; padding:12px 16px; }

    .accordion-header { display:flex; justify-content:space-between; align-items:center; padding:14px 16px; background:#f8fbfd; cursor:pointer; }
    .accordion-header strong { font-size:15px; color:#222; }
    .accordion-meta { font-size:13px; color:#666; text-align:right; }

    .lead-card { border-radius:8px; background:#fff; box-shadow:0 1px 4px rgba(0,0,0,0.06); overflow:hidden; }
    .lead-details { font-size:14px; line-height:20px; color:#333; }
    .lead-details div { margin-bottom:4px; }

    .tag { display:inline-block; font-size:12px; padding:4px 8px; border-radius:12px; margin-left:6px; }

    @media only screen and (max-width:600px) {
      .email-container { width:100% !important; padding:0 12px !important; }
      h1 { font-size:18px !important; }
    }
  </style>
</head>
<body>
  <!-- Outer wrapper -->
  <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f1f2f4" role="presentation">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" border="0" class="email-container" role="presentation" style="max-width:600px; width:100%;">

          <!-- Logo (centered, tighter padding) -->
          <tr>
            <td style="padding:12px; text-align:center;">
              <img src="{{ $baseUrl }}/assets/localist_logo.png" alt="Localists Logo" style="max-height:45px; margin:0 auto; display:block;">
            </td>
          </tr>

          <!-- Header -->
          <tr>
            <td>
              <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" role="presentation" style="border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                <tr>
                  <td style="padding:16px;">
                    <h1>Hi {{ $name }}</h1>
                    <p style="text-align:center; margin-top:6px;"><strong>You've got new leads waiting for you!</strong></p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Loop through leads (compact spacing: padding:6px 0) -->
          @foreach($leadDetailsList as $lead)
          <tr>
            <td style="padding:6px 0;">
              <!-- Card container -->
              <div class="lead-card" style="margin:0 auto; max-width:560px;">
                <!-- unique checkbox id per lead for accordion -->
                <input type="checkbox" id="lead-{{ $lead['id'] }}" class="ac-toggle" />

                <!-- Header (always visible summary) -->
                <label for="lead-{{ $lead['id'] }}" class="accordion-header" style="border-top-left-radius:8px; border-top-right-radius:8px; margin:0;">
                  <div style="line-height:1.1;">
                    <strong style="display:block;">{{ $lead['lead_name'] }}</strong>
                    <span style="font-size:13px; color:#666;">{{ $lead['service_name'] }}</span>
                  </div>
                  <div class="accordion-meta" style="white-space:nowrap;">
                    {{ $lead['credit_score'] }} credits<br>
                    <span style="font-size:13px;">📍 {{ $lead['postcode'] }}</span>
                  </div>
                </label>

                <!-- Content (collapsible on supporting clients; visible fallback otherwise) -->
                <div class="accordion-content" style="background:#ffffff; border-bottom-left-radius:8px; border-bottom-right-radius:8px;">
                  <div style="padding:12px 0 0 0; border-top:1px solid #eee;">
                    <div class="lead-details" style="padding:0 16px 8px 16px;">
                      <div>🏅 <strong>Credits to respond:</strong> {{ $lead['credit_score'] }}</div>
                      <div>📍 <strong>Postcode:</strong> {{ $lead['postcode'] }}</div>
                      <div>📞 <strong>Phone:</strong> {{ $lead['masked_phone'] }}</div>
                      <div>✉️ <strong>Email:</strong> {{ $lead['masked_email'] }}</div>

                      <!-- Optional tags line -->
                      <div style="margin-top:6px;">
                        @if(!empty($lead['phone_verified']) && $lead['phone_verified'])
                          <span class="tag" style="background:#f39ac3; color:#fff;">📞 Verified Phone</span>
                        @endif
                        @if(!empty($lead['has_additional_details']) && $lead['has_additional_details'])
                          <span class="tag" style="background:#ccc; color:#333;">📋 Additional details</span>
                        @endif
                        @if(!empty($lead['is_urgent']) && $lead['is_urgent'])
                          <span class="tag" style="background:#ffa07a; color:#000;">⏰ Urgent</span>
                        @endif
                        @if(!empty($lead['is_high_hiring']) && $lead['is_high_hiring'])
                          <span class="tag" style="background:#90ee90; color:#000;">🚀 High hiring</span>
                        @endif
                      </div>
                    </div>

                    <!-- CTA -->
                    <div style="text-align:center; padding:8px 16px 16px 16px;">
                      @if(!empty($lead['hasEnoughCredits']))
                        <a href="{{ $baseUrl }}/sellers/leads" class="btn">Contact Now</a>
                      @else
                        <a href="{{ $baseUrl }}/settings/billing/my-credits" class="btn">Buy Credits to Contact</a>
                      @endif
                    </div>
                  </div>
                </div>
              </div>
            </td>
          </tr>
          @endforeach

          <!-- Help -->
          <tr>
            <td style="padding-top:10px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" role="presentation" style="border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                <tr>
                  <td style="padding:12px; background:#d8edf8; font-weight:600; color:#1a588c;">Need Help?</td>
                </tr>
                <tr>
                  <td style="padding:12px; font-size:14px; color:#555;">
                    Email us at <a href="mailto:{{ \App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com') }}">{{ \App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com') }}</a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding:12px; font-size:12px; color:#888;">
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
