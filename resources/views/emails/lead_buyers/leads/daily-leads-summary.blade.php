<!doctype html>
<html>

<head>
  <meta charset="UTF-8">
  <title>Daily Lead Summary</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="margin:0;padding:0;background:#f3f6fb;font-family:Inter,Helvetica,sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td align="center" style="padding:20px;">

        <!-- MAIN CONTAINER -->
        <table width="600" cellpadding="0" cellspacing="0"
          style="max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;">

          <!-- HEADER -->
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
            <td style="padding:20px 20px 38px 40px;font-size:14px;color:#333;">
              Hi <strong>{{ ucfirst($name) }}</strong>,<br><br>
              Here’s a summary of new customer quotes received today on
              <a href="{{ $baseUrl }}" style="color:#00aee6;text-decoration:none;font-weight:bold;">
                Localists.com
              </a>
            </td>
          </tr>

          <!-- ================= MISSED LEADS ================= -->
          @if(!empty($sections['missed_secured_lastchance']))
          <tr>
            <td style="padding:0 20px 16px 40px;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="44" valign="top" style="padding-right:10px;">
                    <img src="{{$siteUrl}}/public/images/icons/alarm.png" width="44" height="44" alt="Missed"
                      style="display:block;border:0;outline:none;">
                  </td>
                  <td valign="top" style="font-family:Arial,Helvetica,sans-serif;">
                    <div style="font-size:18px;font-weight:700;color:#111111;line-height:22px;">
                      Lead(s) <span style="color:#e53935;">Missed</span> Today
                    </div>
                    <div style="font-size:12px;color:#000000;line-height:18px;padding-top:4px;font-weight:500;">
                      These lead(s) couldn’t be secured and were shared with other professionals
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          @foreach($sections['missed_secured_lastchance'] as $lead)
          <tr>
            <td style="padding:0px 20px;">
              <table width="100%" style="border:1px solid #e5e7eb;border-radius:6px;">
                <tr>
                  <td style="padding:16px;">
                    <div style="font-size:16px;font-weight:bold;margin-bottom:15px;">{{ ucfirst($lead['lead_name']) }}</div>

                    <div style="font-size:10px;color:#253238;font-weight:600;margin-top:6px;">
                      <div style="line-height:15px;margin-bottom:10px;">
                        <img src="{{$siteUrl}}/public/images/icons/marker-pin.png" alt="Location" style="display:inline-block;vertical-align:middle;margin-right:4px;">
                        {{ $lead['postcode'] }}
                      </div>
                      <div style="line-height:15px;margin-bottom:10px;">
                        <img src="{{$siteUrl}}/public/images/icons/mail.png" alt="Email" style="display:inline-block;vertical-align:middle;margin-right:4px;">
                        {{ $lead['masked_email'] }}
                      </div>
                      <div style="line-height:15px;margin-bottom:10px;">
                        <img src="{{$siteUrl}}/public/images/icons/call.png" alt="Phone" style="display:inline-block;vertical-align:middle;margin-right:4px;">
                        {{ $lead['masked_phone'] }}
                      </div>
                      <!-- <div style="line-height:15px;">
                        <img src="{{$siteUrl}}/public/images/icons/credit-card.png" alt="Credit" style="display:inline-block;vertical-align:middle;margin-right:4px;">
                        {{ $lead['credit_score'] }} Credit Required
                      </div> -->
                    </div>

                    <div style="font-size:15px;font-weight:bold;margin-top:15px;color: #253238;">
                      {{ $lead['service_name'] }}
                    </div>

                    <div style="font-size:13px;color:#6b7280;margin-top:10px;">
                      @foreach($lead['questionsAndAnswers'] as $qa)
                      {{ $qa['answer'] }}@if(!$loop->last), @endif
                      @endforeach
                    </div>
                    @php
                    $total2 = $sectionCounts['credit_enough'] ?? 0;
                    @endphp
                    <div align="center" style="margin-top:14px;">
                      <a href="{{ $baseUrl }}/sellers/leads" style="background:#2f7cf6;color:#fff;text-decoration:none;padding:10px 26px;border-radius:22px;font-weight:bold;font-size:14px;display:inline-block;">
                        {{ $total2 > 1 ? 'View all details' : 'View Details' }} 
                      </a>
                    </div>

                  </td>
                </tr>
              </table>
            </td>
          </tr>
          @endforeach
          @endif

          <!-- ================= CREDIT ENOUGH ================= -->
          @if(!empty($sections['credit_enough']))
          <tr>
            <td style="padding: 25px 36px 0;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="44" valign="top" style="padding-right:10px;">
                    <img src="{{$siteUrl}}/public/images/icons/checked--v1.png" width="44" height="44" alt="Secured"
                      style="display:block;border:0;outline:none;">
                  </td>
                  <td valign="top" style="font-family:Arial,Helvetica,sans-serif;">
                    <div style="font-size:20px;font-weight:700;color:#111111;line-height:22px;">
                      Lead(s) <span style="color:#16a34a;">Secured</span> Today
                    </div>
                    <div style="font-size:12px;color:#000000;line-height:18px;padding-top:4px;">
                      Great news! You’ve successfully secured the following lead(s):
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          @foreach($sections['credit_enough'] as $lead)
          <tr>
            <td style="padding:10px 20px;">
              <table width="100%" style="border:1px solid #e5e7eb;border-radius:6px;">
                <tr>
                  <td style="padding:16px;">
                    <div style="font-size:16px;font-weight:bold;margin-bottom:15px;">{{ $lead['lead_name'] }}</div>
                    <div style="font-size:10px;color:#253238;font-weight:600;margin-top:6px;">
                      <div style="line-height:15px;margin-bottom:10px;">
                        <img src="{{$siteUrl}}/public/images/icons/marker-pin.png" alt="Location" style="display:inline-block;vertical-align:middle;margin-right:4px;">
                        {{ $lead['fullpostcode'] }}
                      </div>
                      <div style="line-height:15px;margin-bottom:10px;">
                        <img src="{{$siteUrl}}/public/images/icons/mail.png" alt="Email" style="display:inline-block;vertical-align:middle;margin-right:4px;">
                        {{ $lead['userEmail'] }}
                      </div>
                      <div style="line-height:15px;margin-bottom:10px;">
                        <img src="{{$siteUrl}}/public/images/icons/call.png" alt="Phone" style="display:inline-block;vertical-align:middle;margin-right:4px;">
                        {{ $lead['userPhone'] }}
                      </div>
                      <div style="line-height:15px;">
                        <img src="{{$siteUrl}}/public/images/icons/credit-card.png" alt="Credit" style="display:inline-block;vertical-align:middle;margin-right:4px;">
                        {{ $lead['credit_score'] }} Credit
                      </div>
                    </div>
                    <div style="font-size:15px;font-weight:bold;margin-top:15px;color: #253238;">
                      {{ $lead['service_name'] }}
                    </div>
                    <div style="font-size:13px;color:#6b7280;margin-top:10px;">
                      @foreach($lead['questionsAndAnswers'] as $qa)
                      {{ $qa['answer'] }}@if(!$loop->last), @endif
                      @endforeach
                    </div>
                    @php
                    $total1 = $sectionCounts['credit_enough'] ?? 0;
                    @endphp
                    <div align="center" style="margin-top:14px;">
                      <a href="{{ $baseUrl }}/sellers/leads/my-responses" style="background:#2f7cf6;color:#fff;text-decoration:none;padding:10px 26px;border-radius:22px;font-weight:bold;font-size:14px;display:inline-block;">
                        {{ $total1 > 1 ? 'View all details' : 'View Details' }}
                      </a>
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          @endforeach
          @endif

          <!-- ================= CREDIT NOT ENOUGH (LAST CHANCE) ================= -->
          @if(!empty($sections['credit_not_enough']))
          <tr>
            <td style="padding: 25px 36px 0;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="44" valign="top" style="padding-right:10px;">
                    <img src="{{$siteUrl}}/public/images/icons/time.png" width="44" height="44" alt="Last Chance"
                      style="display:block;border:0;outline:none;">
                  </td>
                  <td valign="top" style="font-family:Inter,Arial,Helvetica,sans-serif;">
                    <div style="font-size:20px;font-weight:700;color:#111111;line-height:22px;">
                      <span style="color:#FF463D;">Last Chance</span> to Bid and Secure
                    </div>
                    <div style="font-size:12px;color:#000000;line-height:18px;padding-top:4px;">
                      These lead(s) couldn’t be secured as you have low credits<br>
                      <strong>Auto‑bid disabled</strong>
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          @foreach($sections['credit_not_enough'] as $lead)
          <tr>
            <td style="padding:10px 20px;">
              <table width="100%" style="border:1px solid #e5e7eb;border-radius:6px;">
                <tr>
                  <td style="padding:16px;">
                    <div style="font-size:16px;font-weight:bold;margin-bottom:15px;">{{ $lead['lead_name'] }}</div>
                    <div style="font-size:10px;color:#253238;font-weight:600;margin-top:6px;">
                      <div style="line-height:15px;margin-bottom:10px;">
                        <img src="{{$siteUrl}}/public/images/icons/marker-pin.png" alt="Location" style="display:inline-block;vertical-align:middle;margin-right:4px;">
                        {{ $lead['postcode'] }}
                      </div>
                      <div style="line-height:15px;margin-bottom:10px;">
                        <img src="{{$siteUrl}}/public/images/icons/mail.png" alt="Email" style="display:inline-block;vertical-align:middle;margin-right:4px;">
                        {{ $lead['masked_email'] }}
                      </div>
                      <div style="line-height:15px;margin-bottom:10px;">
                        <img src="{{$siteUrl}}/public/images/icons/call.png" alt="Phone" style="display:inline-block;vertical-align:middle;margin-right:4px;">
                        {{ $lead['masked_phone'] }}
                      </div>
                      <div style="line-height:15px;">
                        <img src="{{$siteUrl}}/public/images/icons/credit-card.png" alt="Credit" style="display:inline-block;vertical-align:middle;margin-right:4px;">
                        {{ $lead['credit_score'] }} Credit Required
                      </div>
                    </div>
                    <div style="font-size:15px;font-weight:bold;margin-top:15px;color: #253238;">
                      {{ $lead['service_name'] }}
                    </div>
                    <div style="font-size:13px;color:#6b7280;margin-top:10px;">
                      @foreach($lead['questionsAndAnswers'] as $qa)
                      {{ $qa['answer'] }}@if(!$loop->last), @endif
                      @endforeach
                    </div>
                    @php
                    $total = $sectionCounts['credit_not_enough'] ?? 0;
                    @endphp
                    <div align="center" style="margin-top:14px;">
                      <a href="{{ $baseUrl }}/sellers/leads" style="background:#2f7cf6;color:#fff;text-decoration:none;padding:10px 26px;border-radius:22px;font-weight:bold;font-size:14px;display:inline-block;">
                        {{ $total > 1 ? 'View all details' : 'View Details' }} 
                      </a>
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          @endforeach
          @endif

          <!-- ================= HOW TO SECURE ================= -->
          @if(($totalCredit ?? 0) < 50 || $autobidStatus==1)
            <tr>
            <td style="padding:20px;">
              <table width="100%" cellpadding="0" cellspacing="0" style="border-radius:6px;">
                <tr>
                  <td width="56" valign="top" style="padding-right:8px;">
                    <img src="{{$siteUrl}}/public/images/icons/idea.png" width="44" height="44" alt="idea" style="display:block; border:0; outline:none; text-decoration:none;">
                  </td>
                  <td valign="top" style="padding:0; font-family:'Inter', sans-serif; font-weight:800; font-size:25px; color:#00AFE3; line-height:1.2;">
                    How to secure more leads
                  </td>
                </tr>
                <tr>
                  <td width="56" valign="top" style="font-size:0; line-height:0;">&nbsp;</td>
                  <td valign="top" style="padding-top:0px; font-family:'Inter', sans-serif;">
                    <ul style="font-size:13px; color:#333; margin:0px 0 12px 18px; padding:0;">
                      @if(($totalCredit ?? 0) < 50)
                        <li style="margin-bottom:10px;">
                         <a href="{{ $baseUrl }}/settings/billing/my-credits">Add credits</a> to avoid missing new enquiries
                        </li>
                        @endif
                        @if($autobidStatus==1)
                        <li style="margin-bottom:6px;"><a href="{{ $baseUrl }}/settings/billing/my-credits">Enable Auto Bid</a> to secure matching leads automatically</li>
                        @endif
                    </ul>
                    <div style="font-size:12px; color:#000000;font-weight:500;">
                      Setting this up now helps ensure you don’t miss out when new customers in your area request quotes.
                    </div>
                  </td>
                </tr>
              </table>
            </td>
    </tr>
    @endif


    <!-- FOOTER -->
    <tr>
      <td align="center" bgcolor="#E9F6FB" style="padding:16px;">
        <p style="margin:0 0 6px;font-size:16px;font-weight:800;color:#253238;">
          Need Help?
        </p>
        <p style="margin:0;font-size:13px;color:#253238;">
          Email us at:
          <a href="mailto:contact@localists.com" style="color:#00AFE3;text-decoration:none;font-weight:700;">
            contact@localists.com
          </a>
        </p>
      </td>
    </tr>

    <tr>
      <td align="center" bgcolor="#131838" style="padding:12px 18px;">
        <table cellspacing="0" cellpadding="0" border="0" align="center">
          <tr>
            <td style="padding-right:8px;">
              <img src="{{$siteUrl}}/public/images/globleimg.png" width="18" height="18" style="display:block;">
            </td>
            <td style="font-size:13px;color:#ffffff;padding-right:10px;">
              Localists.com
            </td>
            <td style="padding:0 10px;color:#ffffff;">|</td>
            <td style="padding-right:8px;">
              <img src="{{$siteUrl}}/public/images/vectorimg.png" width="18" height="14" style="display:block;">
            </td>
            <td style="font-size:13px;">
              <a href="mailto:contact@localists.com" style="color:#ffffff;text-decoration:none;">
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