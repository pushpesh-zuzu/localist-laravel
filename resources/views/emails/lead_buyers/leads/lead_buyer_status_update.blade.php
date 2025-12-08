<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width" />
  <title>Lead Purchase Status Update</title>
  <style>
    body { margin:0; background-color:#f1f2f4; font-family:"Lato", Helvetica, Arial, sans-serif; font-size:15px; line-height:22px; color:#4a4a4a; -webkit-font-smoothing:antialiased }
    .email-wrap { width:100%; background-color:#f1f2f4; padding:32px 0 }
    .email-container { max-width:600px; margin:0 auto; padding:0 16px }
    .logo { max-height:50px; display:block; margin:0 auto 20px auto }
    h1 { font-size:22px; font-weight:600; color:#333; margin:0 0 10px 0; font-family:Helvetica, Arial, sans-serif }
    p { margin:0 0 12px 0; font-size:15px; color:#61696d }
    .btn { display:inline-block; background:#3399ff; color:#fff !important; text-decoration:none; font-size:14px; font-weight:bold; padding:12px 20px; border-radius:4px }
    .highlight { color:#00afe3; margin-bottom:16px; font-size:15px }
    .tag { display:inline-block; padding:6px 10px; margin:4px 4px 0 0; border-radius:20px; font-size:12px; }
    .meta { background:#f5f9fc; padding:16px; border-radius:4px; font-size:15px; margin:16px 0 }
    .section-header { background:#d8edf8; color:#1a588c; font-weight:600; padding:12px 20px; font-size:16px }
    .card { background:#fff; border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden }
    .footer { text-align:center; padding:20px; font-size:13px; color:#666 }
    @media only screen and (max-width:600px) {
      .email-container { width:100% !important; padding:0 12px !important }
      .btn { display:block !important; width:100% !important; box-sizing:border-box; font-size:16px !important; text-align:center }
      h1 { font-size:20px !important }
    }
  </style>
</head>
<body>
  <table class="email-wrap" width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
      <td align="center">

        <table class="email-container" width="600" cellpadding="0" cellspacing="0" role="presentation">
          <!-- Logo -->
          <tr>
            <td style="text-align:center; padding:0 0 16px 0">
              <img src="{{ $baseUrl }}/assets/localist_logo_1.png" alt="Localists Logo" class="logo">
            </td>
          </tr>

          <!-- Main Card -->
          <tr>
            <td>
              <table width="100%" cellpadding="0" cellspacing="0" bgcolor="#ffffff" class="card">
                <tr>
                  <td style="padding:20px">
                    
                    <p style="text-align:center;font-size: 18px;">Hi <strong style="color: #333333;">{{ ucfirst($name) }}</strong>,</p>
                    
                    <div class="highlight">You've purchased a <strong>{{ $service }}</strong> lead. Please update its status below:</div>

                    <!-- Tags -->
                    <div style="margin:12px 0;">
                      @if($phone_verified)
                        <span class="tag" style="background:#f39ac3;color:#fff;">📞 Verified Phone</span>
                      @endif
                      @if($has_additional_details)
                        <span class="tag" style="background:#e6e6e6;color:#333;">📋 Additional details</span>
                      @endif
                      @if($is_frequent_user)
                        <span class="tag" style="background:#a0d8ef;color:#000;">🔁 Frequent user</span>
                      @endif
                      @if($is_urgent)
                        <span class="tag" style="background:#ffd9a6;color:#000;">⏰ Urgent</span>
                      @endif
                      @if($is_high_hiring)
                        <span class="tag" style="background:#d1f7d9;color:#000;">🚀 High hiring</span>
                      @endif
                    </div>

                    <!-- Contact Info -->
                    <div class="meta">
                      <div><strong>🏅</strong> {{ $credit_score }} credits</div>
                      <div><strong>📍</strong> {{ $postcode }}</div>
                      <div><strong>📞</strong> {{ $phone }}</div>
                      <div><strong>✉️</strong> {{ $email }}</div>
                    </div>

                    <!-- Status Update Buttons -->
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td align="center" style="padding:6px 0;">
      <table cellpadding="0" cellspacing="0" border="0" align="center">
        <tr>
          <td style="border-radius:4px; background:#007bff; text-align:center;">
            <a href="{{ url('/api/lead-purchase-status-update-log/' . $lead_id . '/' . $seller_id . '/' . $buyer_id . '/Attempted Contact (no response)') }}"
               style="display:inline-block; width:280px; padding:12px 0; font-size:14px; color:#ffffff; text-decoration:none; border-radius:4px; font-family:Arial, sans-serif; text-align:center;">
               Attempted Contact (no response)
            </a>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <tr>
    <td align="center" style="padding:6px 0;">
      <table cellpadding="0" cellspacing="0" border="0" align="center">
        <tr>
          <td style="border-radius:4px; background:#007bff; text-align:center;">
            <a href="{{ url('/api/lead-purchase-status-update-log/' . $lead_id . '/' . $seller_id . '/' . $buyer_id . '/Contact made with the Buyer (in process of providing  quotes)') }}"
               style="display:inline-block; width:280px; padding:12px 0; font-size:14px; color:#ffffff; text-decoration:none; border-radius:4px; font-family:Arial, sans-serif; text-align:center;">
               Contact made (in process of providing quotes)
            </a>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <tr>
    <td align="center" style="padding:6px 0;">
      <table cellpadding="0" cellspacing="0" border="0" align="center">
        <tr>
          <td style="border-radius:4px; background:#007bff; text-align:center;">
            <a href="{{ url('/api/lead-purchase-status-update-log/' . $lead_id . '/' . $seller_id . '/' . $buyer_id . '/Contact made with the Buyer (Quote Provided)') }}"
               style="display:inline-block; width:280px; padding:12px 0; font-size:14px; color:#ffffff; text-decoration:none; border-radius:4px; font-family:Arial, sans-serif; text-align:center;">
               Contact made — Quote Provided
            </a>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <tr>
    <td align="center" style="padding:6px 0;">
      <table cellpadding="0" cellspacing="0" border="0" align="center">
        <tr>
          <td style="border-radius:4px; background:#007bff; text-align:center;">
            <a href="{{ url('/api/lead-purchase-status-update-log/' . $lead_id . '/' . $seller_id . '/' . $buyer_id . '/Contact made with Buyer & Quote Provided (no hire)') }}"
               style="display:inline-block; width:280px; padding:12px 0; font-size:14px; color:#ffffff; text-decoration:none; border-radius:4px; font-family:Arial, sans-serif; text-align:center;">
               Contact made & Quote Provided (no hire)
            </a>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <tr>
    <td align="center" style="padding:6px 0;">
      <table cellpadding="0" cellspacing="0" border="0" align="center">
        <tr>
          <td style="border-radius:4px; background:#007bff; text-align:center;">
            <a href="{{ url('/api/lead-purchase-status-update-log/' . $lead_id . '/' . $seller_id . '/' . $buyer_id . '/Contact made with Buyer & Quote Provided (hired)') }}"
               style="display:inline-block; width:280px; padding:12px 0; font-size:14px; color:#ffffff; text-decoration:none; border-radius:4px; font-family:Arial, sans-serif; text-align:center;">
               Contact made & Quote Provided (hired)
            </a>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>


                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr><td style="height:18px; font-size:0; line-height:18px">&nbsp;</td></tr>

          <!-- Q&A Section -->
          <tr>
            <td>
              <table width="100%" cellpadding="0" cellspacing="0" bgcolor="#ffffff" class="card">
                <tr>
                  <td class="section-header">Details</td>
                </tr>
                <tr>
                  <td style="padding:20px; font-size:15px; color:#4a4a4a;">
                    @if(!empty($questionsAndAnswers))
                      @foreach ($questionsAndAnswers as $qa)
                        <p style="margin:8px 0 4px"><strong>{{ $qa['question'] }}</strong></p>
                        <p style="margin:0 0 12px">{{ $qa['answer'] }}</p>
                      @endforeach
                    @else
                      <p style="color:#61696d; margin:0;">No additional details provided.</p>
                    @endif
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr><td style="height:18px; font-size:0; line-height:18px">&nbsp;</td></tr>

          <!-- Help Section -->
          <tr>
            <td>
              <table width="100%" cellpadding="0" cellspacing="0" bgcolor="#ffffff" class="card">
                <tr>
                  <td class="section-header">Need Help?</td>
                </tr>
                <tr>
                  <td style="padding:20px; font-size:15px; color:#61696d;">
                    Email us at
                    <a href="mailto:{{ \App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com') }}" style="color:#007bff;">
                      {{ \App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com') }}
                    </a>.
                    <p>Kind Regards,<br>Localists Team</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr><td style="height:20px; font-size:0; line-height:20px">&nbsp;</td></tr>

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
