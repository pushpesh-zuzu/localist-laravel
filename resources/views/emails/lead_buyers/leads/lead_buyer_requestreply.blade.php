<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>New Leads Matched for You</title>
  <style>
    body { margin: 0; background-color: #f1f2f4; font-family: "Lato", Helvetica, Arial, sans-serif; font-size: 15px; line-height: 24px; color: #4a4a4a; -webkit-font-smoothing: antialiased }
    .email-wrap { width: 100%; background-color: #f1f2f4; padding: 32px 0 }
    .email-container { max-width: 600px; margin: 0 auto; padding: 0 16px; box-sizing: border-box }
    .logo { max-height: 50px; display: block; margin: 0 auto 20px auto }
    .btn { display: inline-block; background-color: #3399ff; color: #ffffff !important; text-decoration: none; font-size: 14px; font-weight: 700; padding: 10px 18px; border-radius: 4px }
    h1 { font-size: 22px; font-weight: 600; color: #333333; margin: 0 0 8px 0; font-family: Helvetica, Arial, sans-serif }
    .highlight { color: #00afe3; margin-bottom: 12px; font-size: 15px; font-family: Helvetica, Arial, sans-serif }
    p { color: #61696d; margin: 0 0 12px 0; font-family: Helvetica, Arial, sans-serif }
    a { color: #007bff }
    .card { background: #ffffff; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 20px; margin-bottom: 18px }
    .section-header { background-color: #d8edf8; color: #1a588c; padding: 12px 16px; font-weight: 600; border-top-left-radius: 4px; border-top-right-radius: 4px }
    .muted { color: #9aa1a6 }
    .lead-meta { margin-top: 12px; background-color: #f5f9fc; padding: 12px; border-radius: 4px; font-size: 15px }
    .tag { display:inline-block; padding:5px 10px; margin:4px 4px 4px 0; border-radius:20px; font-size:12px }
    @media only screen and (max-width: 600px) {.email-container { width: 100% !important; padding: 0 12px !important } .btn { font-size: 16px !important; padding: 12px 20px !important } h1 { font-size: 20px !important } }
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

          <!-- Greeting -->
          <tr>
            <td>
              <div class="card" style="text-align:center">
                <h1>Welcome back, {{ $name }}</h1>
                <div class="highlight">You've received new lead request(s)</div>
                <p>We found leads that match your services and locations — check them below and respond quickly to secure the work.</p>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:8px">
                  <tr>
                    <td style="padding:0 16px; text-align:center">
                      <a href="{{ $baseUrl }}/sellers/leads" class="btn">View leads in dashboard</a>
                    </td>
                  </tr>
                </table>
              </div>
            </td>
          </tr>

          <!-- Loop through leads -->
          @foreach($leads as $lead)
            <tr>
              <td>
                <div class="card">
                  <p style="color:#61696d; margin-bottom:8px;"><strong>{{ $lead['lead_name'] }}</strong> requested a reply for <strong>{{ $lead['service_name'] }}</strong></p>

                  <!-- tags -->
                  <div style="margin-bottom:10px">
                    @if($lead['phone_verified'])<span class="tag" style="background-color:#f39ac3; color:#fff">📞 Verified Phone</span>@endif
                    @if($lead['has_additional_details'])<span class="tag" style="background-color:#e6e6e6; color:#333">📋 Additional details</span>@endif
                    @if($lead['is_frequent_user'])<span class="tag" style="background-color:#a0d8ef;">🔁 Frequent user</span>@endif
                    @if($lead['is_urgent'])<span class="tag" style="background-color:#ffd9a6;">⏰ Urgent</span>@endif
                    @if($lead['is_high_hiring'])<span class="tag" style="background-color:#d1f7d9;">🚀 High hiring</span>@endif
                  </div>

                  <!-- contact/details -->
                  <div class="lead-meta">
                    <div style="margin-bottom:6px"><strong>Credits:</strong> {{ $lead['credit_score'] }} used — <strong>{{ $lead['remaining_credit'] }} left</strong></div>
                    <div style="margin-bottom:6px"><strong>Location:</strong> {{ $lead['postcode'] }}</div>
                    <div style="margin-bottom:6px"><strong>Phone:</strong> {{ $lead['masked_phone'] }}</div>
                    <div><strong>Email:</strong> {{ $lead['masked_email'] }}</div>
                  </div>

                  <!-- CTA -->
                  <div style="margin-top:16px; text-align:center">
                    @if($lead['hasEnoughCredits'])
                      <a href="{{ $baseUrl }}/sellers/leads" class="btn">Contact {{ $lead['lead_name'] }} now</a>
                    @else
                      <a href="{{ $baseUrl }}/settings/billing/my-credits" class="btn">Add Credits to Contact</a>
                    @endif
                  </div>
                </div>
              </td>
            </tr>

            <!-- Questions & answers card -->
            <tr>
              <td>
                <div class="card">
                  <div class="section-header">Details</div>
                  <div style="padding:16px">
                    @foreach ($lead['questionsAndAnswers'] as $qa)
                      <p style="margin:8px 0 4px 0"><strong>{{ $qa['question'] }}</strong></p>
                      <p style="margin:0 0 12px 0">{{ $qa['answer'] }}</p>
                    @endforeach
                  </div>
                </div>
              </td>
            </tr>
          @endforeach

          <!-- How we work -->
          <tr>
            <td>
              <div class="card">
                <div class="section-header">How We Work</div>
                <div style="padding:16px">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:12px">
                    <tr>
                      <td style="vertical-align:top; padding-right:12px; width:44px">
                        <div style="background-color:#00adef; color:#fff; border-radius:50%; width:32px; height:32px; text-align:center; line-height:32px; font-size:14px">1</div>
                      </td>
                      <td>
                        <strong>Customers tell us what they need</strong>
                        <div class="muted">Local customers share the services they’re looking for by answering key questions relating to the service.</div>
                      </td>
                    </tr>
                  </table>

                  <hr style="border:none; border-bottom:1px solid #eee; margin:8px 0">

                  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:12px 0">
                    <tr>
                      <td style="vertical-align:top; padding-right:12px; width:44px">
                        <div style="background-color:#00adef; color:#fff; border-radius:50%; width:32px; height:32px; text-align:center; line-height:32px; font-size:14px">2</div>
                      </td>
                      <td>
                        <strong>Localists finds the right leads for you</strong>
                        <div class="muted">We match your business with leads that fit your services and location, delivered instantly to your inbox and dashboard.</div>
                      </td>
                    </tr>
                  </table>

                  <hr style="border:none; border-bottom:1px solid #eee; margin:8px 0">

                  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:12px 0">
                    <tr>
                      <td style="vertical-align:top; padding-right:12px; width:44px">
                        <div style="background-color:#00adef; color:#fff; border-radius:50%; width:32px; height:32px; text-align:center; line-height:32px; font-size:14px">3</div>
                      </td>
                      <td>
                        <strong>You review and select your leads</strong>
                        <div class="muted">See full customer details straight away and choose the opportunities that work best for your business.</div>
                      </td>
                    </tr>
                  </table>

                </div>
              </div>
            </td>
          </tr>

          <!-- Important Pages / Buy Credits -->
          <tr>
            <td>
              <div class="card">
                <div class="section-header">Important Pages</div>
                <div style="padding:16px">
                  <ol style="margin:0 0 12px 18px; padding:0">
                    <li><a href="{{ $baseUrl }}/sellers/dashboard">Dashboard</a></li>
                    <li><a href="{{ $baseUrl }}/sellers/leads">Leads</a></li>
                    <li><a href="{{ $baseUrl }}/settings/profile/my-profile">My Profile</a></li>
                    <li><a href="{{ $baseUrl }}/settings/leads/my-services">My Services</a></li>
                    <li><a href="{{ $baseUrl }}/settings/billing/my-credits">My Credits</a></li>
                    <li><a href="{{ $baseUrl }}/settings/billing/invoice-billing-details">Invoices &amp; Billing Details</a></li>
                  </ol>

                  <p>You currently have <strong>0 credits</strong> in your account. To start receiving quality leads and allow our system to auto-bid on your behalf please purchase credits now.</p>

                  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:8px">
                    <tr>
                      <td style="padding:0 16px">
                        <a href="{{ $baseUrl }}/settings/billing/my-credits" class="btn">Contact Lead Now</a>
                      </td>
                    </tr>
                  </table>

                  <p style="margin-top:12px">You can also call our customer support team at <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}">{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}</a>.</p>

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
                  <p class="muted">Email us at <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}">{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}</a>.</p>
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
