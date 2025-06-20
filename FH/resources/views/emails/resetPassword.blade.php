
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your First Health Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f7f7f7;
        }
        .email-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .header {
            text-align: center;
            padding: 10px 0;
        }
        .header img {
            width: 150px; /* Adjust as necessary */
        }
        .content {
            padding: 20px;
        }
        .content h1 {
            color: #333;
        }
        .content p {
            color: #555;
            line-height: 1.6;
        }
        .reset-button {
            text-align: center;
            margin: 20px 0;
        }
        .reset-button a {
            background-color: #007bff;
            color: #ffffff;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
        }
        .reset-button a:hover {
            background-color: #0056b3;
        }
        .footer {
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #888;
        }
        .social-media {
            text-align: center;
            margin: 20px 0;
        }
        .social-media img {
            width: 30px; /* Adjust as necessary */
            margin: 0 10px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Logo -->
        <div class="header">
            <img src="{{ asset('images/logo-firsthealth.png') }}" alt="First Health Logo">
        </div>

        <!-- Email Content -->
        <div class="content">
            <h1>Refresh Your Security: Reset Your First Health Password Now</h1>
            <p>Hi there,</p>
            <p>We received a request to reset your password for your First Health account. No worries, we’re here to help!</p>
            <p>To reset your password, please click the link below:</p>

            <!-- Reset Button -->
            <div class="reset-button">
                <a href="http://stg-api.firsthealthassist.com/android/assetlinks.php?resetUrl={{ $resetUrl }}">
                   Open App to Reset Password
                </a>
            </div>

            <p>If you didn’t request a password reset, simply ignore this email—your account remains secure.</p>
            <p>For any further assistance, feel free to contact our support team at <strong>support@firsthealthassist.com</strong> or visit our <a href="https://www.example.com/help-center">help center</a>.</p>
            <p>Thank you for being a part of the First Health community!</p>
        </div>

        <!-- Social Media Icons -->
        <div class="social-media">
           <a href="https://www.facebook.com/people/First-Health-Assist/61574147188798/"><img src="https://cdn-icons-png.flaticon.com/24/733/733547.png" alt="Facebook"></a>
            <a href="https://www.instagram.com/firsthealthassist/"><img src="https://cdn-icons-png.flaticon.com/24/2111/2111463.png" alt="Instagram"></a>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Copyright 2024 First Health. All rights reserved.</p>
            <p>If you have any questions, reach out to us at: hello@firsthealthassist.com</p>
            <p>This is a no-reply email. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>

