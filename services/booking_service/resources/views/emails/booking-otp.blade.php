<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking OTP Verification</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #334155; margin: 0; padding: 0; background-color: #f8fafc; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f8fafc; padding: 40px 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        .header { background-color: #ffffff; padding: 30px 20px; text-align: center; border-bottom: 4px solid #10b981; }
        .logo { max-height: 70px; margin-bottom: 15px; }
        .content { padding: 40px 35px; }
        .title { font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 20px; text-align: center; }
        .otp-code { font-size: 38px; font-weight: 800; color: #10b981; letter-spacing: 10px; margin: 30px 0; text-align: center; padding: 25px; background: #f0fdf4; border-radius: 12px; border: 2px dashed #10b981; }
        .info-card { background: #f8fafc; padding: 20px; border-radius: 10px; border-left: 4px solid #10b981; margin: 25px 0; }
        .footer { background-color: #f1f5f9; padding: 35px 20px; text-align: center; font-size: 13px; color: #64748b; line-height: 1.8; }
        .thanks-msg { font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 10px; }
        .expiry { color: #dc2626; font-size: 14px; font-weight: 600; text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <img src="https://via.placeholder.com/200x80?text=UNIVERSITY+LOGO" alt="University Logo" class="logo">
                <div style="font-size: 16px; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: 1.5px;">
                    Resource Reservation System
                </div>
            </div>
            <div class="content">
                <div class="title">🔐 Booking Verification</div>
                <p>Hello,</p>
                <p>Thank you for choosing our resource reservation system. To complete your booking, please use the secure verification code below:</p>
                
                <div class="otp-code">
                    {{ $otpCode }}
                </div>

                @if($bookingReference)
                <div class="info-card">
                    <div style="font-weight: 700; color: #475569; margin-bottom: 5px;">Booking Reference:</div>
                    <div style="font-size: 18px; font-weight: 700; color: #1e293b;">{{ $bookingReference }}</div>
                </div>
                @endif

                <div class="expiry">
                    🕒 This code will expire in {{ $expiresInMinutes }} minutes.
                </div>

                <p style="margin-top: 30px; font-size: 14px; color: #64748b; text-align: center;">
                    If you did not request this, please ignore this email or contact support.
                </p>
            </div>
            <div class="footer">
                <div class="thanks-msg">Thanks for reaching us.</div>
                <p>&copy; {{ date('Y') }} University of Sri Jayewardenepura. All rights reserved.</p>
                <p>Faculty of Engineering, Sri Lanka.</p>
            </div>
        </div>
    </div>
</body>
</html>