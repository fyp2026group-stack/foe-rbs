<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Booking Request</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #334155; margin: 0; padding: 0; background-color: #f8fafc; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f8fafc; padding: 40px 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        .header { background-color: #ffffff; padding: 30px 20px; text-align: center; border-bottom: 4px solid #10b981; }
        .logo { max-height: 70px; margin-bottom: 15px; }
        .content { padding: 40px 35px; }
        .title { font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 20px; }
        .info-list { background: #f8fafc; padding: 25px; border-radius: 12px; margin: 25px 0; }
        .info-item { margin-bottom: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; }
        .info-item:last-child { border-bottom: none; }
        .label { font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 5px; }
        .value { font-size: 16px; font-weight: 600; color: #1e293b; }
        .btn { display: inline-block; background-color: #10b981; color: #ffffff !important; padding: 12px 25px; border-radius: 8px; text-decoration: none; font-weight: 700; margin-top: 20px; }
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
                <div class="title">📋 New Booking Request</div>
                <p>Hello Admin,</p>
                <p>A new guest booking has been requested and requires your verification.</p>
                
                <div class="info-list">
                    <div class="info-item">
                        <div class="label">Booking Reference</div>
                        <div class="value">{{ $booking->booking_reference }}</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Date & Time</div>
                        <div class="value">{{ $booking->booking_date->format('Y-m-d') }} | {{ $booking->start_time }} - {{ $booking->end_time }}</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Requested By</div>
                        <div class="value">{{ $booking->user_email }}</div>
                    </div>
                </div>

                <div style="text-align: center;">
                    <a href="{{ config('app.frontend_url', 'http://localhost:5173') . '/admin/dashboard' }}" class="btn">Review in Dashboard</a>
                </div>
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
