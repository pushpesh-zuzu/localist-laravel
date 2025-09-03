<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Exciting Job Opportunities in Your Area</title>
</head>
<body style="margin: 0; padding: 0; background-color: #ffffff; font-family: 'Lato', Helvetica, Arial, sans-serif; font-size: 18px; font-weight: 510; line-height: 25px; color: #4a4a4a;">

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #ffffff; padding: 32px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" style="max-width: 600px; padding: 0 16px; box-sizing: border-box;">

          <!-- Logo -->
          <tr>
            <td align="center" style="padding-bottom: 20px;">
              <img src="{{ $baseUrl }}/assets/localist_logo.png" alt="Localists Logo" style="max-height: 50px;">
            </td>
          </tr>

          <!-- Teaser Card -->
          <tr>
            <td style="background: #ffffff; padding: 32px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);">
              <h1 style="font-size: 22px; font-weight: 600; color: #333333; margin: 0 0 10px;">{{ $name }}, new jobs are waiting for you!</h1>
              @if($credit_purchase)
                <p style="color: #61696d;">You haven't purchased any credit pack for  5 days, and your current balance is below 10. There are <strong>{{ $total_count }}</strong> jobs matching your preferences waiting — but you can’t bid on them!</p>
                @else
                <p style="color: #61696d;">You’ve missed out on <strong>{{ $total_count }}</strong> potential jobs  for your  all service  with an average credit value of £ {{ $total_credt_sum }} in last 7 days</p>
              @endif
              <!-- Stats -->
              @foreach ($leadDataList as $lead)
              <div style="margin-top: 16px; background-color: #f5f9fc; padding: 16px; border-radius: 4px; font-size: 16px; line-height: 24px;">
                <strong>📊 Total Jobs:</strong> {{ $lead['count'] }}<br>
                @if(!$credit_value)
                    <strong>💼 Job Value:</strong> £{{ number_format($lead['credit_sum'] / max($lead['count'], 1), 2) }}<br>
                @endif
                <strong>📍 Location:</strong> {{ ucfirst($lead['area']) }}<br>
                <strong>🛠 Service:</strong> {{ $lead['category_name'] }}
              </div>
              @endforeach

              <!-- CTA -->
              @if ($credit_purchase)
                  <a href="{{ $baseUrl }}/mycredits" style="display: block; background-color: #00afe3; color: #ffffff !important; text-decoration: none; font-size: 16px; font-weight: bold; padding: 14px; border-radius: 4px; margin-top: 20px; text-align: center;">Buy Credits Now to Start Bidding</a>
              @else
                  <a href="{{ $baseUrl }}/leads" style="display: block; background-color: #00afe3; color: #ffffff !important; text-decoration: none; font-size: 16px; font-weight: bold; padding: 14px; border-radius: 4px; margin-top: 20px; text-align: center;">Check Your Leads Now </a>
              @endif

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
