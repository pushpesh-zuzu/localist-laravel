<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width,initial-scale=1.0" name="viewport">
  <title>Recommended Pros</title>

  <style>
    body {
      margin: 0;
      padding: 0;
      background: #f4f6f8;
      font-family: Arial, Helvetica, sans-serif;
    }

    table { border-collapse: collapse !important; }

    @media only screen and (max-width:600px) {
      .email-container { width: 100% !important; padding: 0 12px !important; }
      .seller-card { padding: 14px !important; }
      .contact-btn { width: 100% !important; display: block !important; text-align: center !important; margin-top: 10px; }
    }
  </style>
</head>

<body>

  <table width="100%" bgcolor="#f4f6f8" cellpadding="0" cellspacing="0">
    <tr>
      <td align="center">

        <table width="600" class="email-container" style="background:#ffffff; margin-top:20px; border-radius:10px; overflow:hidden;">

          <!-- HEADER -->
          <tr>
            <td align="center" style="padding:25px 0; background:#ffffff;">
              <img src="{{ $baseUrl }}/assets/localist_logo_1.png"
                   alt="Localists Logo"
                   style="max-height:60px;">
            </td>
          </tr>

          <!-- WELCOME TEXT -->
          <tr>
            <td style="padding:25px 30px;">
              <p style="margin:0; font-size:20px; font-weight:bold; color:#00AEEF;">
                Hi {{ ucfirst($customerName) }},
              </p>

              <p style="margin-top:12px; font-size:15px; color:#333;">
                Here are the top recommended service providers for:
                <strong>{{ $serviceName }}</strong>
              </p>
            </td>
          </tr>

          <!-- SELLER LIST -->
          <tr>
            <td style="padding:0 20px 20px;">

              @foreach($sellers as $seller)
              <table width="100%" class="seller-card"
                    style="background:#ffffff; border-radius:10px; padding:16px; margin-bottom:14px; border:1px solid #e5e5e5; box-shadow:0 1px 3px rgba(0,0,0,0.06);">

                <tr>

                  <!-- Avatar -->
                  <td width="60" align="center" valign="top">

                    @if(!empty($seller->profile_image))
                      <img src="{{ $baseUrl }}/images/users/{{ $seller->profile_image }}"
                           style="width:52px; height:52px; border-radius:50%; object-fit:cover;">
                    @else
                      <div style="width:52px; height:52px; background:#e0e0e0;
                                  border-radius:50%; text-align:center; line-height:52px;
                                  font-size:20px; font-weight:bold; color:#555;">
                        {{ strtoupper(substr($seller->name, 0, 1)) }}
                      </div>
                    @endif

                  </td>

                  <!-- Seller Info -->
                  <td valign="top" style="padding-left:12px;">
                    <p style="margin:6px 0 0; font-size:15px; font-weight:bold; color:#333;">
                      {{ ucfirst($seller->name) }}
                    </p>

                    <p style="margin:4px 0; color:#777; font-size:13px;">
                      📍 {{ $seller->distance ?? 0 }} miles away
                    </p>
                  </td>

                  <!-- Button -->
                  <td align="right" valign="middle">
                    <a href="{{ $baseUrl }}/view-profile/{{ $leadId }}/{{ $seller->id }}"
                       class="contact-btn"
                       style="background:#00AEEF; color:#fff; padding:10px 18px;
                              border-radius:6px; font-size:13px; text-decoration:none;
                              font-weight:bold; display:inline-block;">
                      Contact Now
                    </a>
                  </td>

                </tr>
              </table>
              @endforeach

            </td>
          </tr>

          <!-- SUPPORT BOX -->
          <tr>
            <td style="padding:20px 25px;">
              <table width="100%" bgcolor="#e8f6ff" style="border-radius:8px;">
                <tr>
                  <td style="padding:18px;">
                    <p style="margin:0; font-size:14px; color:#333;">
                      You can also reach our support team at:
                      <strong>{{ \App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com') }}</strong>
                    </p>

                    <p style="margin-top:12px; font-size:14px; color:#333;">
                      Kind Regards,<br>
                      <strong>Localists Team</strong>
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>

</body>
</html>
