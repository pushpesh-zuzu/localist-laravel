<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Lead Hired</title>
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

          <!-- Main Card -->
          <tr>
            <td style="background: #ffffff; padding: 32px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);">
              <h1 style="font-size: 22px; font-weight: 600; color: #333333; margin: 0 0 10px;"> Hi {{ $name }}, the {{ $service_name }} lead is no longer available.</h1>
              <p style="color: #61696d;">Unfortunately, this lead has already been purchased by another customer.
              But don't worry — new {{ $service_name }} leads are added regularly, and we'll notify you when a matching one becomes available.</strong>.</p>
              <!-- Tags -->
              <div style="margin: 10px 0;">
                @if($phone_verified)
                  <span style="display: inline-block; padding: 5px 10px; margin: 2px; border-radius: 20px; font-size: 12px; color: #fff; background-color: #f39ac3;">📞 Verified Phone</span>
                @endif
                @if($has_additional_details)
                  <span style="display: inline-block; padding: 5px 10px; margin: 2px; border-radius: 20px; font-size: 12px; background-color: #ccc; color: #333;">📋 Additional details</span>
                @endif
                @if($is_frequent_user)
                  <span style="display: inline-block; padding: 5px 10px; margin: 2px; border-radius: 20px; font-size: 12px; background-color: #a0d8ef; color: #000;">🔁 Frequent user</span>
                @endif
                @if($is_urgent)
                  <span style="display: inline-block; padding: 5px 10px; margin: 2px; border-radius: 20px; font-size: 12px; background-color: #a0d8ef; color: #000;">⏰ Urgent</span>
                @endif
                @if($is_high_hiring)
                  <span style="display: inline-block; padding: 5px 10px; margin: 2px; border-radius: 20px; font-size: 12px; background-color: #a0d8ef; color: #000;">🚀 High hiring</span>
                @endif
              </div>

              <!-- Contact Details -->
              <div style="margin-top: 16px; background-color: #f5f9fc; padding: 16px; border-radius: 4px; font-size: 16px; line-height: 24px;">
                <strong>🏅</strong> {{ $credit_score }} credits<br>
                <strong>📍</strong> {{ $postcode }}<br>
                <strong>📞</strong> {{ $masked_phone }}<br>
                <strong>✉️</strong> {{ $masked_email }}<br>
              </div>

              <!-- CTA -->

            </td>
          </tr>

          <!-- Questions & Answers -->
          <tr>
            <td style="background: #ffffff; padding: 32px; border-radius: 4px; margin-top: 20px;">
              <div style="background-color: #d8edf8; color: #1a588c; padding: 12px 20px; margin: -32px -32px 20px -32px; border-top-left-radius: 4px; border-top-right-radius: 4px; font-weight: bold;">Details</div>
              @foreach ($questionsAndAnswers as $qa)
                <p style="margin: 8px 0;"><strong>{{ $qa['question'] }}</strong></p>
                <p style="margin: 0 0 12px;">{{ $qa['answer'] }}</p>
              @endforeach
            </td>
          </tr>

          <!-- Help Section -->
          <tr>
            <td style="background: #ffffff; padding: 32px; border-radius: 4px; margin-top: 20px;">
              <div style="background-color: #d8edf8; color: #1a588c; padding: 12px 20px; margin: -32px -32px 20px -32px; border-top-left-radius: 4px; border-top-right-radius: 4px; font-weight: bold;">Need Help?</div>
              <p style="color: #61696d;">
                 Email us at
                <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists')}}">{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists')}}</a>.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding: 20px; font-size: 12px; color: #666666;">
              Manage your email preferences <a href="{{ $baseUrl }}/e-mail-notification" style="color: #007bff;">here</a>.<br>
              {{ \App\Helpers\CustomHelper::setting_value('website_address','') }}
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
