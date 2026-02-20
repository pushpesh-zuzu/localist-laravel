<!doctype html>
<html>

<head>
  <meta charset="UTF-8">
  <title>Daily Lead Summary</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Use inline styles for maximum email-client compatibility; minimal style block -->
  <style>
    /* email-safe fallbacks */
    body {
      -webkit-text-size-adjust: 100%;
      -ms-text-size-adjust: 100%;
    }

    img {
      border: 0;
      outline: none;
      text-decoration: none;
      display: block;
    }

    a {
      color: inherit;
    }
  </style>
</head>

<body style="margin:0;padding:0;background:#f3f6fb;font-family:Inter, 'Helvetica Neue',Helvetica, Arial, sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
      <td align="center" style="padding:28px 16px;">

        <!-- MAIN CONTAINER -->
        <table width="680" cellpadding="0" cellspacing="0" role="presentation" style="max-width:680px;background:#ffffff;border-radius:8px;overflow:hidden;">

          <!-- TOP BLUE BAR with rounded corners and logo -->
          <tr>
            <td align="center" style="padding:20px;">
              <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                <tr>
                  <td bgcolor="#00AFE3" height="40" align="center" style="border-radius:5px;">
                    <img src="{{ $baseUrl }}/assets/localist_logo_1.png" height="26" alt="Localists"
                      style="display: block; border: 0; outline: none;">
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- GREETING -->
          <tr>
            <td style="padding:22px 40px 41px;font-size:14px;color:#333;">
              Hi <strong style="font-weight:700;">{{ ucfirst($name) }}</strong>,<br><br>
              Here’s a summary of new customer quotes received today on
              <a href="{{ $baseUrl }}" style="color:#00aee6;text-decoration:none;font-weight:700;">Localists.com</a>.
            </td>
          </tr>

          <!-- ========== MISSED LEADS HEADER ========== -->
          @if(!empty($sections['missed_secured_lastchance']))
          <tr>
            <td style="padding:24px 40px 21px;">
              <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                  <td width="56" height="44" valign="middle" style="padding-right:4px;">
                    <img src="{{$siteUrl}}/public/images/icons/alarm.png"
                      width="44" height="44" alt="Missed" style="display:block;">
                  </td>
                  <td height="44" valign="middle" style="font-family:Arial,Helvetica,sans-serif;">
                    <div style="font-size:20px;font-weight:800;color:#111111;line-height:20px;">
                      Lead(s) <span style="color:#e53935;">Missed</span> Today
                    </div>
                    <div style="font-size:13px;color:#333333;line-height:18px;margin-top:6px;">
                      These lead(s) couldn’t be secured and were shared with other professionals
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>


          @foreach($sections['missed_secured_lastchance'] as $lead)
          <!-- Missed Lead Card -->
          <tr>
            <td style="padding:12px 40px 18px;">
              <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border:1px solid #e5e7eb;border-radius:6px;">
                <tr>
                  <td style="padding:18px;">
                    <div style="font-size:20px;font-weight:800;color:#111;margin-bottom:12px;">{{ ucfirst($lead['lead_name']) }}</div>

                    <div style="font-size:12px;color:#253238;font-weight:600;margin-bottom:14px;">
                      <div style="line-height:18px;margin-bottom:8px;">
                        <img src="{{$siteUrl}}/public/images/icons/marker-pin.png" alt="Location" style="display:inline-block;vertical-align:middle;margin-right:8px;width:14px;height:14px;">
                        {{ $lead['postcode'] }}
                      </div>
                      <div style="line-height:18px;margin-bottom:8px;">
                        <img src="{{$siteUrl}}/public/images/icons/mail.png" alt="Email" style="display:inline-block;vertical-align:middle;margin-right:8px;width:14px;height:14px;">
                        {{ $lead['masked_email'] }}
                      </div>
                      <div style="line-height:18px;margin-bottom:8px;">
                        <img src="{{$siteUrl}}/public/images/icons/call.png" alt="Phone" style="display:inline-block;vertical-align:middle;margin-right:8px;width:14px;height:14px;">
                        {{ $lead['masked_phone'] }}
                      </div>
                    </div>

                    <div style="font-size:15px;font-weight:700;color:#253238;margin-top:6px;">{{ $lead['service_name'] }}</div>

                    <div style="font-size:13px;color:#6b7280;line-height:20px;margin-top:10px;">
                      @foreach($lead['questionsAndAnswers'] as $qa)
                      {{ $qa['answer'] }}@if(!$loop->last), @endif
                      @endforeach
                    </div>

                    @php $total2 = $sectionCounts['credit_enough'] ?? 0; @endphp


                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top:20px;">
                      <tr>
                        <td align="center">
                          <table cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                              <td align="center"
                                width="172"
                                height="36"
                                bgcolor="#2E7BF1"
                                style="border-radius:119px;font-family:Inter, Arial, sans-serif;">

                                <a href="{{ $postloginUrl }}/sellers/leads"
                                  style="display:inline-block;
                      width:172px;
                      line-height:36px;
                      font-size:14px;
                      font-weight:700;
                      color:#ffffff;
                      text-decoration:none;">
                                  {{ $total2 > 1 ? 'View all details' : 'View Details' }}
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
          @endforeach
          @endif

          <!-- ================= CREDIT ENOUGH ================= -->
          @if(!empty($sections['credit_enough']))
          <!-- ========== SECURED LEADS HEADER ========== -->
          <tr>
            <td style="padding:6px 40px 0;">
              <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                  <td width="56" valign="top" style="padding-right:10px;">
                    <img src="{{$siteUrl}}/public/images/icons/checked--v1.png" width="44" height="44" alt="Secured" style="display:block;">
                  </td>
                  <td valign="middle" style="font-family:Arial,Helvetica,sans-serif;">
                    <div style="font-size:20px;font-weight:800;color:#111111;line-height:1.15;">
                      Lead(s) <span style="color:#16a34a;">Secured</span> Today
                    </div>
                    <div style="font-size:13px;color:#333333;line-height:18px;margin-top:6px;">
                      Great news! You’ve successfully secured the following lead(s):
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          @foreach($sections['credit_enough'] as $lead)
          <!-- Secured Lead Card -->
          <tr>
            <td style="padding:12px 40px 18px;">
              <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border:1px solid #e5e7eb;border-radius:6px;">
                <tr>
                  <td style="padding:18px;">
                    <div style="font-size:20px;font-weight:800;color:#111;margin-bottom:12px;">{{ $lead['lead_name'] }}</div>

                    <div style="font-size:12px;color:#253238;font-weight:600;margin-top:6px;margin-bottom:12px;">
                      <div style="line-height:18px;margin-bottom:8px;">
                        <img src="{{$siteUrl}}/public/images/icons/marker-pin.png" alt="Location" style="display:inline-block;vertical-align:middle;margin-right:8px;width:14px;height:14px;">
                        {{ $lead['fullpostcode'] }}
                      </div>
                      <div style="line-height:18px;margin-bottom:8px;">
                        <img src="{{$siteUrl}}/public/images/icons/mail.png" alt="Email" style="display:inline-block;vertical-align:middle;margin-right:8px;width:14px;height:14px;">
                        {{ $lead['userEmail'] }}
                      </div>
                      <div style="line-height:18px;margin-bottom:8px;">
                        <img src="{{$siteUrl}}/public/images/icons/call.png" alt="Phone" style="display:inline-block;vertical-align:middle;margin-right:8px;width:14px;height:14px;">
                        {{ $lead['userPhone'] }}
                      </div>
                      <div style="line-height:18px;">
                        <img src="{{$siteUrl}}/public/images/icons/credit-card.png" alt="Credit" style="display:inline-block;vertical-align:middle;margin-right:8px;width:14px;height:14px;">
                        {{ $lead['credit_score'] }} Credit
                      </div>
                    </div>

                    <div style="font-size:15px;font-weight:700;color:#253238;margin-top:6px;">{{ $lead['service_name'] }}</div>

                    <div style="font-size:13px;color:#6b7280;line-height:20px;margin-top:10px;">
                      @foreach($lead['questionsAndAnswers'] as $qa)
                      {{ $qa['answer'] }}@if(!$loop->last), @endif
                      @endforeach
                    </div>

                    @php $total1 = $sectionCounts['credit_enough'] ?? 0; @endphp



                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top:20px;">
                      <tr>
                        <td align="center">
                          <table cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                              <td align="center"
                                width="172"
                                height="36"
                                bgcolor="#2E7BF1"
                                style="border-radius:119px;font-family:Inter, Arial, sans-serif;">

                                <a href="{{ $postloginUrl }}/sellers/leads/my-responses"
                                  style="display:inline-block; width:172px; line-height:36px; font-size:14px;font-weight:700;  color:#ffffff; text-decoration:none;">
                                  {{ $total1 > 1 ? 'View all details' : 'View Details' }}
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
          @endforeach
          @endif

          <!-- ================= CREDIT NOT ENOUGH (LAST CHANCE) ================= -->
          @if(!empty($sections['credit_not_enough']))
          <!-- ========== LAST CHANCE (credit not enough) HEADER ========== -->
          <tr>
            <td style="padding:{{ (!empty($sections['missed_secured_lastchance']) || !empty($sections['credit_enough'])) ? '15px' : '6px' }} 40px 0;">

              <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                  <td width="56" valign="top" style="padding-right:10px;">
                    <img src="{{$siteUrl}}/public/images/icons/time.png" width="44" height="44" alt="Last Chance" style="display:block;">
                  </td>
                  <td valign="middle" style="font-family:Inter,Arial,Helvetica,sans-serif;">
                    <div style="font-size:20px;font-weight:800;color:#111111;line-height:1.15;">
                      <span style="color:#FF463D;">Last Chance</span> to Bid and Secure
                    </div>

                    @php
                    $lowCredit = ($totalCredit ?? 0) < 50;
                      $autoBidDisabled=($autobidStatus ?? 0)==1;
                      @endphp

                      @if($lowCredit || $autoBidDisabled)
                      <div style="font-size:13px;color:#333333;line-height:18px;margin-top:6px;font-weight:600;">
                      These lead(s) couldn't be secured because you have
                      @if($lowCredit) low credits @endif
                      @if($lowCredit && $autoBidDisabled) and @endif
                      @if($autoBidDisabled) Auto-bid disabled @endif.
                      </div>
                      @endif

                  </td>
                </tr>
              </table>
            </td>
          </tr>
          @php
          $isFirstVisibleSection =
          empty($sections['missed']) &&
          empty($sections['credit_enough']);
          @endphp
          @foreach($sections['credit_not_enough'] as $lead)
          <!-- Last Chance Lead Card -->
          <tr>
            <td style="padding:{{ $isFirstVisibleSection && $loop->first ? '15px' : ($loop->first ? '12px' : '20px') }} 40px 26px;">
              <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border:1px solid #e5e7eb;border-radius:6px;">
                <tr>
                  <td style="padding:18px;">
                    <div style="font-size:20px;font-weight:800;color:#111;margin-bottom:12px;">
                      {{ $lead['lead_name'] }}
                    </div>

                    <div style="font-size:12px;color:#253238;font-weight:600;margin-top:6px;margin-bottom:12px;">
                      <div style="line-height:18px;margin-bottom:8px;">
                        <img src="{{$siteUrl}}/public/images/icons/marker-pin.png" alt="Location" style="display:inline-block;vertical-align:middle;margin-right:8px;width:14px;height:14px;">
                        {{ $lead['postcode'] }}
                      </div>
                      <div style="line-height:18px;margin-bottom:8px;">
                        <img src="{{$siteUrl}}/public/images/icons/mail.png" alt="Email" style="display:inline-block;vertical-align:middle;margin-right:8px;width:14px;height:14px;">
                        {{ $lead['masked_email'] }}
                      </div>
                      <div style="line-height:18px;margin-bottom:8px;">
                        <img src="{{$siteUrl}}/public/images/icons/call.png" alt="Phone" style="display:inline-block;vertical-align:middle;margin-right:8px;width:14px;height:14px;">
                        {{ $lead['masked_phone'] }}
                      </div>
                      <div style="line-height:18px;">
                        <img src="{{$siteUrl}}/public/images/icons/credit-card.png" alt="Credit" style="display:inline-block;vertical-align:middle;margin-right:8px;width:14px;height:14px;">
                        {{ $lead['credit_score'] }} Credit Required
                      </div>
                    </div>

                    <div style="font-size:15px;font-weight:700;color:#253238;margin-top:6px;">
                      {{ $lead['service_name'] }}
                    </div>

                    <div style="font-size:13px;color:#6b7280;line-height:20px;margin-top:10px;">
                      @foreach($lead['questionsAndAnswers'] as $qa)
                      {{ $qa['answer'] }}@if(!$loop->last), @endif
                      @endforeach
                    </div>

                    @php $total = $sectionCounts['credit_not_enough'] ?? 0; @endphp

                    <!-- Updated Button -->
                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top:16px;">
                      <tr>
                        <td align="center">
                          <table cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                              <td align="center"
                                width="172"
                                height="36"
                                bgcolor="#2E7BF1"
                                style="border-radius:119px;font-family:Inter, Arial, sans-serif;">

                                <a href="{{ $postloginUrl }}/sellers/leads"
                                  style="display:inline-block;
                                width:172px;
                                line-height:36px;
                                font-size:14px;
                                font-weight:600;
                                color:#ffffff;
                                text-decoration:none;">
                                  {{ $total > 1 ? 'View all details' : 'View Details' }}
                                </a>

                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                    <!-- End Button -->

                  </td>
                </tr>
              </table>
            </td>
          </tr>


          @endforeach
          @endif

          <!-- ================= HOW TO SECURE ================= -->
          @if(($totalCredit ?? 0) < 50 || $autobidStatus==1)
            <!--==========HOW TO SECURE MORE LEADS==========-->
            <tr>
              <td style="padding:18px 40px 26px;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation">

                  <!-- ICON + HEADING ROW -->
                  <tr>
                    <td width="56" height="44" valign="middle" style="padding-right:4px;">
                      <img src="{{$siteUrl}}/public/images/icons/idea.png" width="44" height="44"
                        alt="idea" style="display:block;">
                    </td>

                    <td height="44" valign="middle" style="font-family:Inter, Arial, sans-serif;">
                      <div style="font-weight:800;
                      font-size:24px;
                      color:#00AFE3;
                      line-height:24px;">
                        How to secure more leads
                      </div>
                    </td>
                  </tr>

                  <!-- SPACING -->
                  <tr>
                    <td></td>
                    <td height="14" style="font-size:0;line-height:0;">&nbsp;</td>
                  </tr>

                  <!-- CONTENT ROW -->
                  <tr>
                    <td></td>
                    <td style="font-family:Inter, Arial, sans-serif;">

                      <ul style="font-size:14px;color:#333;margin:0 0 12px 18px;padding:0;line-height:22px;">
                        @if(($totalCredit ?? 0) < 50) <li style="margin-bottom:8px;">
                          <a href="{{ $postloginUrl }}/settings/billing/my-credits"
                            style="color:#00AFE3;text-decoration:underline;">
                            Add credits
                          </a>
                          to avoid missing new enquiries
                          </li>
                          @endif

                          @if($autobidStatus==1)
                          <li style="margin-bottom:8px;">
                            <a href="{{ $postloginUrl }}/settings/billing/my-credits"
                              style="color:#00AFE3;text-decoration:underline;">
                              Enable Auto Bid
                            </a>
                            to secure matching leads automatically
                          </li>
                          @endif
                      </ul>

                      <div style="font-size:13px;color:#000;font-weight:500;line-height:18px;">
                        Setting this up now helps ensure you don’t miss out when new customers in your area request
                        quotes.
                      </div>

                    </td>
                  </tr>

                </table>
              </td>
            </tr>
            @endif
            <!-- FOOTER CONTACT BLOCK -->
            <tr>
              <td align="center" bgcolor="#E9F6FB" style="padding:20px 40px;">
                <p style="margin:0 0 6px;font-size:16px;font-weight:800;color:#253238;">Need Help?</p>
                <p style="margin:0;font-size:13px;color:#253238;">
                  Email us at:
                  <a href="mailto:contact@localists.com" style="color:#00AFE3;text-decoration:none;font-weight:700;">contact@localists.com</a>
                </p>
              </td>
            </tr>

            <!-- DARK BOTTOM BAR -->
            <tr>
              <td align="center" bgcolor="#131838" style="padding:12px 18px;">
                <table cellspacing="0" cellpadding="0" border="0" align="center" role="presentation">
                  <tr>
                    <td style="padding-right:8px;">
                      <img src="{{$siteUrl}}/public/images/globleimg.png" width="18" height="18" style="display:block;">
                    </td>
                    <td style="font-size:13px;color:#ffffff;padding-right:10px;">Localists.com</td>
                    <td style="padding:0 10px;color:#ffffff;">|</td>
                    <td style="padding-right:8px;">
                      <img src="{{$siteUrl}}/public/images/vectorimg.png" width="18" height="14" style="display:block;">
                    </td>
                    <td style="font-size:13px;">
                      <a href="mailto:contact@localists.com" style="color:#ffffff;text-decoration:none;font-weight:600;">contact@localists.com</a>
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