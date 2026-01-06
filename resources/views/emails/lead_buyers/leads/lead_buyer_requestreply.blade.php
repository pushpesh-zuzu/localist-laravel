<!doctype html>
<html>

<head>
  <meta charset="UTF-8">
  <title>New Leads Matched for You</title>
</head>

<body style="margin:0;padding:0;background:#f4f8fb;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td align="center" style="padding:30px 10px;">
        <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.08);">
          <tr>
            <td align="center" style="padding:20px;">
              <table width="100%">
                <tr>
                  <td bgcolor="#00AFE3" height="40" align="center" style="border-radius:5px;">
                    <img src="{{ $baseUrl }}/assets/localist_logo_1.png" height="26" alt="Localists">
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:14px 30px 0;font-family:Arial,Helvetica,sans-serif;">

              <!-- Welcome text -->
              <p style="font-size:16px;color:#253238;margin:0 0 4px;">
                Welcome back, <strong>{{ ucfirst($name) }}</strong>,
              </p>

              <!-- Lead received text -->
              <p style="color:#00AFE3;font-size:12px;font-weight:700;margin:10 0 10px;">
                You've received new lead request(s)
              </p>

              <!-- Description -->
              <p style="font-size:12px;color:#253238;line-height:18px;font-weight:500;margin:0 0 16px;">
                We found leads that match your services and locations — check them below and respond quickly to secure
                the work.
              </p>

              <!-- Button -->
              <div style="text-align:center;margin:18px 0 0;">
                <a href="{{ $baseUrl }}/sellers/leads" style="background:#ff9c2c;color:#ffffff; text-decoration:none;padding:8px 22px;border-radius:30px;font-weight:700;font-size:14px;display:inline-block;">
                  View leads in dashboard
                </a>
              </div>

            </td>
          </tr>


          <!-- BODY -->
          <tr>
            <td style="padding:5px 30px;">


              @foreach($leads as $lead)
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:20px;">
                <tr>
                  <td style="background:rgba(227, 246, 252, 0.5); border-radius:10px; padding:17px 25px;">
                    <p style="margin:0 0 16px;font-size:15px;font-weight:700;color:#253238;">
                      {{ $lead['lead_name'] }} requested a reply for <strong>{{ $lead['service_name'] }}</strong>
                    </p>
                    <!-- LEAD TAGS WITH IMAGES INSTEAD OF EMOJIS -->

                    <div style="margin-bottom:18px;">
                      @if($lead['phone_verified'])
                      <span style="display:inline-block;background:#00AFE3;color:#ffffff;font-size:12px;font-weight:600;padding:6px 10px;border-radius:14px;margin:0 6px 6px 0;">
                        <img src="{{$siteUrl}}/public/images/icons/phonetop.png" width="14" height="14" style="vertical-align:middle;margin-right:4px;">Verified Phone
                      </span>
                      @endif
                      @if($lead['has_additional_details'])
                      <span style="display:inline-block;background:#00AFE3;color:#ffffff;font-size:12px;font-weight:600;padding:6px 10px;border-radius:14px;margin:0 6px 6px 0;">
                        <img src="{{$siteUrl}}/public/images/icons/document.png" width="14" height="14"
                          style="vertical-align:middle;margin-right:4px;">Additional details
                      </span>
                      @endif
                      @if($lead['is_frequent_user'])
                      <span style="display:inline-block;background:#00AFE3;color:#ffffff;font-size:12px;font-weight:600;padding:6px 10px;border-radius:14px;margin:0 6px 6px 0;">
                        <img src="{{$siteUrl}}/public/images/icons/star.png" width="14" height="14" style="vertical-align:middle;margin-right:4px;">Frequent
                        user
                      </span>
                      @endif
                      @if($lead['is_urgent'])
                      <span style="display:inline-block;background:#00AFE3;color:#ffffff;font-size:12px;font-weight:600;padding:6px 10px;border-radius:14px;margin:0 6px 6px 0;">
                        <img src="{{$siteUrl}}/public/images/icons/warning.png" width="14" height="14" style="vertical-align:middle;margin-right:4px;">Urgent
                      </span>
                      @endif
                      @if($lead['is_high_hiring'])
                      <span
                        style="display:inline-block;background:#00AFE3;color:#ffffff;font-size:12px;font-weight:600;padding:6px 10px;border-radius:14px;margin:0 6px 6px 0;">
                        <img src="{{$siteUrl}}/public/images/icons/user-check.png" width="14" height="14" style="vertical-align:middle;margin-right:4px;">High
                        hiring
                      </span>
                      @endif
                    </div>






                    <!-- Credits White Box -->
                    <table width="100%" cellpadding="0" cellspacing="0"
                      style="background:#ffffff; border-radius:4px; margin-bottom:10px;">
                      <tr>
                        <td style="padding:8px 12px;">
                          <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                              <td width="30" valign="middle">
                                <img src="{{$siteUrl}}/public/images/icons/credits.png" width="26" height="26" alt="Credits">
                              </td>
                              <td valign="middle"
                                style="padding-left:6px; font-weight:600; font-size:14px; color:#253238;">
                                {{ $lead['credit_score'] }} used — <strong>{{ $lead['remaining_credit'] }} left
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>

                    <!-- Location White Box -->
                    <table width="100%" cellpadding="0" cellspacing="0"
                      style="background:#ffffff; border-radius:4px; margin-bottom:10px;">
                      <tr>
                        <td style="padding:8px 12px;">
                          <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                              <td width="30" valign="middle">
                                <img src="{{$siteUrl}}/public/images/icons/location.png" width="26" height="26" alt="Location">
                              </td>
                              <td valign="middle"
                                style="padding-left:6px; font-weight:600; font-size:14px; color:#253238;">
                                {{ $lead['postcode'] }}
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>

                    <!-- Phone White Box -->
                    <table width="100%" cellpadding="0" cellspacing="0"
                      style="background:#ffffff; border-radius:4px; margin-bottom:10px;">
                      <tr>
                        <td style="padding:8px 12px;">
                          <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                              <td width="30" valign="middle">
                                <img src="{{$siteUrl}}/public/images/icons/phone.png" width="26" height="26" alt="Phone">
                              </td>
                              <td valign="middle"
                                style="padding-left:6px; font-weight:600; font-size:14px; color:#253238;">
                                {{ $lead['masked_phone'] }}
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>

                    <!-- Email White Box -->
                    <table width="100%" cellpadding="0" cellspacing="0"
                      style="background:#ffffff; border-radius:4px; margin-bottom:20px;">
                      <tr>
                        <td style="padding:8px 12px;">
                          <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                              <td width="30" valign="middle">
                                <img src="{{$siteUrl}}/public/images/icons/email.png" width="26" height="26" alt="Email">
                              </td>
                              <td valign="middle"
                                style="padding-left:6px; font-weight:600; font-size:14px; color:#253238;">
                                {{ $lead['masked_email'] }}
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>

                    <!-- CTA BUTTON INSIDE BLUE BOX -->
                    @if($lead['hasEnoughCredits'])
                    <div style="text-align:center;">
                      <a href="{{ $baseUrl }}/sellers/leads" style="background:#ff9c2c;color:#ffffff;text-decoration:none; padding:8px 22px;border-radius:30px;font-size:15px; font-weight:bold;display:inline-block;">
                        Contact {{ $lead['lead_name'] }} now
                      </a>
                    </div>
                    @else
                    <div style="text-align:center;">
                      <a href="{{ $baseUrl }}/settings/billing/my-credits" style="background:#ff9c2c;color:#ffffff;text-decoration:none; padding:8px 22px;border-radius:30px;font-size:15px; font-weight:bold;display:inline-block;">
                        Contact Lead Now
                      </a>
                    </div>
                    @endif

                  </td>
                </tr>
              </table>








              @if(!empty($lead['questionsAndAnswers']))
              <table width="100%" cellpadding="0" cellspacing="0"
                style="margin-top:18px; font-family:Arial, sans-serif;">
                <tr>
                  <td>
                    <!-- Blue header bar -->
                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                      <tr>
                        <td bgcolor="#00AFE3"
                          style="padding:10px 14px; border-radius:2px 2px 0 0; font-weight:700; color:#ffffff; font-size:16px;">
                          Details
                        </td>
                      </tr>
                      <!-- small white gap under the blue bar -->
                      <tr>
                        <td style="background:#ffffff; height:10px; line-height:10px; font-size:1px;">&nbsp;</td>
                      </tr>
                    </table>
                    <!-- Details content (card) -->
                    <table width="100%" cellpadding="10" cellspacing="0" role="presentation" style="background:#ffffff;">
                      @foreach ($lead['questionsAndAnswers'] as $qa)
                      <tr>
                        <td style="font-size:14px;color:#253238;font-weight:700;padding-bottom:4px;">
                          {{ $qa['question'] }}
                        </td>
                      </tr>
                      <tr>
                        <td style="padding-bottom:12px;">
                          <div style="font-size:14px;color:#00AFE3;font-weight:600;">
                            {{ $qa['answer'] }}
                          </div>
                          @if(!$loop->last)
                          <div style="width:50%;border-bottom:1px solid #E3F6FC;margin-top:6px;"></div>
                          @endif

                        </td>
                      </tr>
                      @endforeach
                    </table>
                    <!-- pronounced white space after the whole details block -->
                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                      <tr>
                        <td height="20" style="line-height:20px; font-size:1px;">&nbsp;</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
              @endif

              @endforeach


              <!-- INFO + IMPORTANT PAGES SECTION -->
              <table width="100%" cellpadding="0" cellspacing="0">


                <!-- DETAILS INFO CARD -->

                <tr>
                  <td bgcolor="#00AFE3"
                    style="padding:10px 14px; border-radius:2px 2px 0 0; font-weight:700; color:#ffffff; font-size:16px;">
                    How We Work
                  </td>
                </tr>
                <!-- small white gap under the blue bar -->
                <tr>
                  <td style="background:#ffffff; height:10px; line-height:10px; font-size:1px;">&nbsp;</td>
                </tr>


                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                  style="background:#E3F6FC;border-radius:12px;">
                  <tr>
                    <td style="padding:14px 16px;">
                      <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                        <tr>
                          <td valign="middle" style="padding:0; font-size:0; line-height:0;">
                            <img src="{{$siteUrl}}/public/images/icons/info.png" width="30" height="30"
                              style="display:inline-block;vertical-align:middle;border:0;outline:none;margin:0;">
                            <span
                              style="display:inline-block;vertical-align:middle;margin-left:8px;font-size:16px;font-weight:800; color:#00AFE3;line-height:1;">
                              Customers tell us what they need
                            </span>
                          </td>
                        </tr>
                      </table>

                      <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top: 10px;">
                        <tr>
                          <td
                            style="font-family: Inter, Arial, Helvetica, sans-serif;font-size:12px;font-weight:500;line-height:18px;letter-spacing:0; color:#253238; padding:0; margin:0;text-align:left;">
                            Local customers share the services they’re looking for by answering
                            key questions relating to the service.
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>

                  <tr>
                    <td style="padding:14px 16px;">
                      <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                        <tr>
                          <td valign="middle" style="padding:0; font-size:0; line-height:0;">
                            <img src="{{$siteUrl}}/public/images/icons/target.png" width="30" height="30"
                              style="display:inline-block;vertical-align:middle;border:0;outline:none;margin:0;">
                            <span style="
                                    display:inline-block;
                                    vertical-align:middle;
                                    margin-left:8px;
                                    font-size:16px;
                                    font-weight:800;
                                    color:#00AFE3;
                                    line-height:1;">
                              Localists finds the right leads for you
                            </span>
                          </td>
                        </tr>
                      </table>

                      <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top: 10px;">
                        <tr>
                          <td style="
                  font-family: Inter, Arial, Helvetica, sans-serif;
                  font-size:12px;
                  font-weight:500;
                  line-height:18px;
                  letter-spacing:0;
                  color:#253238;
                  padding:0;
                  margin:0;
                  text-align:left;
                ">
                            We match your business with leads that fit your services and location,
                            delivered instantly to your inbox and dashboard.
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>





                  <tr>
                    <td style="padding:14px 16px;">
                      <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                        <tr>
                          <td valign="middle" style="padding:0; font-size:0; line-height:0;">
                            <img src="{{$siteUrl}}/public/images/icons/check.png" width="30" height="30"
                              style="display:inline-block;vertical-align:middle;border:0;outline:none;margin:0;">
                            <span style="
                                    display:inline-block;
                                    vertical-align:middle;
                                    margin-left:8px;
                                    font-size:16px;
                                    font-weight:800;
                                    color:#00AFE3;
                                    line-height:1;">
                              You review and select your leads
                            </span>
                          </td>
                        </tr>
                      </table>

                      <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top: 10px;">
                        <tr>
                          <td style="
                  font-family: Inter, Arial, Helvetica, sans-serif;
                  font-size:12px;
                  font-weight:500;
                  line-height:18px;
                  letter-spacing:0;
                  color:#253238;
                  padding:0;
                  margin:0;
                  text-align:left;
                ">
                            See full customer details straight away and choose the opportunities
                            that work best for your business.
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>


                </table>

                <!-- Item 1 -->


                <table width="100%" cellpadding="0" cellspacing="0"
                  style="margin-top:18px;background:#ffffff;border-radius:12px;">

                  <!-- BLUE HEADER -->
                  <tr>
                    <td bgcolor="#00AFE3" style="padding:10px 14px;font-weight:700;color:#ffffff;font-size:16px;">
                      Important Pages
                    </td>
                  </tr>

                  <!-- GAP -->
                  <tr>
                    <td height="12" style="font-size:1px;line-height:1px;">&nbsp;</td>
                  </tr>

                  <!-- LINKS -->
                  <tr>
                    <td style="padding:0 18px;">
                      <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                          <td style="font-size:13px;color:#253238;line-height:22px;">
                            1. <a href="{{ $baseUrl }}/sellers/dashboard"
                              style="color:#253238;text-decoration:underline;font-weight:700;">
                              Dashboard
                            </a><br>

                            2. <a href="{{ $baseUrl }}/sellers/leads"
                              style="color:#253238;text-decoration:underline;font-weight:700;">
                              Leads
                            </a><br>

                            3. <a href="{{ $baseUrl }}/settings/profile/my-profile"
                              style="color:#253238;text-decoration:underline;font-weight:700;">
                              My Profile
                            </a><br>

                            4. <a href="{{ $baseUrl }}/settings/leads/my-services"
                              style="color:#253238;text-decoration:underline;font-weight:700;">
                              My Services
                            </a><br>

                            5. <a href="{{ $baseUrl }}/settings/billing/my-credits"
                              style="color:#253238;text-decoration:underline;font-weight:700;">
                              My Credits
                            </a><br>

                            6. <a href="{{ $baseUrl }}/settings/billing/invoice-billing-details"
                              style="color:#253238;text-decoration:underline;font-weight:700;">
                              Invoices &amp; Billing Details
                            </a>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>

                  <!-- TEXT -->
                  <tr>
                    <td style="padding:14px 18px 0 18px;
               font-size:12px;color:#253238;line-height:18px;">
                      You currently have <strong>0 credits</strong> in your account.
                      To start receiving quality leads and allow our system to auto-bid on your behalf,
                      please purchase credits now.
                    </td>
                  </tr>

                  <!-- CTA BUTTON -->
                  <tr>
                    <td align="center" style="padding:16px 0 20px 0;">
                      <a href="{{ $baseUrl }}/settings/billing/my-credits" style="background:#ff9c2c;color:#ffffff;text-decoration:none;
                            padding:8px 22px;border-radius:30px;
                            font-size:14px;font-weight:700;display:inline-block;">
                        Contact Lead Now
                      </a>
                    </td>
                  </tr>

                </table>

            </td>
          </tr>

          <!-- HELP SECTION -->
          <tr>
            <td align="center" style="padding:20px 30px; background:rgba(227, 246, 252, 0.5); border-radius:8px;">
              <div style="font-size:16px; font-weight:800; color:#253238; margin-bottom:8px; text-align:center;">
                Need Help?
              </div>
              <div style="font-size:12px; font-weight:600; color:#253238; line-height:18px; text-align:center;">
                Email us at:
                <a href="mailto:contact@localists.com" style="color:#00AFE3; text-decoration:none;">
                  contact@localists.com
                </a>
              </div>
            </td>
          </tr>

          <!-- FOOTER -->
          <tr>
            <td align="center" bgcolor="#131838" style="padding:9px 18px;">
              <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center">
                <tr>
                  <td valign="middle" style="padding-right:8px;">
                    <img src="{{$siteUrl}}/public/images/globleimg.png" width="19" height="19" alt=""
                      style="display:block;">
                  </td>
                  <td valign="middle"
                    style="font-size:13px; line-height:18px; color:#ffffff; font-family:Inter, Arial, sans-serif; padding-right:10px;">
                    Localists.com
                  </td>
                  <td valign="middle" style="padding:0 10px; font-size:13px; line-height:18px; color:#ffffff;">|</td>
                  <td valign="middle" style="padding-right:8px;">
                    <img src="{{$siteUrl}}/public/images/vectorimg.png" width="18" height="14" alt=""
                      style="display:block;">
                  </td>
                  <td valign="middle" style="font-size:13px; line-height:18px; font-family:Inter, Arial, sans-serif;">
                    <a href="mailto:contact@localists.com" style="color:#ffffff; text-decoration:none;">
                      contact@localists.com
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

</body>

</html>