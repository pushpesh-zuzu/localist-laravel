<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>New Lead Matched for You</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f2f4;">

  <!-- Full background -->
  <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f1f2f4" role="presentation">
    <tr>
      <td align="center">

        <!-- Main wrapper -->
        <table width="600" cellpadding="0" cellspacing="0" border="0" role="presentation" style="max-width:600px; width:100%;">

          <!-- Logo -->
          <tr>
            <td align="center" style="padding:16px;">
              <img src="{{ $baseUrl }}/assets/localist_logo.png" alt="Localists Logo" width="160" style="border:0; display:block; max-width:160px; height:auto;">
            </td>
          </tr>

          <!-- Header -->
          <tr>
            <td bgcolor="#ffffff" align="center" style="padding:20px; border-radius:4px 4px 0 0;">
              <h1 style="font-size:20px; font-weight:600; color:#333; margin:0; font-family:Helvetica, Arial, sans-serif; line-height:26px;">Hi {{ $name }}</h1>
              <p style="margin:6px 0 0 0; font-size:15px; line-height:1.4; color:#61696d; font-family:Helvetica, Arial, sans-serif;"><strong>You've got a new lead!</strong></p>
            </td>
          </tr>

          <!-- Loop through leads -->
          @foreach($leadDetailsList as $lead)
          <tr>
            <td bgcolor="#ffffff" style="padding:16px; border-bottom:1px solid #eee;">
              <p style="font-size:15px; line-height:1.4; color:#333; margin:0 0 8px 0;">
                <strong>{{ $lead['lead_name'] }}</strong> is looking for <strong>{{ $lead['service_name'] }}</strong>.
              </p>

              <!-- Tags (if any) -->
              @if($lead['phone_verified'] || $lead['has_additional_details'] || $lead['is_frequent_user'] || $lead['is_urgent'] || $lead['is_high_hiring'])
                <div style="margin:8px 0;">
                  @if($lead['phone_verified'])
                    <span style="display:inline-block; background-color:#f39ac3; color:#fff; padding:4px 8px; border-radius:16px; font-size:12px;">📞 Verified Phone</span>
                  @endif
                  @if($lead['has_additional_details'])
                    <span style="display:inline-block; background-color:#ccc; color:#333; padding:4px 8px; border-radius:16px; font-size:12px;">📋 Additional details</span>
                  @endif
                  @if($lead['is_frequent_user'])
                    <span style="display:inline-block; background-color:#a0d8ef; color:#000; padding:4px 8px; border-radius:16px; font-size:12px;">🔁 Frequent user</span>
                  @endif
                  @if($lead['is_urgent'])
                    <span style="display:inline-block; background-color:#ffa07a; color:#000; padding:4px 8px; border-radius:16px; font-size:12px;">⏰ Urgent</span>
                  @endif
                  @if($lead['is_high_hiring'])
                    <span style="display:inline-block; background-color:#90ee90; color:#000; padding:4px 8px; border-radius:16px; font-size:12px;">🚀 High hiring</span>
                  @endif
                </div>
              @endif

              <!-- Details -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="background-color:#f5f9fc; border-radius:4px; margin-top:10px;">
                <tr>
                  <td style="padding:12px; font-size:14px; line-height:20px; color:#333; font-family:Helvetica, Arial, sans-serif;">
                    <div style="margin-bottom:4px;">🏅 <span style="margin-left:6px;">{{ $lead['credit_score'] }} credits to respond</span></div>
                    <div style="margin-bottom:4px;">📍 <span style="margin-left:6px;">{{ $lead['postcode'] }}</span></div>
                    <div style="margin-bottom:4px;">📞 <span style="margin-left:6px;">{{ $lead['masked_phone'] }}</span></div>
                    <div>✉️ <span style="margin-left:6px;">{{ $lead['masked_email'] }}</span></div>
                  </td>
                </tr>
              </table>

              <!-- CTA -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="margin-top:12px;">
                <tr>
                  <td align="center">
                    @if($lead['hasEnoughCredits'])
                      <a href="{{ $baseUrl }}/sellers/leads"
                        style="display:inline-block; background-color:#00afe3; color:#ffffff; text-decoration:none; font-size:16px; font-weight:700; padding:12px 20px; border-radius:4px; font-family:Helvetica, Arial, sans-serif;">
                        Contact Lead Now
                      </a>
                    @else
                      <a href="{{ $baseUrl }}/settings/billing/my-credits"
                        style="display:inline-block; background-color:#00afe3; color:#ffffff; text-decoration:none; font-size:16px; font-weight:700; padding:12px 20px; border-radius:4px; font-family:Helvetica, Arial, sans-serif;">
                        Contact Lead Now
                      </a>
                    @endif
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          @endforeach

          <!-- Help Section -->
          <tr>
            <td bgcolor="#ffffff" style="padding:0; border-top:1px solid #eee;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
                <tr>
                  <td bgcolor="#d8edf8" style="padding:10px 16px; color:#1a588c; font-weight:500; font-family:Helvetica, Arial, sans-serif;">Need Help?</td>
                </tr>
                <tr>
                  <td style="padding:14px; font-size:15px; line-height:1.4; color:#61696d; font-family:Helvetica, Arial, sans-serif;">
                    Email us at
                    <a href="mailto:{{ \App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com') }}"
                      style="color:#007bff; text-decoration:none;">
                      {{ \App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com') }}
                    </a>.
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding:14px; font-size:12px; color:#666; font-family:Helvetica, Arial, sans-serif;">
              Manage your email preferences
              <a href="{{ $baseUrl }}/settings/notifications/e-mail-notification" style="color:#007bff; text-decoration:none;">here</a>.<br>
              {{ \App\Helpers\CustomHelper::setting_value('website_address','') }}
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
