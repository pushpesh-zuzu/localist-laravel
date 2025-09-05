<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>New Contact Form Submission</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif; background-color:#f9f9f9;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f9f9f9; padding:20px;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,0.05);">
          <tr>
            <td style="background-color:#00aee6; padding:20px; text-align:center; color:#ffffff; font-size:22px; font-weight:bold;">
              New Contact Form Submission
            </td>
          </tr>
          <tr>
            <td style="padding:30px; color:#333333; font-size:16px; line-height:1.6;">
              <p>Hello Admin,</p>
              <p>You’ve received a new contact form submission from the <strong>Localists</strong> website:</p>
              
              <table cellpadding="8" cellspacing="0" border="0" style="width:100%; border-collapse:collapse; margin-top:15px;">
                <tr>
                  <td style="background-color:#f2f2f2; width:150px; font-weight:bold;">Full Name</td>
                  <td>{{ $fullName }}</td>
                </tr>
                <tr>
                  <td style="background-color:#f2f2f2; font-weight:bold;">Email</td>
                  <td>{{ $email }}</td>
                </tr>
                <tr>
                  <td style="background-color:#f2f2f2; font-weight:bold;">Phone</td>
                  <td>{{ $phone }}</td>
                </tr>
                <tr>
                  <td style="background-color:#f2f2f2; font-weight:bold;">User Type</td>
                  <td>{{ $userType == 1 ? 'Professional' : 'Customer' }}</td>
                </tr>
                <tr>
                  <td style="background-color:#f2f2f2; font-weight:bold;">Message</td>
                  <td>{{ $user_message }}</td>
                </tr>
              </table>

              <p style="margin-top:25px;">Please follow up with the user at your earliest convenience.</p>
              <p style="margin-top:25px;">Best regards,<br><strong>Localists Website</strong></p>
            </td>
          </tr>
          <tr>
            <td style="background-color:#f2f2f2; text-align:center; padding:15px; font-size:13px; color:#777;">
              © 2025 Localists. This is an automated notification.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
