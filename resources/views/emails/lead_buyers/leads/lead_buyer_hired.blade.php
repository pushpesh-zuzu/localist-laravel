<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>You've Hired a Lead</title>
  <style>
    body { margin: 0; background-color: #f1f2f4; font-family: "Lato", Helvetica, Arial, sans-serif; font-size: 15px; line-height: 24px; color: #4a4a4a; -webkit-font-smoothing: antialiased; }
    .email-wrap { width: 100%; background-color: #f1f2f4; padding: 32px 0; }
    .email-container { max-width: 600px; margin: 0 auto; padding: 0 16px; box-sizing: border-box; }
    .logo { max-height: 50px; display: block; margin: 0 auto 20px auto; max-width:100%; height:auto; }
    .btn { display: inline-block; background-color: #00afe3; color: #ffffff !important; text-decoration: none; font-size: 14px; font-weight: 700; padding: 10px 18px; border-radius: 4px; }
    h1 { font-size: 22px; font-weight: 600; color: #333333; margin: 0 0 8px 0; font-family: Helvetica, Arial, sans-serif; }
    .highlight { color: #00afe3; margin-bottom: 12px; font-size: 15px; font-family: Helvetica, Arial, sans-serif; text-align:center; }
    p { color: #61696d; margin: 0 0 12px 0; font-family: Helvetica, Arial, sans-serif; }
    a { color: #007bff; }
    .card { background: #ffffff; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 20px; margin-bottom: 18px; }
    .section-header { background-color: #d8edf8; color: #1a588c; padding: 12px 16px; font-weight: 600; border-top-left-radius: 4px; border-top-right-radius: 4px; }
    .muted { color: #9aa1a6; }
    .lead-meta { margin-top: 12px; background-color: #f5f9fc; padding: 12px; border-radius: 4px; font-size: 15px; }
    .tag { display:inline-block; padding:5px 10px; margin:4px 4px 4px 0; border-radius:20px; font-size:12px; }
    del { color: #9aa1a6; margin-right:6px; }
    @media only screen and (max-width: 600px) {
      .email-container { width: 100% !important; padding: 0 12px !important; }
      .btn { font-size: 16px !important; padding: 12px 20px !important; display:block; width:100%; box-sizing:border-box; text-align:center; }
      h1 { font-size: 20px !important; }
    }
  </style>
</head>
<body>
  <table width="100%" cellpadding="0" cellspacing="0" border="0" class="email-wrap">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" border="0" class="email-container">

          <!-- Logo -->
          <tr>
            <td style="padding:0 16px 8px 16px; text-align:center">
              <img src="{{ $baseUrl }}/assets/localist_logo.png" alt="Localists Logo" class="logo">
            </td>
          </tr>

          <!-- Main Card -->
          <tr>
            <td>
              <div class="card">
                <h1 style="text-align:center">Hi {{ $name }}</h1>
                <div class="highlight">You have purchased a new lead</div>

                <p style="margin-bottom:12px;color:#61696d;">Congratulations — you've successfully hired the <strong>{{ $service_name }}</strong> lead.</p>

                <!-- tags -->
                <div style="text-align:center; margin-bottom:12px;">
                  @if($phone_verified)
                    <span class="tag" style="background-color:#f39ac3; color:#fff">📞 Verified Phone</span>
                  @endif
                  @if($has_additional_details)
                    <span class="tag" style="background-color:#e6e6e6; color:#333">📋 Additional details</span>
                  @endif
                  @if($is_frequent_user)
                    <span class="tag" style="background-color:#a0d8ef; color:#000">🔁 Frequent user</span>
                  @endif
                  @if($is_urgent)
                    <span class="tag" style="background-color:#ffd9a6; color:#000">⏰ Urgent</span>
                  @endif
                  @if($is_high_hiring)
                    <span class="tag" style="background-color:#d1f7d9; color:#000">🚀 High hiring</span>
                  @endif
                </div>

                <!-- Contact / Purchase Details -->
                <div class="lead-meta">
                  <div style="margin-bottom:8px"><strong>🏅</strong> {{ $credit_score }} credits</div>
                  <div style="margin-bottom:6px"><strong>📍</strong> {{ $postcode }}</div>
                  <div style="margin-bottom:6px"><strong>📞</strong> {{ $phone }}</div>
                  <div><strong>✉️</strong> {{ $email }}</div>
                </div>

                <div style="margin-top:16px; text-align:center">
                  <a href="{{ $baseUrl }}/sellers/leads/my-responses" class="btn">Contact Lead Now</a>
                </div>

              </div>
            </td>
          </tr>

          <!-- Questions & Answers -->
          <tr>
            <td>
              <div class="card">
                <div class="section-header">Details</div>
                <div style="padding:16px">
                  @if(!empty($questionsAndAnswers))
                    @foreach ($questionsAndAnswers as $qa)
                      <p style="margin:8px 0 4px 0"><strong>{{ $qa['question'] }}</strong></p>
                      <p style="margin:0 0 12px 0">{{ $qa['answer'] }}</p>
                    @endforeach
                  @else
                    <p class="muted">No additional details provided.</p>
                  @endif
                </div>
              </div>
            </td>
          </tr>

          <!-- Need Help -->
          <tr>
            <td>
              <div class="card">
                <div class="section-header">Need Help?</div>
                <div style="padding:16px">
                  <p class="muted">Email us at <a href="mailto:{{ \App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com') }}">{{ \App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com') }}</a>.</p>
                </div>
              </div>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding: 20px; font-size: 13px; color: #666666;">
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
