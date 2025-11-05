<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Localists - Relist My Quote</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&display=swap" rel="stylesheet">
</head>

<body style="margin:0;padding:0;background:#f5f8fa;font-family:'Poppins',Arial,sans-serif;">
    
    <!-- Main Container -->
    <div style="max-width:600px;margin:0 auto;background:#ffffff;">
        
        <!-- HERO SECTION -->
        <div style="display:flex;align-items:flex-start;">
            <!-- LEFT TEXT -->
            <div style="width: 75%; padding: 40px 0px 40px 40px;">
                <img src="{{ $baseUrl }}/assets/localist_logo_1.png" alt="localists" width="120" style="display:block;margin-bottom:24px;">
                <h1 style="margin:0;font-size:36px;line-height:1.1;font-weight:900;color:#001b35;">
                    NEED <span style="color:#00AFE3;">A HAND</span><br>
                    <span style="color:#00AFE3;">FINISHING</span><br>
                    YOUR PROJECT?
                </h1>
            </div>
            
            <!-- RIGHT IMAGE -->
            <div style="width: 50%; text-align:right;">
                <img src="{{ $baseUrl }}/assets/mask-group.png" alt="localists" width="280" style="display:block;margin-left:auto;max-width:100%;">
            </div>
        </div>

        <!-- MESSAGE SECTION -->
        <div style="padding:0px 40px 20px 40px;">
            <p style="margin:0 0 12px 0;font-weight:600;font-size:16px;color:#001b35;">
                Hi {{ $customerName }} ,
            </p>

            <div style="display:inline-block;background:#10a37f;color:#ffffff;padding:10px 16px;border-radius:6px;font-weight:600;margin-bottom:16px;">
                Looks like your quote request on Localists has expired
            </div>

            <p style="margin:14px 0 32px 0;color:#222222;font-size:16px;font-weight:700;line-height:22px;">
                Did you already hire someone, or would you like us to reconnect you with new verified professionals?
            </p>
        </div>

        <!-- CHOICES SECTION -->
        <div style="background:#00AFE3;padding:30px;text-align:center;">
            <h2 style="color:#ffffff;margin:0 0 20px 0;font-size:18px;font-weight:700;">
                Choose what fits best
            </h2>

            <div style="display:flex;justify-content:center;gap:12px;flex-wrap:wrap;">
                <!-- Choice 1 -->
                <div style="position:relative;background:#ffffff;border-radius:10px;width:140px;height:84px;text-align:center;color:#0d1b3d;font-weight:600;">
                    <img src="{{ $baseUrl }}/assets/icon1.png" style="position:absolute;left:50%;top:-10px;transform:translateX(-50%);height:41px;width:41px;" alt="icon">
                    <div style="position:absolute;bottom:0;left:0;right:0;padding:7px;">
                        <p style="font-size:12px;font-weight:800;line-height:16px;margin:0;">I hired someone</p>
                        <p style="font-size:12px;font-weight:800;line-height:16px;margin:0;">through Localists</p>
                    </div>
                </div>
                
                <!-- Choice 2 -->
                <div style="position:relative;background:#ffffff;border-radius:10px;width:140px;height:84px;text-align:center;color:#0d1b3d;font-weight:600;">
                    <img src="{{ $baseUrl }}/assets/icon2.png" style="position:absolute;left:50%;top:-10px;transform:translateX(-50%);height:41px;width:41px;" alt="icon">
                    <div style="position:absolute;bottom:0;left:0;right:0;padding:7px;">
                        <p style="font-size:12px;font-weight:800;line-height:16px;margin:0;">Still looking —</p>
                        <p style="font-size:12px;font-weight:800;line-height:16px;margin:0;">relist my quote</p>
                    </div>
                </div>
                
                <!-- Choice 3 -->
                <div style="position:relative;background:#ffffff;border-radius:10px;width:140px;height:84px;text-align:center;color:#0d1b3d;font-weight:600;">
                    <img src="{{ $baseUrl }}/assets/icon3.png" style="position:absolute;left:50%;top:-10px;transform:translateX(-50%);height:41px;width:41px;" alt="icon">
                    <div style="position:absolute;bottom:0;left:0;right:0;padding:7px;">
                        <p style="font-size:12px;font-weight:800;line-height:16px;margin:0;">I hired someone</p>
                        <p style="font-size:12px;font-weight:800;line-height:16px;margin:0;">elsewhere</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- BUTTON SECTION -->
        <div style="background:#ffffff;padding:30px 0;text-align:center;">
            <a href="{{ $baseUrl }}/login?client_id={{base64_encode($token)}}" style="background:#F76C32;color:#ffffff;font-weight:700;padding:14px 36px;border-radius:6px;text-decoration:none;display:inline-block;">
                Relist My Quote
            </a>
        </div>

        <!-- FOOTER -->
        <div style="background:#E8F6FB;text-align:center;padding:24px;">
            <p style="margin:0 0 10px 0;font-weight:700;color:#0d1b3d;">
                We'll make sure you get quick responses this time!
            </p>
            <img src="{{ $baseUrl }}/assets/localist_logo_1.png" alt="localists" width="110" style="display:block;margin:8px auto 0;">
        </div>

        <!-- BOTTOM BAR -->
        <div style="background:#0d1b3d;color:#ffffff;text-align:center;padding:14px;font-size:14px;">
            <img src="{{ $baseUrl }}/assets/globe.png" alt="Localists" width="16" style="vertical-align:middle;margin-right:6px;">
            <a href="{{ $baseUrl }}" style="color:#ffffff;text-decoration:none;">localists.com</a>
            &nbsp; | &nbsp;
            <img src="{{ $baseUrl }}/assets/email.png" alt="Email Icon" width="16" style="vertical-align:middle;margin-right:6px;">
            <a href="mailto:{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}" style="color:#ffffff;text-decoration:none;">{{\App\Helpers\CustomHelper::setting_value('website_email','contact@localists.com')}}</a>
        </div>
    </div>

</body>
</html>