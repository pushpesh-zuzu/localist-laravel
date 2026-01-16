<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Localists Email</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    /* MOBILE FIX */
    @media only screen and (max-width: 480px) {
      .container {
        width: 100% !important;
      }

      .right-img {
        height: 190px !important;
        /* blue box ke barabar */
        max-height: 190px !important;
        width: 100% !important;
      }


    }
  </style>
</head>

<body style="margin:0; padding:0; font-family:Inter, sans-serif; background-color:#f9f9fa;">
  <!-- Wrapper Table -->
  <table width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#f9f9fa">
    <tr>
      <td align="center" style="padding:20px 0;">
        <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style=" background-color:#f9f9fa; border-collapse:collapse;">
          <tr>
            <td style="padding:0;">
              <!-- Header -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#00AFE3">
                <tr>
                  <td align="center">

                    <!-- INNER CENTERED CONTENT -->
                    <table width="600" cellpadding="0" cellspacing="0" border="0">
                      <tr width="600">
                        <td align="center" width="600" style="padding:10px 0;">
                          <img src="{{$baseUrl}}/assets/localist_logo_1.png"
                            alt="Localists"
                            height="26"
                            style="display:block;">
                        </td>
                      </tr>
                    </table>

                  </td>
                </tr>
              </table>




              <!-- Greeting -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:20px;">
                <tr>
                  <td style="color:#252832; font-family:Inter, Arial, sans-serif; font-size:16px; font-weight:700; line-height:20px; padding:0 20px 5px 20px;">
                    Hi <span>{{ ucfirst($name) }}</span>,
                  </td>
                </tr>
                <tr>
                  <td style="color:#252832; font-family:Inter, Arial, sans-serif; font-size:13px; font-weight:500; line-height:15px; padding:10px 20px 0 20px;">
                    Welcome to Localists.com! We’re excited to have you on board and to start building a long-term relationship that helps you win more work with less effort.
                  </td>
                </tr>
              </table>


              <!-- Profile Created Box -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#e3f6fc; border-radius:10px; margin-top:20px; padding:20px;">
                <tr>
                  <td>
                    <p style="color:#252832; font-size:20px; font-weight:900; margin:0 0 10px 0;">Your profile has now been created, and you’re ready to start accessing quality leads.</p>
                    <p style="color:#00afe3; font-size:16px; font-weight:900; margin:15px 0 5px;">Your Login Details:</p>
                    <p style="color:#00afe3; font-size:12px; font-weight:700; margin-bottom:15px;">
                      <a href="{{$baseUrl}}/en/gb/login" style="color:#00afe3; text-decoration:underline;">Log in to your account</a>
                    </p>
                    <p style="color:#252832; font-size:12px; font-weight:700; line-height:22px; margin-bottom:15px;">
                      Username: {{$email}}<br>Password: {{$password}}
                    </p>
                    <p style="color:#00afe3; font-size:12px; font-weight:700; line-height:15px;">
                      <a href="{{ $baseUrl }}/en/gb/login?client_id={{base64_encode($token)}}" style="color:#00afe3; text-decoration:underline;">Login via Magic Link (one click login)</a>
                    </p>
                  </td>
                </tr>
              </table>


              <!-- Guide Box -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="display:flex; margin-top:20px; width:100%; max-width:600px;">
                <tr>
                  <td style="width:35%; background-color:#f86c33; text-align:center; padding:20px 10px;">
                    <img src="{{$siteUrl}}/public/images/helpful.png" width="62" height="62" style="display:block; margin:0 auto;">
                    <p style="margin-top:8px; font-size:16px; font-weight:700; color:#253238;">Helpful Guide</p>
                  </td>
                  <td style="width:65%; padding:18px; background:linear-gradient(90deg, #00aee4 0%, #1dc8ff 100%); color:#252832; font-size:12px; line-height:18px; font-weight:500;">
                    <p>We’ve attached a document that explains how to maximise your returns with Localists.com. It’s full of practical tips to help you get the most from your credits and leads.</p>
                    <p style="color:#ffffff; font-weight:700; line-height:17px; margin-top:5px;">YOU NEED TO CALL ALL LEADS AS SOON AS THEY COME IN. 400% INCREASE IN CLOSE RATES IF PHONED WITHIN MINUTES RATHER THAN 24 HOURS.</p>
                  </td>
                </tr>
              </table>

              <!-- Features Section -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:20px;">

                <!-- Feature 1 -->
                <tr>
                  <td style="background-color:#e3f6fc; border-radius:10px; padding:18px 14px; display:block;">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                      <tr>
                        <td style="width:36px;"><img src="{{$siteUrl}}/public/images/we-do-marketing.png" width="36" height="36" alt="Marketing Icon" style="display:block;"></td>
                        <td style="padding-left:10px; color:#00afe3; font-size:16px; font-weight:700;">We Do Your Marketing for You</td>
                      </tr>
                    </table>
                    <p style="margin-top:10px; color:#252832; font-size:12px; font-weight:500; line-height:18px;">
                      At Localists.com, we handle all the marketing work on your behalf. Our team sets budgets specifically for your service types in your location, ensuring you get targeted exposure and high-quality leads without lifting a finger. This means you can focus on winning jobs while we take care of generating the opportunities. You then choose only the jobs you want. Once you purchase your first credit pack, you will receive an email from our marketing the lead types and locations you are most interested in seeing a higher volume of leads for. Within 72 hours of your confirmation this will be implemented by the marketing team and you will see a steady stream of leads coming in tailored to your exact needs.
                    </p>
                  </td>
                </tr>

                <!-- SPACER (20px) -->
                <tr>
                  <td height="20" style="font-size:0; line-height:0;">&nbsp;</td>
                </tr>

                <!-- Feature 2 -->
                <tr>
                  <td style="background-color:#edfcf8; border-radius:10px; padding:18px 14px; display:block;">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                      <tr>
                        <td style="width:36px;"><img src="{{$siteUrl}}/public/images/your-dedicated-account.png" width="36" height="36" alt="" style="display:block;"></td>
                        <td style="padding-left:10px; color:#00afe3; font-size:16px; font-weight:700;">Your Dedicated Account Manager</td>
                      </tr>
                    </table>
                    <p style="margin-top:10px; color:#252832; font-size:12px; font-weight:500; line-height:18px;">
                      To make sure you always have the support you need, we’ve assigned you a dedicated account manager. They’re your go-to contact for any queries, guidance, or advice—whether it’s about leads, credits, or strategy. You’ll never have to figure things out alone. Your account manager will be in touch shortly to run through your profile and the platform with you.
                    </p>
                  </td>
                </tr>

                <!-- SPACER (20px) -->
                <tr>
                  <td height="20" style="font-size:0; line-height:0;">&nbsp;</td>
                </tr>

                <!-- Feature 3 -->
                <tr>
                  <td style="background-color:#e3f6fc; border-radius:10px; padding:18px 14px; display:block;">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                      <tr>
                        <td style="width:36px;"><img src="{{$siteUrl}}/public/images/request-reply.png" width="36" height="36" alt="Reply Icon" style="display:block;"></td>
                        <td style="padding-left:10px; color:#00afe3; font-size:16px; font-weight:700;">Request Reply</td>
                      </tr>
                    </table>
                    <p style="margin-top:10px; color:#252832; font-size:12px; font-weight:500; line-height:18px;">
                      We make life easier by connecting you with high-intent customers who choose your company directly while live on our platform. Keep your profile up to date and respond quickly to calls, messages or emails to turn every request into a win.
                    </p>
                  </td>
                </tr>

                <!-- SPACER (20px) -->
                <tr>
                  <td height="20" style="font-size:0; line-height:0;">&nbsp;</td>
                </tr>

                <!-- Feature 4 -->
                <tr>
                  <td style="background-color:#edfcf8; border-radius:10px; padding:18px 14px; display:block;">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                      <tr>
                        <td style="width:36px;"><img src="{{$siteUrl}}/public/images/credit-card.png" width="36" alt="Credit Icon" style="display:block;"></td>
                        <td style="padding-left:10px; color:#00afe3; font-size:16px; font-weight:700;">Credit Expiry</td>
                      </tr>
                    </table>
                    <p style="margin-top:5px; color:#252832; font-size:12px; font-weight:500; line-height:18px;">
                      To give you plenty of time to utilise the platform, your credits are valid for 12 months, giving you more time to experience our high quality, high intent leads without any pressure. Add some credit to your account now.
                    </p>
                    <a href="{{$baseUrl}}/settings/billing/payment-details" style="display:inline-block; padding:8px 15px; background-color:#0fc77b; color:#fff; font-weight:700; text-decoration:none; border-radius:81px; margin-top:10px;">Add Card/Credits</a>
                  </td>
                </tr>

              </table>


              <!-- Referral Section -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#00afe3; border-radius:10px; padding:20px; margin-top:20px;">
                <tr>
                  <td style="text-align:center; color:#fff;border: 2px solid #ffffff;    border-radius: 10px;">
                    <p style="font-size:25px; font-weight:700; margin-bottom:10px;">Referral Scheme</p>
                    <p style="font-size:12px; line-height:18px;">10% commission on any credit pack sales. Recurring income from renewals. On average, local professionals renew at a rate of 80%, meaning ongoing passive income payable every month.</p>
                  </td>
                </tr>
              </table>

              <!-- Exclusive Leads Section -->


              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:10px auto; padding:0;">
                <tr>
                  <td align="center">
                    <table role="presentation" width="550" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto; background:#f7f7f7; border-radius:12px;">
                      <tr>
                        <!-- LEFT BOX -->
                        <td width="50%" style="background-color:#00afe3; border-radius:12px 0 0 12px; padding:22px 20px;">
                          <p style="color:#fff; font-size:12px; font-weight:700; line-height:16px; margin:0 0 10px 0;">
                            We’d also love to know if you're interested in EXCLUSIVE LEADS a powerful way to secure more jobs with minimal competition.
                            Please speak to your account manager to find out more information regarding our
                            <span style="color:#253238;">exclusive leads.</span>
                          </p>

                          <a href="{{$baseUrl}}/en/gb/contact-us"
                            style="display:inline-block; padding:8px 18px; background:#252832; color:#fff; font-weight:700; text-decoration:none; border-radius:26px;">
                            Contact Us
                          </a>
                        </td>

                        <!-- RIGHT IMAGE -->
                        <td width="50%"
                          background="{{$siteUrl}}/public/images/contactus.png"
                          style="background-repeat:no-repeat; background-position:center;  background-size:cover;   border-radius:0 12px 12px 0;">
                          &nbsp;
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>



              <!-- Closing Section -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#e3f6fc; border-radius:10px; text-align:center; margin-top:20px; padding:24px;">
                <tr>
                  <td>
                    <p style="color:#000; font-size:14px; font-weight:700; margin-bottom:27px;">We look forward to helping you grow your business with Localists.com.</p>
                    <img src="{{$baseUrl}}/assets/localist_logo_1.png" alt="Logo" style="height:36px; display:block; margin:0 auto;">
                  </td>
                </tr>
              </table>

              <!-- Footer -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#111637; margin-top:10px; padding:8px; border-radius:5px;">
                <tr>
                  <td align="center" style="color:#fff; font-size:12px; font-weight:500; font-family:Inter, sans-serif;">
                    <!-- Company + Email inline -->
                    <img src="{{$siteUrl}}/public/images/globleimg.png" width="19" height="19" style="vertical-align:middle;"> &nbsp;Localists.com
                    &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
                    <img src="{{$siteUrl}}/public/images/vectorimg.png" width="18" height="14" style="vertical-align:middle;"> &nbsp;contact@localists.com
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
                </a> and we will remove you from our emailing list.
              </p>
            </td>
          </tr>
        </table>
        </div>
</body>

</html>