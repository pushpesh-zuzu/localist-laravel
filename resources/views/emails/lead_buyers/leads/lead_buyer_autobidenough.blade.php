<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>New Leads Matched for You</title>
</head>
<body style="margin:0;padding:0;background-color:#ffffff;font-family:'Lato',Helvetica,Arial,sans-serif;font-size:16px;line-height:22px;color:#4a4a4a;">

  <!-- Outer wrapper -->
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#ffffff;">
    <tr>
      <td align="center">

        <!-- Container -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;padding:0 16px;box-sizing:border-box;">

          <!-- Spacer top -->
          <tr><td style="height:16px;line-height:16px;font-size:0;">&nbsp;</td></tr>

          <!-- Logo -->
          <tr>
            <td align="center" style="padding-bottom:12px;">
              <img src="{{ $baseUrl }}/assets/localist_logo.png" alt="Localists Logo" style="max-height:44px;display:block;border:0;">
            </td>
          </tr>

          <!-- Header -->
          <tr>
            <td style="padding-bottom:12px;text-align:center;">
              <h1 style="font-size:20px;font-weight:600;color:#333;margin:0;line-height:26px;">Hi {{ $name }}</h1>
              <div style="font-size:14px;color:#61696d;margin-top:6px;">You've got new leads!</div>
            </td>
          </tr>

          <!-- Small spacer between header and first card -->
          <tr><td style="height:8px;line-height:8px;font-size:0;">&nbsp;</td></tr>

          <!-- Loop Through Leads -->
          @foreach($leadDetailsList as $lead)
          <tr>
            <td align="center" style="padding-bottom:12px;">
              <!-- card wrapper table for email-safe rounded corners & shadow -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,0.04);overflow:hidden;">
                <tr>
                  <td style="padding:14px 16px;font-family:Helvetica,Arial,sans-serif;color:#4a4a4a;">
                    <div style="margin:0 0 8px 0;color:#333;font-size:15px;">
                      <strong>{{ $lead['lead_name'] }}</strong> is looking for <strong>{{ $lead['service_name'] }}</strong>.
                    </div>

                    <!-- Tags -->
                    <div style="margin:6px 0 10px 0;">
                      @if($lead['phone_verified'])
                        <span style="display:inline-block;padding:4px 8px;margin:2px;border-radius:16px;font-size:12px;color:#fff;background:#f39ac3;">📞 Verified Phone</span>
                      @endif
                      @if($lead['has_additional_details'])
                        <span style="display:inline-block;padding:4px 8px;margin:2px;border-radius:16px;font-size:12px;background:#ccc;color:#333;">📋 Additional details</span>
                      @endif
                      @if($lead['is_frequent_user'])
                        <span style="display:inline-block;padding:4px 8px;margin:2px;border-radius:16px;font-size:12px;background:#a0d8ef;color:#000;">🔁 Frequent user</span>
                      @endif
                      @if($lead['is_urgent'])
                        <span style="display:inline-block;padding:4px 8px;margin:2px;border-radius:16px;font-size:12px;background:#ffa07a;color:#000;">⏰ Urgent</span>
                      @endif
                      @if($lead['is_high_hiring'])
                        <span style="display:inline-block;padding:4px 8px;margin:2px;border-radius:16px;font-size:12px;background:#90ee90;color:#000;">🚀 High hiring</span>
                      @endif
                    </div>

                    <!-- Contact Info box -->
                    <div style="background:#f5f9fc;padding:10px;border-radius:4px;font-size:15px;line-height:20px;color:#333;">
                      <div style="margin-bottom:6px;"><strong>🏅</strong> {{ $lead['credit_score'] }} credits deduct for this lead</div>
                      <div style="margin-bottom:6px;"><strong>💸</strong> {{ $lead['remaining_credit'] }} credits will remain after purchase.</div>
                      <div style="margin-bottom:6px;"><strong>📍</strong> {{ $lead['postcode'] }}</div>
                      <div style="margin-bottom:6px;"><strong>📞</strong> {{ $lead['masked_phone'] }}</div>
                      <div><strong>✉️</strong> {{ $lead['masked_email'] }}</div>
                    </div>

                    <!-- CTA -->
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px;">
                      <tr>
                        <td align="center" style="padding:0 6px;">
                          @if($lead['hasEnoughCredits'])
                            <a href="{{ $baseUrl }}/sellers/leads/save-for-later" style="display:inline-block;background:#00afe3;color:#fff;text-decoration:none;font-size:15px;font-weight:700;padding:10px 16px;border-radius:4px;">Contact Lead Now</a>
                          @else
                            <a href="{{ $baseUrl }}/settings/billing/my-credits" style="display:inline-block;background:#00afe3;color:#fff;text-decoration:none;font-size:15px;font-weight:700;padding:10px 16px;border-radius:4px;">Top Up Credits to Contact</a>
                          @endif
                        </td>
                      </tr>
                    </table>

                    <!-- Details (Questions & Answers) -->
                    @if(!empty($lead['questionsAndAnswers']))
                    <div style="margin-top:12px;">
                      <div style="background:#d8edf8;color:#1a588c;padding:10px 12px;border-radius:4px;font-weight:600;font-size:14px;">Details</div>
                      <div style="padding-top:8px;">
                        @foreach ($lead['questionsAndAnswers'] as $qa)
                          <div style="margin:8px 0 4px 0;font-weight:600;font-size:14px;">{{ $qa['question'] }}</div>
                          <div style="margin:0 0 10px 0;color:#61696d;">{{ $qa['answer'] }}</div>
                        @endforeach
                      </div>
                    </div>
                    @endif

                  </td>
                </tr>
              </table>
            </td>
          </tr>
          @endforeach

          <!-- Help Section (separate card) -->
          <tr>
            <td align="center" style="padding-top:8px;padding-bottom:8px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,0.04);overflow:hidden;">
                <tr>
                  <td style="padding:10px 16px;">
                    <div style="background:#d8edf8;color:#1a588c;padding:10px;border-radius:4px;font-weight:600;font-size:14px;">Need Help?</div>
                    <div style="margin-top:8px;color:#61696d;font-size:15px;">
                      Email us at
                      <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}" style="color:#007bff;text-decoration:none;">{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}</a>.
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding:14px 6px 24px 6px;font-size:12px;color:#666;">
              Manage your email preferences <a href="{{ $baseUrl }}/settings/notifications/e-mail-notification" style="color:#007bff;text-decoration:none;">here</a>.<br>
              {{ \App\Helpers\CustomHelper::setting_value('website_address','') }}
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>

</body>
</html>
