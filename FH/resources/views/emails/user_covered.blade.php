<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>First Health - Area Covered</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f7f7f7;
            color: #333;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #dddddd;
            border-radius: 8px;
            overflow: hidden;
        }
        .header {
            background-color: #0073e6;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header img {
            max-width: 120px;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 20px;
        }
        .content h2 {
            color: #0073e6;
            font-size: 20px;
        }
        .content p {
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 20px;
        }
        .content ul {
            list-style: none;
            padding: 0;
        }
        .content ul li {
            background: url('https://img.icons8.com/ios-filled/50/0073e6/checkmark.png') no-repeat left center;
            padding-left: 30px;
            margin-bottom: 10px;
        }
        .social-icons {
            text-align: center;
            margin-top: 20px;
        }
        .social-icons img {
            margin: 0 10px;
            width: 24px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #777;
            padding: 10px;
            background-color: #f1f1f1;
            margin-top: 20px;
        }
        .footer a {
            color: #0073e6;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <img src="{{ asset('images/logo-firsthealth.png') }}" alt="First Health Logo">
            <h1>Great News! Your Area is Now Covered! 🎉</h1>
        </div>
        <div class="content">
            <p>Hi there,</p>
            <p>
                Update your membership with First Health today and enjoy unbeatable benefits starting from just 
                <strong>RM150 per user</strong>! 🚑
            </p>
            <h2>✨ What’s Included:</h2>
            <ul>
                <li>2 Emergency Trips</li>
                <li>2 Non-Emergency Trips</li>
            </ul>
            <p>
                Don’t miss out on this opportunity to have First Health at your service whenever you need it!
            </p>
        </div>
        <div class="social-icons">
           <a href="https://www.facebook.com/people/First-Health-Assist/61574147188798/"><img src="https://cdn-icons-png.flaticon.com/24/733/733547.png" alt="Facebook"></a>
            <a href="https://www.instagram.com/firsthealthassist/"><img src="https://cdn-icons-png.flaticon.com/24/2111/2111463.png" alt="Instagram"></a>
        </div>
        <div class="footer">
            <p>First Health<br>Copyright 2024 First Health. All rights reserved.</p>
            <p>If you have any questions, reach out to us at: <a href="mailto:xxx@firsthealthassist.com">xxx@firsthealthassist.com</a></p>
            <p>This is a no-reply email. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
