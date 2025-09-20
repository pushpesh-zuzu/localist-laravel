<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Lead Purchase Status Update</title>
  <style>
    body { margin: 0; padding: 0; background-color: #ffffff; font-family: 'Lato', Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px; color: #4a4a4a; -webkit-font-smoothing:antialiased; }
    .outer { width: 100%; background-color: #ffffff; padding: 32px 0; -webkit-text-size-adjust: none; -ms-text-size-adjust: none; }
    .container { max-width: 600px; margin: 0 auto; padding: 0 16px; box-sizing: border-box; }
    img.logo { max-height: 50px; display:block; margin: 0 auto; }
    .card { background: #ffffff; padding: 28px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    h1 { font-size: 22px; font-weight: 600; color: #333333; margin: 0 0 8px; text-align:center; }
    .subtitle { color:#61696d; text-align:center; margin: 8px 0 12px; }
    .tag { display:inline-block; padding:6px 10px; margin:6px 6px 6px 0; border-radius:20px; font-size:12px; color:#fff; }
    .tag-muted { background:#e6e6e6; color:#333; }
    .meta { margin-top: 16px; background-color: #f5f9fc; padding: 16px; border-radius: 4px; font-size: 15px; color:#333; line-height:22px; }
    .action-btn { display:block; background-color:#00afe3; color:#ffffff !important; text-decoration:none; font-size:15px; font-weight:700; padding:12px; border-radius:4px; text-align:center; margin-top:12px; }
    .help-card { background:#ffffff; padding:28px; border-radius:4px; margin-top:20px; }
    .help-header { background-color: #d8edf8; color: #1a588c; padding: 12px 20px; margin: -28px -28px 20px -28px; border-top-left-radius: 4px; border-top-right-radius: 4px; font-weight:700; }
    .qa { margin: 8px 0 12px; color:#4a4a4a; }
    .footer { text-align:center; padding: 20px; font-size:12px; color:#666666; }
    @media only screen and (max-width: 600px) {
      h1 { font-size:20px; }
      .card, .help-card { padding:18px; }
      .action-btn { font-size:16px; padding:12px; }
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

          <!-- Main Card -->
          <tr>
            <td>
              <div class="card">
                <h1>Hi {{ $name }}</h1>
                <p class="subtitle">You've purchased a <strong>{{ $service }}</strong> lead — please update its status using one of the options below.</p>

                <!-- Tags -->
                <div style="text-align:center; margin: 8px 0 12px;">
                  @if($phone_verified)
                    <span class="tag" style="background:#f39ac3;">📞 Verified Phone</span>
                  @endif
                  @if($has_additional_details)
                    <span class="tag tag-muted" style="background:#e6e6e6; color:#333;">📋 Additional details</span>
                  @endif
                  @if($is_frequent_user)
                    <span class="tag" style="background:#a0d8ef; color:#000;">🔁 Frequent user</span>
                  @endif
                  @if($is_urgent)
                    <span class="tag" style="background:#ffd9a6; color:#000;">⏰ Urgent</span>
                  @endif
                  @if($is_high_hiring)
                    <span class="tag" style="background:#d1f7d9; color:#000;">🚀 High hiring</span>
                  @endif
                </div>

                <!-- Contact Details -->
                <div class="meta">
                  <div><strong>🏅</strong> {{ $credit_score }} credits</div>
                  <div><strong>📍</strong> {{ $postcode }}</div>
                  <div><strong>📞</strong> {{ $phone }}</div>
                  <div><strong>✉️</strong> {{ $email }}</div>
                </div>

                <!-- Action Buttons (status update) -->
                <a href="{{ url('/api/lead-purchase-status-update-log/' . $lead_id . '/' . $seller_id . '/' . $buyer_id . '/Attempted Contact (no response)') }}" class="action-btn">Attempted Contact (no response)</a>

                <a href="{{ url('/api/lead-purchase-status-update-log/' . $lead_id . '/' . $seller_id . '/' . $buyer_id . '/Contact made with the Buyer (in process of providing  quotes)') }}" class="action-btn">Contact made (in process of providing quotes)</a>

                <a href="{{ url('/api/lead-purchase-status-update-log/' . $lead_id . '/' . $seller_id . '/' . $buyer_id . '/Contact made with the Buyer (Quote Provided)') }}" class="action-btn">Contact made — Quote Provided</a>

                <a href="{{ url('/api/lead-purchase-status-update-log/' . $lead_id . '/' . $seller_id . '/' . $buyer_id . '/Contact made with Buyer & Quote Provided (no hire)') }}" class="action-btn">Contact made & Quote Provided (no hire)</a>

                <a href="{{ url('/api/lead-purchase-status-update-log/' . $lead_id . '/' . $seller_id . '/' . $buyer_id . '/Contact made with Buyer & Quote Provided (hired)') }}" class="action-btn">Contact made & Quote Provided (hired)</a>

              </div>
            </td>
          </tr>

          <!-- Questions & Answers -->
          <tr>
            <td>
              <div class="help-card">
                <div class="help-header">Details</div>

                @if(!empty($questionsAndAnswers))
                  <div style="padding:6px 0 0 0;">
                    @foreach ($questionsAndAnswers as $qa)
                      <div class="qa"><strong>{{ $qa['question'] }}</strong></div>
                      <div style="margin:0 0 12px 0;">{{ $qa['answer'] }}</div>
                    @endforeach
                  </div>
                @else
                  <p style="color:#61696d; margin:0;">No additional details provided.</p>
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
