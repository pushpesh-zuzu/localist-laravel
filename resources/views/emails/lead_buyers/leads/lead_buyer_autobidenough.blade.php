<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>New Leads Matched for You</title>
  <style>
    body { margin:0; padding:0; background-color:#f1f2f4; font-family:'Lato', Helvetica, Arial, sans-serif; color:#4a4a4a; -webkit-font-smoothing:antialiased; }
    img { border:0; display:block; max-width:100%; }
    a { color:#007bff; }

    .btn {
      display:inline-block;
      background-color:#00afe3;
      color:#ffffff !important;
      text-decoration:none;
      font-size:15px;
      font-weight:700;
      padding:10px 16px;
      border-radius:4px;
      -webkit-text-size-adjust:none;
    }

    h1 { font-size:20px; font-weight:600; color:#333; margin:0; font-family:Helvetica, Arial, sans-serif; text-align:center; line-height:26px; }
    p { margin:0 0 10px 0; font-size:15px; line-height:1.4; color:#61696d; font-family:Helvetica, Arial, sans-serif; }
    .tag { display:inline-block; padding:4px 8px; margin:2px; border-radius:16px; font-size:12px; }

    @media only screen and (max-width:600px) {
      .email-container { width:100% !important; padding:0 12px !important; }
      .card-td { padding:16px !important; }
      .btn { font-size:14px !important; padding:10px 14px !important; }
      h1 { font-size:18px !important; }
    }
  </style>
</head>
<body>
  <!-- Outer wrapper -->
  <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f1f2f4" role="presentation">
    <tr>
      <td align="center">

        <!-- Container -->
        <table width="600" cellpadding="0" cellspacing="0" border="0" class="email-container" role="presentation" style="max-width:600px; width:100%;">

          <!-- Logo -->
          <tr>
            <td style="padding:16px; text-align:center;">
              <img src="{{ $baseUrl }}/assets/localist_logo.png" alt="Localists Logo" style="max-height:45px; margin:0 auto;">
            </td>
          </tr>

          <!-- Header -->
          <tr>
            <td style="padding-bottom:12px; text-align:center;">
              <h1>Hi {{ $name }}</h1>
              <div style="font-size:14px; color:#61696d; margin-top:6px;">You've got new leads!</div>
            </td>
          </tr>

          <!-- Leads Loop -->
          @foreach($leadDetailsList as $lead)
          <tr>
            <td style="padding:10px 16px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" role="presentation" style="border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden;">
                <tr>
                  <td class="card-td" style="padding:16px; font-family:Helvetica, Arial, sans-serif; color:#4a4a4a;">

                    <p style="margin:0 0 8px 0; color:#333; font-size:15px;">
                      <strong>{{ $lead['lead_name'] }}</strong> is looking for <strong>{{ $lead['service_name'] }}</strong>.
                    </p>

                    <!-- Tags -->
                    <div style="margin:8px 0;">
                      @if($lead['phone_verified'])
                        <span class="tag" style="background-color:#f39ac3; color:#fff;">📞 Verified Phone</span>
                      @endif
                      @if($lead['has_additional_details'])
                        <span class="tag" style="background-color:#ccc; color:#333;">📋 Additional details</span>
                      @endif
                      @if($lead['is_frequent_user'])
                        <span class="tag" style="background-color:#a0d8ef; color:#000;">🔁 Frequent user</span>
                      @endif
                      @if($lead['is_urgent'])
                        <span class="tag" style="background-color:#ffa07a; color:#000;">⏰ Urgent</span>
                      @endif
                      @if($lead['is_high_hiring'])
                        <span class="tag" style="background-color:#90ee90; color:#000;">🚀 High hiring</span>
                      @endif
                    </div>

                    <!-- Contact Info -->
                    <div style="margin-top:10px; background-color:#f5f9fc; padding:12px; border-radius:4px; font-size:14px; line-height:20px; color:#333;">
                      <div style="margin-bottom:4px;"><strong>🏅</strong> <span style="margin-left:6px;">{{ $lead['credit_score'] }} credits deduct for this lead</span></div>
                      <div style="margin-bottom:4px;"><strong>💸</strong> <span style="margin-left:6px;">{{ $lead['remaining_credit'] }} credits will remain after purchase</span></div>
                      <div style="margin-bottom:4px;"><strong>📍</strong> <span style="margin-left:6px;">{{ $lead['postcode'] }}</span></div>
                      <div style="margin-bottom:4px;"><strong>📞</strong> <span style="margin-left:6px;">{{ $lead['masked_phone'] }}</span></div>
                      <div><strong>✉️</strong> <span style="margin-left:6px;">{{ $lead['masked_email'] }}</span></div>
                    </div>

                    <!-- CTA -->
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="margin-top:12px;">
                      <tr>
                        <td align="center" style="padding:0 12px;">
                          @if($lead['hasEnoughCredits'])
                            <a href="{{ $baseUrl }}/sellers/leads/save-for-later" class="btn">Contact Lead Now</a>
                          @else
                            <a href="{{ $baseUrl }}/settings/billing/my-credits" class="btn">Top Up Credits to Contact</a>
                          @endif
                        </td>
                      </tr>
                    </table>

                    <!-- Q&A Section -->
                    @if(!empty($lead['questionsAndAnswers']))
                    <div style="margin-top:12px;">
                      <div style="background:#d8edf8; color:#1a588c; padding:10px 12px; border-radius:4px; font-weight:600; font-size:14px;">Details</div>
                      <div style="padding-top:8px;">
                        @foreach ($lead['questionsAndAnswers'] as $qa)
                          <div style="margin:8px 0 4px 0; font-weight:600; font-size:14px;">{{ $qa['question'] }}</div>
                          <div style="margin:0 0 10px 0; color:#61696d;">{{ $qa['answer'] }}</div>
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

          <!-- Help Section -->
          <tr>
            <td style="padding:10px 16px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" role="presentation" style="border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden;">
                <tr>
                  <td bgcolor="#d8edf8" style="padding:10px 16px; color:#1a588c; font-weight:500; font-family:Helvetica, Arial, sans-serif;">
                    Need Help?
                  </td>
                </tr>
                <tr>
                  <td style="padding:14px; font-family:Helvetica, Arial, sans-serif; color:#61696d;">
                    <p style="margin:0;">Email us at <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}">{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}</a>.</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding:14px; font-size:12px; color:#666; font-family:Helvetica, Arial, sans-serif;">
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
