<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <title>You've Hired a Lead</title>
</head>

<body style="margin:0;padding:0;background:#f4f8fb;font-family:Arial,Helvetica,sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td align="center" style="padding:30px 10px;">

        <table width="600" cellpadding="0" cellspacing="0"
          style="background:#ffffff;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.08);">

          <!-- HEADER -->
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

          <!-- BODY -->
          <tr>
            <td style="padding:5px 30px;">

              <p style="font-size:16px;margin:0 0 8px;">
                Hi <strong>{{ ucfirst($name) }}</strong>,
              </p>

              <p style="color:#555;font-size:14px;margin:10 0 10px;">
                Congratulations — You have successfully purchased the <strong>{{ $service_name }}</strong> lead.
              </p>

              <!-- LEAD TAGS -->
              <div style="margin-bottom:18px;">
                @if($phone_verified)
                <span style="display:inline-block;background:#f39ac3;color:#ffffff;font-size:12px;font-weight:600;padding:6px 10px;border-radius:14px;margin:0 6px 6px 0;">📞 Verified Phone</span>
                @endif
                @if($has_additional_details)
                <span style="display:inline-block;background:#e6e6e6;color:#333333;font-size:12px;font-weight:600;padding:6px 10px;border-radius:14px;margin:0 6px 6px 0;">📋 Additional Details</span>
                @endif
                @if($is_frequent_user)
                <span style="display:inline-block;background:#a0d8ef;color:#000000;font-size:12px;font-weight:600;padding:6px 10px;border-radius:14px;margin:0 6px 6px 0;">🔁 Frequent User</span>
                @endif
                @if($is_urgent)
                <span style="display:inline-block;background:#ffd9a6;color:#000000;font-size:12px;font-weight:600;padding:6px 10px;border-radius:14px;margin:0 6px 6px 0;">⏰ Urgent</span>
                @endif
                @if($is_high_hiring)
                <span style="display:inline-block;background:#d1f7d9;color:#000000;font-size:12px;font-weight:600;padding:6px 10px;border-radius:14px;margin:0 6px 6px 0;">🚀 High Hiring</span>
                @endif
              </div>

              <!-- NEW LEAD -->
              <!-- <div style="text-align:center;margin-bottom:18px;">
                <strong style="font-size:18px;">🎯 New Lead Unlocked</strong>
              </div> -->

              <!-- LEAD DETAILS -->
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:10px;border-radius:8px;background:#f1f9ff;">
                    <strong>🏅</strong> {{ $credit_score }} credits
                  </td>
                </tr>
                <tr>
                  <td height="8"></td>
                </tr>
                <tr>
                  <td style="padding:10px;border-radius:8px;background:#f6f6f6;">
                    <strong>📍</strong> {{ $postcode }}
                  </td>
                </tr>
                <tr>
                  <td height="8"></td>
                </tr>
                <tr>
                  <td style="padding:10px;border-radius:8px;background:#fff4e9;">
                    <strong>📞</strong> {{ $phone }}
                  </td>
                </tr>
                <tr>
                  <td height="8"></td>
                </tr>
                <tr>
                  <td style="padding:10px;border-radius:8px;background:#eef3ff;">
                    <strong>✉️</strong> {{ $email }}
                  </td>
                </tr>
              </table>

              <!-- CTA -->
              <div style="text-align:center;margin:28px 0;">
                <a href="{{ $baseUrl }}/sellers/leads/my-responses" style="background:#66FF0D;color:#253238;text-decoration:none;
                   padding:12px 22px;border-radius:30px;font-size:15px;
                   font-weight:bold;display:inline-block;">
                  Contact Lead Now
                </a>
              </div>

              <!-- CUSTOMER REQUIREMENTS -->
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:20px;">
                <tr>
                  <td>
                    <div style="font-size:16px;font-weight:800;color:#253238;margin-bottom:12px;padding-bottom:8px;border-bottom:2px solid #e3f1f8;">
                      📝 Customer Requirements
                    </div>

                    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4fbff;border-radius:14px;padding:24px;font-family:Arial, sans-serif;">
                    
                      @if(!empty($questionsAndAnswers))
                      @foreach ($questionsAndAnswers as $qa)

                      <tr>
                        <td style="font-size:15px;color:#5c6f7b;font-weight:600;padding-bottom:8px;">
                          {{ $qa['question'] }}
                        </td>
                      </tr>
                      <tr>
                        <td style="font-size:16px;color:#ff7a00;font-weight:700;padding-bottom:18px;">
                          {{ $qa['answer'] }}
                        </td>
                      </tr>
                      <tr>
                        <td style="border-bottom:1px solid #dceef7; padding-bottom:1px;"></td>
                      </tr>
                      <tr>
                        <td height="14"></td>
                      </tr>
                      @endforeach
                      @else
                      <tr>
                        <td style="font-size:16px;color:#ff7a00;font-weight:700;padding-bottom:18px;">
                          No additional details provided.
                        </td>
                      </tr>
                      <tr>
                        <td height="14"></td>
                      </tr>                      
                      @endif

                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:18px 20px;">
              <div style="font-size:16px; font-weight:800; color:#00AFE3;">
                Need Help?
              </div>
              <div style="font-size:12px; font-weight:600; color:#253238; line-height:18px;">
                Our team is here to help you make the most of Localists.<br>
                Email us at
                <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}"
                  style="color:#00AFE3; text-decoration:none;">
                  {{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}
                </a>
              </div>
            </td>
          </tr>
          <!-- FOOTER -->
          <tr>
            <td align="center" bgcolor="#131838" style="padding:9px 18px;">
              <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center">
                <tr>
                  <!-- Globe -->
                  <td valign="middle" style="padding-right:8px;">
                    <img src="{{$siteUrl}}/public/images/globleimg.png" width="19" height="19" alt="" style="display:block;">
                  </td>

                  <!-- Website text -->
                  <td valign="middle"
                    style="font-size:13px; line-height:18px; color:#ffffff; font-family:Inter, Arial, sans-serif; padding-right:10px;">
                    Localists.com
                  </td>

                  <!-- Divider -->
                  <td valign="middle" style="padding:0 10px; font-size:13px; line-height:18px; color:#ffffff;">|</td>

                  <!-- Email icon -->
                  <td valign="middle" style="padding-right:8px;">
                    <img src="{{$siteUrl}}/public/images/vectorimg.png" width="18" height="14" alt="" style="display:block;">
                  </td>

                  <!-- Email text -->
                  <td valign="middle" style="font-size:13px; line-height:18px; font-family:Inter, Arial, sans-serif;">
                    <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}"
                      style="color:#ffffff; text-decoration:none;">
                      {{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}
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