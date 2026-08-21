<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Status Update</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #334155; margin: 0; padding: 0; background-color: #f8fafc; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f8fafc; padding: 40px 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        .header { background-color: #ffffff; padding: 30px 20px; text-align: center; border-bottom: 4px solid #10b981; }
        .logo { max-height: 70px; margin-bottom: 15px; }
        .content { padding: 40px 35px; }
        .title { font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 20px; }
        .status-pill { display: inline-block; background-color: #ecfdf5; color: #065f46; padding: 8px 16px; border-radius: 50px; font-weight: 700; text-transform: uppercase; font-size: 14px; margin: 10px 0; }
        .info-list { background: #f8fafc; padding: 25px; border-radius: 12px; margin: 25px 0; }
        .info-item { margin-bottom: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; }
        .info-item:last-child { border-bottom: none; }
        .label { font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 5px; }
        .value { font-size: 16px; font-weight: 600; color: #1e293b; }
        .footer { background-color: #f1f5f9; padding: 35px 20px; text-align: center; font-size: 13px; color: #64748b; line-height: 1.8; }
        .thanks-msg { font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 10px; }
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
                <div class="title">🔔 Booking Status Update</div>
                <p>Hello,</p>
                <p>We are writing to inform you that your booking status has been updated:</p>
                
                <div style="text-align: center;">
                    <div class="status-pill">{{ $booking->status }}</div>
                </div>

                <div class="info-list">
                    <div class="info-item">
                        <div class="label">Booking Reference</div>
                        <div class="value">{{ $booking->booking_reference }}</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Date & Time</div>
                        <div class="value">{{ $booking->booking_date->format('Y-m-d') }} | {{ $booking->start_time }} - {{ $booking->end_time }}</div>
                    </div>
                </div>

                <p style="margin-top: 20px;">If you have any questions, please contact our administrative office.</p>
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
