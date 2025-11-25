<html><head>
    <meta charset="UTF-8">

    <meta content="width=device-width" name="viewport">
    <style>body { margin: 0; background-color: rgb(241, 242, 244); font-family: "Lato", Helvetica, Arial, sans-serif; font-size: 18px; line-height: 25px; color: rgb(74, 74, 74); -webkit-font-smoothing: antialiased }.email-wrap { width: 100%; background-color: rgb(241, 242, 244); padding: 32px 0 }.email-container { max-width: 600px; margin: 0 auto; padding: 0 16px }.logo { max-height: 50px; display: block; margin: 0 auto 20px auto }.btn { display: inline-block; background-color: rgb(51, 153, 255); color: rgb(255, 255, 255) !important; text-decoration: none; font-size: 14px; font-weight: bold; padding: 10px 18px; border-radius: 4px }h1 { font-size: 22px; font-weight: 600; color: rgb(51, 51, 51); margin: 0 0 10px 0; font-family: Helvetica, Arial, sans-serif }.highlight { color: rgb(0, 175, 227); margin-bottom: 16px; font-size: 15px; font-family: Helvetica, Arial, sans-serif }p { color: rgb(97, 105, 109); margin: 0 0 12px 0; font-family: Helvetica, Arial, sans-serif; font-size: 15px; line-height: 1.5 }a { color: rgb(0, 123, 255) }@media only screen and (max-width: 600px) {.email-container { width: 100% !important; padding: 0 12px !important } .btn { font-size: 16px !important; padding: 12px 20px !important } h1 { font-size: 20px !important } }</style>

</head><body><div style="word-wrap:break-word; word-break:break-word"><div style="word-wrap:break-word; word-break:break-word"><div style="word-wrap:break-word; word-break:break-word"><div style="word-wrap:break-word; word-break:break-word"><div style="font-family:Verdana, arial, Helvetica, sans-serif">


  <table width="100%" cellpadding="0" cellspacing="0" border="0" class="email-wrap" bgcolor="#f1f2f4">
    <tbody><tr>
      <td align="center">


        <table width="600" cellpadding="0" cellspacing="0" border="0" class="email-container" style="max-width:600px; width:100%">
          <tbody><tr>
            <td style="padding:0 16px 8px 16px; text-align:center">
              <img src="{{$baseUrl}}/assets/localist_logo_1.png" alt="Localists Logo" class="logo">
            </td>
          </tr>


          <tr>
            <td align="center">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="border-radius:4px; box-shadow:0 1px 3px rgba(0, 0, 0, 0.04)">
                <tbody><tr>
                  <td style="padding:20px">
                    <h1>Welcome to Localists, {{ $name }}</h1>
                    <div class="highlight">We're excited to start helping you grow your business!</div>
                    <p>We'll now email you targeted leads from new customers. Ensure you get the right leads by confirming your lead preferences now.</p>
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:8px">
                      <tbody><tr>
                        <td style="padding:0 16px">
                          <a href="{{$baseUrl}}/settings/leads/my-services" class="btn">Confirm lead preferences</a>
                        </td>
                      </tr>
                    </tbody></table>
                  </td>
                </tr>
              </tbody></table>
            </td>
          </tr>

          <tr><td style="height:18px; font-size:0; line-height:18px">&nbsp;</td></tr>


          <tr>
            <td align="center">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="border-radius:4px; box-shadow:0 1px 3px rgba(0, 0, 0, 0.04); overflow:hidden">

                <tbody><tr>
                  <td bgcolor="#d8edf8" style="padding:12px 20px; color:rgb(26, 88, 140); font-size:16px; font-weight:500; font-family:Helvetica, Arial, sans-serif">
                    Your account
                  </td>
                </tr>
                <tr>
                  <td style="padding:20px; font-family:Helvetica, Arial, sans-serif; font-size:15px; color:rgb(74, 74, 74)">
                    <p>You can log in to your account and manage your leads anytime:</p>
                    <p>
                      <strong>Email:</strong> {{$email}}<br>
                      <strong>Password:</strong><strong>{{$password}}</strong>
                    </p>
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:8px">
                      <tbody><tr>
                        <td style="padding:0 16px">
                          <a href="{{$baseUrl}}/login" class="btn" style="display:inline-block; text-decoration:none">Log in to Localists</a>
                        </td>
                      </tr>
                    </tbody></table>
                  </td>
                </tr>
              </tbody></table>
            </td>
          </tr>

          <tr><td style="height:18px; font-size:0; line-height:18px">&nbsp;</td></tr>


          <tr>
            <td align="center">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="border-radius:4px; box-shadow:0 1px 3px rgba(0, 0, 0, 0.04); overflow:hidden">
                <tbody><tr>
                  <td bgcolor="#d8edf8" style="padding:12px 20px; color:rgb(26, 88, 140); font-size:16px; font-weight:500; font-family:Helvetica, Arial, sans-serif">
                    Service and Locations
                  </td>
                </tr>
                <tr>
                  <td style="padding:20px; font-family:Helvetica, Arial, sans-serif; font-size:15px; color:rgb(74, 74, 74)">
                    <p>You have registered for following service(s) having {{$jobs}} jobs.</p>
                    <ul style="margin:0 0 12px 18px; padding:0">
                      @foreach($services as $s)
                        <li style="margin-bottom:6px">{{$s}}</li>
                      @endforeach
                    </ul>
                    <p>You can change anytime from <a href="{{$baseUrl}}/settings/leads/my-services">My Services</a> page</p>
                  </td>
                </tr>
              </tbody></table>
            </td>
          </tr>

          <tr><td style="height:18px; font-size:0; line-height:18px">&nbsp;</td></tr>


          <tr>
            <td align="center">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="border-radius:4px; box-shadow:0 1px 3px rgba(0, 0, 0, 0.04); overflow:hidden">
                <tbody><tr>
                  <td bgcolor="#d8edf8" style="padding:12px 20px; color:rgb(26, 88, 140); font-size:16px; font-weight:500; font-family:Helvetica, Arial, sans-serif">
                    How We Work
                  </td>
                </tr>
                <tr>
                  <td style="padding:18px 20px 24px 20px; font-family:Helvetica, Arial, sans-serif; font-size:15px; color:rgb(74, 74, 74)">

                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:12px">
                      <tbody><tr>
                        <td style="vertical-align:top; padding-right:12px; width:44px">
                          <div style="background-color:rgb(0, 173, 239); color:rgb(255, 255, 255); border-radius:50%; width:32px; text-align:center; line-height:32px; font-size:14px; font-family:Helvetica, Arial, sans-serif">1</div>
                        </td>
                        <td style="vertical-align:top">
                          <strong>Customers tell us what they need</strong><br>
                          <span>Local customers share the services they’re looking for by answering key questions relating to the service.</span>
                        </td>
                      </tr>
                    </tbody></table>

                    <hr style="border:none; border-bottom:1px solid rgb(238, 238, 238); margin:8px 0">


                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:12px 0">
                      <tbody><tr>
                        <td style="vertical-align:top; padding-right:12px; width:44px">
                          <div style="background-color:rgb(0, 173, 239); color:rgb(255, 255, 255); border-radius:50%; width:32px; height:32px; text-align:center; line-height:32px; font-size:14px; font-family:Helvetica, Arial, sans-serif">2</div>
                        </td>
                        <td style="vertical-align:top">
                          <strong>Localists.com finds the right leads for you</strong><br>
                          <span>We match your business with leads that fit your services and location, delivered instantly to your inbox and dashboard.</span>
                        </td>
                      </tr>
                    </tbody></table>

                    <hr style="border:none; border-bottom:1px solid rgb(238, 238, 238); margin:8px 0">


                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:12px 0">
                      <tbody><tr>
                        <td style="vertical-align:top; padding-right:12px; width:44px">
                          <div style="background-color:rgb(0, 173, 239); color:rgb(255, 255, 255); border-radius:50%; width:32px; height:32px; text-align:center; line-height:32px; font-size:14px; font-family:Helvetica, Arial, sans-serif">3</div>
                        </td>
                        <td style="vertical-align:top">
                          <strong>You review and select your leads</strong><br>
                          <span>See full customer details straight away and choose the opportunities that work best for your business.</span>
                        </td>
                      </tr>
                    </tbody></table>

                    <hr style="border:none; border-bottom:1px solid rgb(238, 238, 238); margin:8px 0">


                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:12px 0">
                      <tbody><tr>
                        <td style="vertical-align:top; padding-right:12px; width:44px">
                          <div style="background-color:rgb(0, 173, 239); color:rgb(255, 255, 255); border-radius:50%; width:32px; height:32px; text-align:center; line-height:32px; font-size:14px; font-family:Helvetica, Arial, sans-serif">4</div>
                        </td>
                        <td style="vertical-align:top">
                          <strong>You connect with the customer directly</strong><br>
                          <span>Reach out by phone or email to introduce your services and secure new business.</span>
                        </td>
                      </tr>
                    </tbody></table>

                    <hr style="border:none; border-bottom:1px solid rgb(238, 238, 238); margin:8px 0">


                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:12px 0">
                      <tbody><tr>
                        <td style="vertical-align:top; padding-right:12px; width:44px">
                          <div style="background-color:rgb(0, 173, 239); color:rgb(255, 255, 255); border-radius:50%; width:32px; height:32px; text-align:center; line-height:32px; font-size:14px; font-family:Helvetica, Arial, sans-serif">5</div>
                        </td>
                        <td style="vertical-align:top">
                          <strong>You win new work — no hassle</strong><br>
                          <span>No hidden costs or long-term commitment. There are no commissions or extra costs — just a clear, simple way to grow your business through Localists.com.</span>
                        </td>
                      </tr>
                    </tbody></table>

                  </td>
                </tr>
              </tbody></table>
            </td>
          </tr>

          <tr><td style="height:18px; font-size:0; line-height:18px">&nbsp;</td></tr>


          <tr>
            <td align="center">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="border-radius:4px; box-shadow:0 1px 3px rgba(0, 0, 0, 0.04); overflow:hidden">
                <tbody><tr>
                  <td bgcolor="#d8edf8" style="padding:12px 20px; color:rgb(26, 88, 140); font-size:16px; font-weight:500; font-family:Helvetica, Arial, sans-serif">
                    Important Pages
                  </td>
                </tr>
                <tr>
                  <td style="padding:20px; font-family:Helvetica, Arial, sans-serif; font-size:15px; color:rgb(74, 74, 74)">
                    <ol style="margin:0 0 12px 18px; padding:0">
                      <li><a href="{{$baseUrl}}/sellers/dashboard">Dashboard</a></li>
                      <li><a href="{{$baseUrl}}/sellers/leads">Leads</a></li>
                      <li><a href="{{$baseUrl}}/settings/profile/my-profile">My Profile</a></li>
                      <li><a href="{{$baseUrl}}/settings/leads/my-services">My Services</a></li>
                      <li><a href="{{$baseUrl}}/settings/billing/my-credits">My Credits</a></li>
                      <li><a href="{{$baseUrl}}/settings/billing/invoice-billing-details">Invoices &amp; Billing Details</a></li>
                    </ol>

                    <p>You currently have <strong>0 credits</strong> in your account. To start receiving quality leads and allow our system to auto-bid on your behalf please purchase credits now.</p>

                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:8px">
                      <tbody><tr>
                        <td style="padding:0 16px">
                          <a href="{{$baseUrl}}/settings/billing/my-credits" class="btn">Contact Lead Now</a>
                        </td>
                      </tr>
                    </tbody></table>

                    <p style="margin-top:12px">You can also call our customer support team at
                      <a>
                        {{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}
                      </a>.
                    </p>

                    <p>Kind Regards,<br>Localists Team</p>
                  </td>
                </tr>
              </tbody></table>
            </td>
          </tr>

          <tr><td style="height:20px; font-size:0; line-height:20px">&nbsp;</td></tr>


          <tr>
            <td align="center" style="padding:0 16px 40px 16px; font-family:Helvetica, Arial, sans-serif; color:rgb(102, 102, 102); font-size:13px">
              Manage your email preferences <a href="{{$baseUrl}}/settings/notifications/e-mail-notification">here</a>.<br>
              {{\App\Helpers\CustomHelper::setting_value('website_address','')}}
            </td>
          </tr>

        </tbody></table>


      </td>
    </tr>
  </tbody></table>


</div></div></div></div></div></body></html>
