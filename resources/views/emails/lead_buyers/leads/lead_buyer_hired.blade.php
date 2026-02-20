<!doctype html>
<html>

<head>
  <meta charset="UTF-8">
  <title>You've Purchased a Lead</title>
</head>

<body style="margin:0;padding:0;background:#f4f8fb;font-family:Inter,Arial,Helvetica,sans-serif;">

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

          <!-- BODY -->
          <tr>
            <td style="padding:5px 30px;">

              <p style="font-size:16px;margin:0 0 8px;">
                Hi <strong>{{ ucfirst($name) }}</strong>,
              </p>

              <p style="color:#253238;font-size:14px;line-height: 30px;">
                You have purchased a new lead<br>
                Congratulations — You have successfully purchased the <strong>{{ $service_name }}</strong> lead.
              </p>

              <!-- LEAD TAGS WITH IMAGES INSTEAD OF EMOJIS -->
              <div style="margin-bottom:18px;">
                @if($phone_verified)
                <span style="display:inline-block;background:#00AFE3;color:#ffffff;font-size:12px;font-weight:600;padding:6px 10px;border-radius:14px;margin:0 6px 6px 0;">
                  <img src="{{$siteUrl}}/public/images/icons/phonetop.png" width="14" height="14" style="vertical-align:middle;margin-right:4px;">Verified Phone
                </span>
                @endif
                @if($has_additional_details)
                <span style="display:inline-block;background:#00AFE3;color:#ffffff;font-size:12px;font-weight:600;padding:6px 10px;border-radius:14px;margin:0 6px 6px 0;">
                  <img src="{{$siteUrl}}/public/images/icons/document.png" width="14" height="14"
                    style="vertical-align:middle;margin-right:4px;">Additional details
                </span>
                @endif
                @if($is_frequent_user)
                <span style="display:inline-block;background:#00AFE3;color:#ffffff;font-size:12px;font-weight:600;padding:6px 10px;border-radius:14px;margin:0 6px 6px 0;">
                  <img src="{{$siteUrl}}/public/images/icons/star.png" width="14" height="14" style="vertical-align:middle;margin-right:4px;">Frequent
                  user
                </span>
                @endif
                @if($is_urgent)
                <span style="display:inline-block;background:#00AFE3;color:#ffffff;font-size:12px;font-weight:600;padding:6px 10px;border-radius:14px;margin:0 6px 6px 0;">
                  <img src="{{$siteUrl}}/public/images/icons/warning.png" width="14" height="14" style="vertical-align:middle;margin-right:4px;">Urgent
                </span>
                @endif
                @if($is_high_hiring)
                <span
                  style="display:inline-block;background:#00AFE3;color:#ffffff;font-size:12px;font-weight:600;padding:6px 10px;border-radius:14px;margin:0 6px 6px 0;">
                  <img src="{{$siteUrl}}/public/images/icons/user-check.png" width="14" height="14" style="vertical-align:middle;margin-right:4px;">High
                  hiring
                </span>
                @endif
              </div>

              <!-- LEAD DETAILS WITH BUTTON INSIDE -->
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:20px;">
                <tr>
                  <td style="background:rgba(227, 246, 252, 0.5); border-radius:10px; padding:17px 25px;">

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
                                {{ $credit_score }} credits
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
                              <td valign="middle" style="padding-left:6px; font-weight:600; font-size:14px; color:#253238;">
                                {{ $postcode }}
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
                              <td valign="middle" style="padding-left:6px; font-weight:600; font-size:14px; color:#253238;">
                                {{ $phone }}
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
                              <td valign="middle" style="padding-left:6px; font-weight:600; font-size:14px; color:#253238;">
                                {{ $email }}
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>

                    <!-- CTA BUTTON INSIDE BLUE BOX -->
                    <div style="text-align:center;">
                      <a href="{{ $postloginUrl }}/sellers/leads/my-responses" style="background:#ff9c2c;color:#ffffff;text-decoration:none;padding:8px 22px;border-radius:30px;font-size:15px; font-weight:bold;display:inline-block;">
                        Contact Lead Now
                      </a>
                    </div>

                  </td>
                </tr>
              </table>

              <!-- DETAILS SECTION -->
              @if(!empty($questionsAndAnswers))
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
                    <table width="100%" cellpadding="10" cellspacing="0" role="presentation"
                      style="background:#ffffff;">


                      @foreach ($questionsAndAnswers as $qa)
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


              <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:10px;">
                <tr>
                  <td style="padding:0 14px 20px 14px;">

                    <!-- Section Title -->
                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                      <tr>
                        <td align="center"
                          style="font-size:22px;font-weight:800;color:#00AFE3;padding-bottom:18px;letter-spacing:0.2px;">
                          How To Improve Conversion Rates
                        </td>
                      </tr>
                    </table>

                    <!-- TIP CARD (repeat for each tip) -->
                    <!-- Tip 1 -->
                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                      style="background:#E3F6FC;border-radius:12px;margin-bottom:14px;">
                      <tr>
                        <td style="padding:14px 16px;">
                          <!-- ICON + HEADLINE ROW -->



                          <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                              <td valign="middle" style="padding:0; font-size:0; line-height:0;">
                                <img src="{{$siteUrl}}/public/images/icons/speed.png" width="28" height="28"
                                  style="display:inline-block;vertical-align:middle;border:0;outline:none;margin:0;">
                                <span style="
                                    display:inline-block;
                                    vertical-align:middle;
                                    margin-left:8px;
                                    font-size:15px;
                                    font-weight:800;
                                    color:#00AFE3;
                                    line-height:1;">
                                  BE QUICK
                                </span>
                              </td>
                            </tr>
                          </table>



                          <!-- descriptive text (starts below the headline) -->
                          <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                            style="margin-top: 10px;">
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
                                Speed matters. The likelihood of connecting with a lead decreases rapidly the longer you
                                wait.
                                Research shows that reaching out within the first minute can increase your chances of
                                success
                                by up to 400% compared to waiting a full day.
                                <br><br>
                                Make response time your top priority — measure it in seconds, not minutes. Call inbound
                                leads
                                immediately and don’t let opportunities slip away.
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>

                    <!-- Tip 2 -->
                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                      style="background:#EDFCF8;border-radius:12px;margin-bottom:14px;">
                      <tr>
                        <td style="padding:14px 16px;">
                          <table width="100%" cellpadding="0" cellspacing="0" role="presentation">                            <tr>
                              <td valign="middle" style="padding:0; font-size:0; line-height:0;">
                                <img src="{{$siteUrl}}/public/images/icons/strategy.png" width="24" height="24"
                                  style="display:inline-block;vertical-align:middle;border:0;outline:none;margin:0;">
                                <span style="
                                    display:inline-block;
                                    vertical-align:middle;
                                    margin-left:8px;
                                    font-size:15px;
                                    font-weight:800;
                                    color:#00AFE3;
                                    line-height:1;">
                                  IMPROVE YOUR NO-CONTACT STRATEGY
                                </span>
                              </td>
                            </tr>
                          </table>
                          <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                            style="margin-top: 10px;">
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
                                You won’t connect with every lead on the first attempt. That’s why it’s important to
                                have a solid
                                strategy for when you don’t get through.
                                <br><br>
                                Best practice includes leaving a voicemail, sending a follow-up email, WhatsApp, and
                                giving
                                prospects a compelling reason to call back. Creating urgency increases the chance
                                they’ll re-engage.
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>

                    <!-- Tip 3 -->
                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                      style="background:#E3F6FC;border-radius:12px;">
                      <tr>
                        <td style="padding:14px 16px;">
                          <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                              <td valign="middle" style="padding:0; font-size:0; line-height:0;">
                                <img src="{{$siteUrl}}/public/images/icons/callbackcontent.png" width="23" height="22"
                                  style="display:inline-block;vertical-align:middle;border:0;outline:none;margin:0;">
                                <span style="
                                    display:inline-block;
                                    vertical-align:middle;
                                    margin-left:8px;
                                    font-size:15px;
                                    font-weight:800;
                                    color:#00AFE3;
                                    line-height:1;">
                                  CALL THEM BACK
                                </span>
                              </td>
                            </tr>
                          </table>

                          <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                            style="margin-top: 10px;">
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
                                Persistence pays off. Many tradespeople give up too early, but research suggests you
                                should attempt
                                to reach a prospect at least six times before moving on.
                                <br><br>
                                Vary the times of your calls — mornings, afternoons, and evenings — to increase your
                                chance of
                                connecting. Always leave the door open for future conversations.
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

          <tr>
            <td align="center" style="padding:12px 16px; background:#f4f6f8;">
              <p style="margin:0; font-size:11px; line-height:16px; color:#6b7280; text-align:center;">
                Click here to 
                <a href="{{ url('/api/unsubscribe-status-update/' . $userId . '/user') }}"
                  style="color:#00AFE3; text-decoration:underline;font-weight:600;">
                  unsubscribe
                </a>  and we will remove you from our emailing list.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>

</html>