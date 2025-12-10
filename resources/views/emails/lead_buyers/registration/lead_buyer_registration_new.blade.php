<!DOCTYPE html>
<html lang="en">
  <head>
    <title>exported project</title>
    <meta property="og:title" content="exported project" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta charset="utf-8" />
    <meta property="twitter:card" content="summary_large_image" />

    <style data-tag="reset-style-sheet">
      html { line-height: 1.15; } body { margin: 0; } * { box-sizing: border-box; border-width: 0; border-style: solid; -webkit-font-smoothing: antialiased; } p, li, ul, pre, div, h1, h2, h3, h4, h5, h6, figure, blockquote, figcaption { margin: 0; padding: 0; } button { background-color: transparent; } button, input, optgroup, select, textarea { font-family: inherit; font-size: 100%; line-height: 1.15; margin: 0; } button, select { text-transform: none; } button, [type="button"], [type="reset"], [type="submit"] { -webkit-appearance: button; color: inherit; } button::-moz-focus-inner, [type="button"]::-moz-focus-inner, [type="reset"]::-moz-focus-inner, [type="submit"]::-moz-focus-inner { border-style: none; padding: 0; } button:-moz-focus, [type="button"]:-moz-focus, [type="reset"]:-moz-focus, [type="submit"]:-moz-focus { outline: 1px dotted ButtonText; } a { color: inherit; text-decoration: inherit; } pre { white-space: normal; } input { padding: 2px 4px; } img { display: block; } details { display: block; margin: 0; padding: 0; } summary::-webkit-details-marker { display: none; } [data-thq="accordion"] [data-thq="accordion-content"] { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-in-out; padding: 0; } [data-thq="accordion"] details[data-thq="accordion-trigger"][open] + [data-thq="accordion-content"] { max-height: 1000vh; } details[data-thq="accordion-trigger"][open] summary [data-thq="accordion-icon"] { transform: rotate(180deg); } html { scroll-behavior: smooth; }
    </style>
    <style data-tag="default-style-sheet">
      html { font-family: Inter; font-size: 16px; } body { font-weight: 400; font-style: normal; text-decoration: none; text-transform: none; letter-spacing: normal; line-height: 1.15; color: #252832; background: #f9f9fa; fill: #252832; }
    </style>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" data-tag="font" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=STIX+Two+Text:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap" data-tag="font" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" data-tag="font" />

    <style>
      .container { width: 100%; display: flex; overflow: auto; min-height: 100vh; align-items: center; flex-direction: column; }
      .main-frame { width: 100%; height: auto; display: flex; position: relative; align-items: flex-start; flex-shrink: 0; }
      .email-body { width: 600px; display: flex; flex-direction: column; align-items: center; background-color: #f9f9fa; margin: 0 auto; padding: 20px; gap: 20px; }
      .profile-created { width: 524px; height: 241px; overflow: hidden; background-color: #e3f6fc; padding: 20px; display: flex; flex-direction: column; justify-content: flex-start; }
      .profile-text { color: #252832; font-size: 20px; font-weight: 900; text-align: left; font-family: Inter; line-height: normal; margin-bottom: 10px; }
      .login-details { color: #00afe3; font-size: 16px; font-weight: 900; text-align: left; font-family: Inter; line-height: 15px; margin-bottom: 15px;margin-top: 15px; }
      .login-link { color: #00afe3; font-size: 12px; font-weight: 700; text-align: left; font-family: Inter; line-height: 15px; text-decoration: underline; margin-bottom: 15px; }
      .username-password { color: #252832; font-size: 12px; font-weight: 700; text-align: left; font-family: Inter; line-height: 22px; text-decoration: none; margin-bottom: 15px; }
      .magic-link { color: #00afe3; font-size: 12px; font-weight: 700; text-align: left; font-family: Inter; line-height: 15px; text-decoration: underline; }
      .login-header { width: 524px; height: 40px; border-radius: 5px; background-color: #00afe3; margin: 14px 37px 20px;display: flex;justify-content: center; align-items: center;color:#FFFFFF}
      .greeting { color: #252832; font-size: 16px;  text-align: left; font-family: Inter; line-height: 20px; width: 495px; }
      .welcome-para { color: #252832; font-size: 12px; font-weight: 500; text-align: left; font-family: Inter; line-height: 15px; width: 497px;  }
      .features-section { display: flex; flex-direction: column; gap: 20px; width: 524px; }
      .features-grid { display: flex; flex-direction: column; gap: 20px; }
      .feature-card { width: 524px; height: 237px; overflow: hidden; border-radius: 10px; background-color: #e3f6fc; position: relative; display: flex; flex-direction: column; }
      .feature-header { display: flex; gap: 10px; align-items: center; padding: 14px 0 0 11px; }
      .feature-icon { width: 36px; overflow: hidden; position: relative; }
      .feature-title { color: #00afe3; font-size: 16px; font-weight: 700; text-align: left; font-family: Inter; line-height: normal; }
      .feature-desc { display: flex; flex-direction: column; gap: 5px; padding: 0 11px; flex: 1; }
      .feature-text { color: #252832; font-size: 12px; font-weight: 500; text-align: left; font-family: Inter; line-height: 18px;margin-top: 10px; }
      .support-card, .request-card { width: 524px; /*padding: 15px 11px;*/ overflow: hidden; border-radius: 10px; display: flex; flex-direction: column; }
      .support-card { background-color: #edfcf8; }
      .request-card { background-color: #e3f6fc; }
      .credit-card { width: 524px; padding: 15px 11px; overflow: hidden; border-radius: 10px; background-color: #edfcf8; display: flex; flex-direction: column; gap: 10px; }
      .credit-content { display: flex; flex-direction: column; gap: 10px; width: 502px; flex: 1; }
      .credit-header { display: flex; gap: 10px; align-items: center; }
      .add-button { display: flex; padding: 8px 15px; overflow: hidden; align-items: center; border-radius: 81px; flex-direction: column; justify-content: center; background-color: #0fc77b; gap: 8px; align-self: flex-start; margin-top: auto; }
      .add-text { color: #fff; font-size: 15px; font-weight: 700; text-align: center; font-family: Inter; line-height: normal; }
      .referral-box { width: 524px; height: 189px; overflow: hidden; border-radius: 10px; background-color: #00afe3; display: flex; align-items: center; justify-content: center; }
      .referral-content { display: flex; flex-direction: column; gap: 10px; align-items: center; padding: 16px 0 0 10px; width: 476px;border: 2px solid #ffffff;  border-radius: 10px;   padding: 20px; }
      .referral-title { color: #fff; font-size: 25px; font-weight: 700; text-align: center; font-family: Inter; line-height: normal; width: 100%; }
      .referral-text { color: #fff; font-size: 12px;  text-align: center; font-family: Inter; line-height: 18px; width: 100%; }
      .closing-section { display: flex; gap: 10px; padding: 24px 55px; align-items: center; justify-content: center; background-color: #e3f6fc; width: 600px; height: 165px; flex-direction: column; }
      .closing-text { color: #000; font-size: 14px; font-weight: 700; text-align: center; font-family: Inter; line-height: normal; width: 377px; margin: 27px 0 0 0; }
      .logo-img { width: 148px; height: 36px; margin: 0; }
      .footer { display: flex; padding: 8px 111px; overflow: hidden; align-items: center; justify-content: center; background-color: #111637; width: 600px; height: 37px; gap: 10px; }
      .footer-left { display: flex; gap: 5px; align-items: center; }
      .globe-icon { width: 19px; height: 19px; }
      .company-name { color: #fff; font-size: 12px; font-weight: 500; text-align: left; font-family: Inter; line-height: 15px; width: 86px; }
      .footer-right { display: flex; gap: 9px; align-items: center; }
      .arrow-icon { width: 18px; height: 14px; }
      .contact-email { color: #fff; font-size: 12px; font-weight: 500; text-align: left; font-family: Inter; line-height: 15px; width: 160px; }
      .exclusive-section { display: flex; position: relative; align-items: flex-start; height: auto; margin: 20px 0; }
      .exclusive-card { display: flex; align-items: center; justify-content: space-between; width: 100%; height: auto; min-height: 161px; }
      .text-section { display: flex; flex-direction: column; gap: 4px; padding: 13px 23px; overflow: hidden; border-radius: 12px 0 0 12px; background-color: #00afe3; width: 231px; height: auto; justify-content: flex-start; min-height: 180px; }
      .exclusive-text { color: #fff; font-size: 12px; font-weight: 700; text-align: left; font-family: Inter; line-height: 14px;  }
      .contact-btn { display: flex; padding: 8px 5px; overflow: hidden; align-items: center; border-radius: 26px; flex-direction: column; justify-content: center; background-color: #252832; gap: 2px; margin-top: 10px; width: 30%; min-width: 80px; align-self: flex-start; }
      .contact-text { color: #fff; font-size: 12px; font-weight: 700; text-align: center; font-family: Inter; line-height: normal; }
      .sidebar-img { width: 192px; height: 180px; border-radius: 0 12px 12px 0; object-fit: cover; }
      .guide-box {
  width: 524px;
  height: auto;
  display: flex;
  border-radius: 10px;
  overflow: hidden;
  margin-top: 10px;
}

/* LEFT ORANGE PANEL */
.guide-left {
  width: 35%;
  background-color: #f86c33;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20px 10px;
  text-align: center;
}

.guide-left-icon {
  width: 62px;
  height: 62px;
}

.guide-left-title {
  margin-top: 8px;
  font-size: 16px;
  font-weight: 700;
  color: #253238;
  font-family: Inter;
}

/* RIGHT BLUE GRADIENT PANEL */
.guide-right {
  width: 65%;
  padding: 18px 18px;
  background: linear-gradient(90deg, #00aee4 0%, #1dc8ff 100%);
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 8px;
}

.guide-right-text {
  color: #252832;
  font-size: 12px;
  line-height: 18px;
  font-weight: 500;
  font-family: Inter;
}

.guide-right-urgent {
  color: #ffffff;
  font-size: 12px;
  line-height: 17px;
  font-weight: 700;
  font-family: Inter;
}

/* MOBILE FIX */
@media (max-width: 480px) {
  .guide-box {
    flex-direction: column;
    width: 100%;
    height: auto;
  }

  .guide-left,
  .guide-right {
    width: 100%;
  }

  .guide-left {
    padding: 15px 0;
  }

  .guide-right {
    padding: 15px;
  }
}

      .urgent-text { color: #fff; font-size: 12px; font-weight: 700; text-align: left; font-family: Inter; line-height: 18px; width: 100%; }
      @media (max-width: 479px) { 
        .add-button { height: 40px; } 
        .email-body { width: 100%; padding: 10px; gap: 15px; } 
        .features-section, .referral-box, .closing-section, .footer, .exclusive-section, .guide-section { width: 100%; max-width: 524px; margin: 0 auto; } 
        .profile-created, .login-header { width: 100%; max-width: 524px; margin: 0 auto; } 
        .greeting, .welcome-para { width: 100%; max-width: 495px; margin: 0 auto; } 
        .feature-card, .support-card, .request-card, .credit-card { width: 100%; max-width: 524px; } 
        .referral-box { height: auto; padding: 20px; text-align: center; } 
        .referral-content { width: 100%; padding: 0; } 
        .closing-section { width: 100%; max-width: 600px; height: auto; padding: 20px; flex-direction: column; gap: 10px; } 
        .closing-text { width: 100%; margin: 0; } 
        .footer { width: 100%; max-width: 600px; padding: 8px 20px; justify-content: space-between; } 
        .exclusive-section { height: auto; flex-direction: column; } 
        .exclusive-card { flex-direction: column; height: auto; gap: 10px; } 
        .text-section { width: 100%; height: auto; border-radius: 12px; justify-content: flex-start; padding: 20px; } 
        .sidebar-img { width: 100%; height: 150px; border-radius: 12px; margin: 10px 0 0 0; } 
        .contact-btn { margin: 10px auto 0; align-self: center; width: 100%; max-width: 150px; min-width: auto; } 
        .guide-section { width: 100%; max-width: 524px; height: auto; flex-direction: column; align-items: flex-start; padding: 20px; gap: 15px; } 
        .guide-icon-section { width: auto; } 
        .guide-desc { width: 100%; } 
        .login-header { margin: 10px auto 20px; } 
      }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="main-frame">
        <div class="email-body">
          <div class="login-header"><img src="{{$baseUrl}}/assets/localist_logo_1.png" alt="Localists.com" style="height: 25px;" /></div>
          <p class="greeting">Hi <span style="font-weight: 700;">{{ ucfirst($name) }}</span>,</p>
          <p class="welcome-para">Welcome to Localists.com! We’re excited to have you on board and to start building a long-term relationship that helps you win more work with less effort.</p>
          
          <div class="profile-created">
            <p class="profile-text">Your profile has now been created, and you’re ready to start accessing quality leads.&nbsp;</p>
            <p class="login-details">Your Login Details:</p>
            <p class="login-link"><a href="{{$baseUrl}}/en/gb/login">Log in to your account</a></p>
            <p class="username-password">Username:&nbsp;{{$email}}<br />Password: &nbsp;{{$password}}</p>
            <p class="magic-link"><a href="{{ $baseUrl }}/en/gb/login?client_id={{base64_encode($token)}}">Login via Magic Link (one click login)</a></p>
          </div>

          <div class="guide-box">
          <div class="guide-left">
            <img src="{{$siteUrl}}/public/images/helpful.png" class="guide-left-icon" />
            <p class="guide-left-title">Helpful Guide</p>
          </div>

          <div class="guide-right">
            <p class="guide-right-text">
              We’ve attached a document that explains how to maximise your returns with
              Localists.com. It’s full of practical tips to help you get the most from
              your credits and leads.
            </p>

            <p class="guide-right-urgent">
              YOU NEED TO CALL ALL LEADS AS SOON AS THEY COME IN. 400% INCREASE IN CLOSE
              RATES IF PHONED WITHIN MINUTES RATHER THAN 24 HOURS.
            </p>
          </div>
        </div>
         
          <div class="features-section">
            <div class="features-grid">
              <div class="feature-card">
                <div class="feature-header">
                  <div class="feature-icon">
                    <img src="{{$siteUrl}}/public/images/we-do-marketing.png" alt="Marketing Icon" style="width: 36px; height: 36px;" />
                  </div>
                  <span class="feature-title">We Do Your Marketing for You</span>
                </div>
                <div class="feature-desc">
                  <p class="feature-text">At Localists.com, we handle all the marketing work on your behalf. Our team sets budgets specifically for your service types in your location, ensuring you get targeted exposure and high-quality leads without lifting a finger. This means you can focus on winning jobs while we take care of generating the opportunities. You then choose only the jobs you want. Once you purchase your first credit pack, you will receive an email from our marketing the lead types and locations you are most interested in seeing a higher volume of leads for. Within 72 hours of your confirmation this will be implemented by the marketing team and you will see a steady stream of leads coming in tailored to your exact needs.&nbsp;</p>
                </div>
              </div>
              <div class="support-card">
                <div class="feature-content">
                  <div class="feature-header">
                    <img src="{{$siteUrl}}/public/images/your-dedicated-account.svg" alt="" style="width: 36px; height: 36px;"/>
                    <span class="feature-title">Your Dedicated Account Manager</span>
                  </div>
                  <div class="feature-desc">
                    <p class="feature-text">&nbsp;To make sure you always have the support you need, we’ve assigned you a dedicated account manager. They’re your go-to contact for any queries, guidance, or advice—whether it’s about leads, credits, or strategy. You’ll never have to figure things out alone. Your account manager will be in touch shortly to run through your profile and the platform with you.</p>
                  </div>
                </div>
              </div>
              <div class="request-card">
                <div class="feature-content">
                  <div class="feature-header">
                    <div class="feature-icon">
                      <img src="{{$siteUrl}}/public/images/request-reply.png" alt="Reply Icon" style="width: 36px; height: 36px;" />
                      
                    </div>
                    <span class="feature-title">Request Reply</span>
                  </div>
                  <div class="feature-desc">
                    <p class="feature-text">We make life easier by connecting you with high‑intent customers who choose your company directly while live on our platform. Keep your profile up to date and respond quickly to calls, messages or emails to turn every request into a win.</p>
                  </div>
                </div>
              </div>
              <div class="credit-card">
                <div class="credit-content">
                  <div class="credit-header">
                    <div class="feature-icon">
                      <img src="{{$siteUrl}}/public/images/credit-card.png" alt="Credit Icon" style="width: 36px; " />
                    </div>
                    <span class="feature-title">Credit Expiry</span>
                  </div>
                  <div class="feature-desc" style="padding: 0 0px;">
                    <p class="feature-text" style="margin-top: 1px;">To give you time plenty of time to utilise the platform, your credits are valid for 12 months, giving you more time to experience our high quality, high intent leads&nbsp;without any pressure. Add some credit to your account now.&nbsp;</p>
                  </div>
                </div>
                <a href="{{$baseUrl}}/en/gb/settings/billing/payment-details" class="add-button">
                  <span class="add-text">Add Card/Credits</span>
              </a>
              </div>
            </div>
          </div>
          <div class="referral-box">
            <div class="referral-content">
              <span class="referral-title">Referral Scheme</span>
              <span class="referral-text">10% commission on any credit pack sales. Recurring income from renewals. On average, local professionals renew at a rate of 80%, meaning ongoing passive income payable every month.</span>
            </div>
          </div>
          <div class="exclusive-section">
            <div class="exclusive-card">
              <div class="text-section">
                <div class="exclusive-text">We’d also love to know if you’re interested in EXCLUSIVE LEADS a powerful way to secure more jobs with minimal competition. Please speak to your account manager to find out more information regarding our <span style="color: #253238;">exclusive leads.</span></div>
                <a href="{{$baseUrl}}/en/gb/contact-us" class="contact-btn">
                  <span class="contact-text">Contact Us</span>
                 </a>
              </div>
              <img src="{{$siteUrl}}/public/images/contactus.png" alt="Garden Maintenance" class="sidebar-img" />
            </div>
          </div>
          <div class="closing-section">
            <span class="closing-text">We look forward to helping you grow your business with Localists.com.</span>
            <img src="{{$baseUrl}}/assets/localist_logo_1.png" alt="Logo" class="logo-img" />
          </div>
          <div class="footer">
            <div class="footer-left">
              <img src="{{$siteUrl}}/public/images/globle7745-xeg.svg" alt="Globe" class="globe-icon" />
              <span class="company-name">&nbsp;Localists.com</span>
            </div>
            <div class="footer-right">
              <img src="{{$siteUrl}}/public/images/vector7745-xm1q.svg" alt="Arrow" class="arrow-icon" />
              <span class="contact-email">contact@localists.com</span>
            </div>
          </div>
          
        </div>
      </div>
    </div>
  </body>
</html>