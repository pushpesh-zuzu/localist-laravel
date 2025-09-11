<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>New Leads Matched for You</title>
</head>
<body style="margin: 0; padding: 0; background-color: #ffffff; font-family: 'Lato', Helvetica, Arial, sans-serif; font-size: 18px; font-weight: 510; line-height: 25px; color: #4a4a4a;">

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #ffffff; padding: 32px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" style="max-width: 600px; width: 100%; padding: 0 16px; box-sizing: border-box;">

          <!-- Logo -->
          <tr>
            <td align="center" style="padding-bottom: 20px;">
              <img src="{{ $baseUrl }}/assets/localist_logo.png" alt="Localists Logo" style="max-height: 50px;">
            </td>
          </tr>

          <!-- Header -->
          <tr>
            <td style="padding-bottom: 20px;">
              <h1 style="font-size: 22px; font-weight: 600; color: #333333; margin: 0 0 10px; text-align: center;">
                 Hi {{ $name }},
                 <p> You've got new leads!</p></h1>
            </td>
          </tr>

          <!-- Loop Through Leads -->
          @foreach($leadDetailsList as $lead)
          <tr>
            <td style="background: #ffffff; padding: 32px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04); margin-bottom: 20px;">
              <p style="color: #61696d;">{{ $lead['lead_name'] }} is looking for <strong>{{ $lead['service_name'] }}</strong>.</p>

              <!-- Tags -->
              <div style="margin: 10px 0;">
                @if($lead['phone_verified'])
                  <span style="display: inline-block; padding: 5px 10px; margin: 2px; border-radius: 20px; font-size: 12px; color: #fff; background-color: #f39ac3;">📞 Verified Phone</span>
                @endif
                @if($lead['has_additional_details'])
                  <span style="display: inline-block; padding: 5px 10px; margin: 2px; border-radius: 20px; font-size: 12px; background-color: #ccc; color: #333;">📋 Additional details</span>
                @endif
                @if($lead['is_frequent_user'])
                  <span style="display: inline-block; padding: 5px 10px; margin: 2px; border-radius: 20px; font-size: 12px; background-color: #a0d8ef; color: #000;">🔁 Frequent user</span>
                @endif
                @if($lead['is_urgent'])
                  <span style="display: inline-block; padding: 5px 10px; margin: 2px; border-radius: 20px; font-size: 12px; background-color: #a0d8ef; color: #000;">⏰ Urgent</span>
                @endif
                @if($lead['is_high_hiring'])
                  <span style="display: inline-block; padding: 5px 10px; margin: 2px; border-radius: 20px; font-size: 12px; background-color: #a0d8ef; color: #000;">🚀 High hiring</span>
                @endif
              </div>

              <!-- Contact Info -->
              <div style="margin-top: 16px; background-color: #f5f9fc; padding: 16px; border-radius: 4px; font-size: 16px; line-height: 24px;">
                <strong>🏅</strong> {{ $lead['credit_score'] }} credits deduct for this lead<br>
                <strong>💸</strong> {{ $lead['remaining_credit'] }} credits will remain after purchase.<br>
                <strong>📍</strong> {{ $lead['postcode'] }}<br>
                <strong>📞</strong> {{ $lead['masked_phone'] }}<br>
                <strong>✉️</strong> {{ $lead['masked_email'] }}<br>
              </div>

              <!-- CTA -->
              @if($lead['hasEnoughCredits'])
                <a href="{{ $baseUrl }}/sellers/leads/save-for-later" style="display: block; background-color: #00afe3; color: #ffffff !important; text-decoration: none; font-size: 16px; font-weight: bold; padding: 14px; border-radius: 4px; margin-top: 20px; text-align: center;">Contact Lead Now</a>
              @else
                <a href="{{ $baseUrl }}/settings/billing/my-credits" style="display: block; background-color: #00afe3; color: #ffffff !important; text-decoration: none; font-size: 16px; font-weight: bold; padding: 14px; border-radius: 4px; margin-top: 20px; text-align: center;">Top Up Credits to Contact</a>
              @endif

              <!-- Questions & Answers -->
              <div style="margin-top: 32px;">
                <div style="background-color: #d8edf8; color: #1a588c; padding: 12px 20px; margin: 0 -32px 20px -32px; border-top-left-radius: 4px; border-top-right-radius: 4px; font-weight: bold;">Details</div>
                @foreach ($lead['questionsAndAnswers'] as $qa)
                  <p style="margin: 8px 0;"><strong>{{ $qa['question'] }}</strong></p>
                  <p style="margin: 0 0 12px;">{{ $qa['answer'] }}</p>
                @endforeach
              </div>
            </td>
          </tr>
          @endforeach

          <!-- Help Section -->
          <tr>
            <td style="background: #ffffff; padding: 32px; border-radius: 4px; margin-top: 20px;">
              <div style="background-color: #d8edf8; color: #1a588c; padding: 12px 20px; margin: -32px -32px 20px -32px; border-top-left-radius: 4px; border-top-right-radius: 4px; font-weight: bold;">Need Help?</div>
              <p style="color: #61696d;">
               Email us at
                <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}">{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}</a>.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding: 20px; font-size: 12px; color: #666666;">
              Manage your email preferences <a href="{{ $baseUrl }}/settings/notifications/e-mail-notification" style="color: #007bff;">here</a>.<br>
              {{ \App\Helpers\CustomHelper::setting_value('website_address','') }}
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
