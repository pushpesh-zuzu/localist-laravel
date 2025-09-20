<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Exciting Job Opportunities in Your Area</title>
  <style>
    body { margin: 0; padding: 0; background-color: #ffffff; font-family: 'Lato', Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px; color: #4a4a4a; -webkit-font-smoothing:antialiased; }
    .outer { width: 100%; background-color: #ffffff; padding: 32px 0; -webkit-text-size-adjust: none; -ms-text-size-adjust: none; }
    .container { max-width: 600px; margin: 0 auto; padding: 0 16px; box-sizing: border-box; }
    img.logo { max-height: 50px; display:block; margin: 0 auto; }
    .card { background: #ffffff; padding: 28px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    h1 { font-size: 22px; font-weight: 600; color: #333333; margin: 0 0 8px; text-align:center; }
    p.lead-intro { color: #61696d; margin: 10px 0 0 0; text-align:center; }
    .stat-block { margin-top: 16px; background-color: #f5f9fc; padding: 16px; border-radius: 4px; font-size: 15px; color:#333; }
    .stat-block strong { font-weight:600; }
    .btn { display:block; background-color:#00afe3; color:#ffffff !important; text-decoration:none; font-size:16px; font-weight:700; padding:14px; border-radius:4px; text-align:center; margin-top:20px; }
    .help-card { background:#ffffff; padding:28px; border-radius:4px; margin-top:20px; }
    .help-header { background-color: #d8edf8; color: #1a588c; padding: 12px 20px; margin: -28px -28px 20px -28px; border-top-left-radius: 4px; border-top-right-radius: 4px; font-weight:700; }
    .footer { text-align:center; padding: 20px; font-size:12px; color:#666666; }
    @media only screen and (max-width: 600px) {
      h1 { font-size:20px; }
      .btn { font-size:16px; padding:12px; }
      .card, .help-card { padding:18px; }
    }
  </style>
</head>
<body>
  <table role="presentation" class="outer" width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td align="center">
        <table role="presentation" class="container" width="100%" cellpadding="0" cellspacing="0">
          <!-- Logo -->
          <tr>
            <td align="center" style="padding-bottom:20px;">
              <img src="{{ $baseUrl }}/assets/localist_logo.png" alt="Localists" class="logo">
            </td>
          </tr>

          <!-- Main Teaser Card -->
          <tr>
            <td>
              <div class="card">
                <h1>Hi {{ $name }}</h1>
                <p class="lead-intro">New jobs are waiting for you!</p>

                @if($credit_purchase ?? false)
                  <p style="text-align:center; color:#61696d; margin-top:12px;">
                    You haven't purchased any credit pack for 5 days and your balance is low. There are <strong>{{ $total_count }}</strong> jobs matching your preferences — but you can’t bid on them.
                  </p>
                @else
                  <p style="text-align:center; color:#61696d; margin-top:12px;">
                    You’ve missed out on <strong>{{ $total_count }}</strong> potential jobs for your services with an average value of £ {{ $total_credt_sum }} in the last 7 days.
                  </p>
                @endif

                <!-- Per-service stats -->
                @foreach ($leadDataList as $lead)

                  <div class="stat-block" role="listitem" aria-label="Service summary">
                    <div style="margin-bottom:6px;"><strong>📊 Total Jobs:</strong> {{ $lead['count'] }}</div>
                    <div style="margin-top:4px;"><strong>💼 {{ $lead['category_name'] }}:</strong> £{{ number_format($lead['credit_sum'] ?? 0, 0) }}</div>

                    {{-- optional area display --}}
                    {{-- <div style="margin-top:6px; color:#61696d;"><strong>📍 Location:</strong> {{ ucfirst($lead['area'] ?? '') }}</div> --}}
                  </div>


                @endforeach

                <!-- CTA -->
                @if ($credit_purchase ?? false)
                  <a href="{{ $baseUrl }}/settings/billing/my-credits" class="btn">Buy Credits Now to Start Bidding</a>
                @else
                  <a href="{{ $baseUrl }}/sellers/leads" class="btn">Check Your Leads Now</a>
                @endif
              </div>
            </td>
          </tr>

          <!-- Help Section -->
          <tr>
            <td>
              <div class="help-card">
                <div class="help-header">Need Help?</div>
                <p style="color:#61696d; margin:0;">
                  Email us at
                  <a href="mailto:{{ \App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com') }}" style="color:#007bff;">
                    {{ \App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com') }}
                  </a>.
                </p>
              </div>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td class="footer">
              Manage your email preferences <a href="{{ $baseUrl }}/settings/notifications/e-mail-notification" style="color:#007bff;">here</a>.<br>
              {{ \App\Helpers\CustomHelper::setting_value('website_address','') }}
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
