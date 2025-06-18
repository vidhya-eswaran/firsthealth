<!DOCTYPE html>
<html>
<head>
    <title>Invitation to Join as a Dependant</title>
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
            <h1>You’re Invited to Join as a Dependant!</h1>
            <p>Hi there,</p>
            <p>You have been invited to join First Health membership as a dependant.</p>
            <p>Your invitation is for the role of <strong>{{ $dependentType }}</strong>.</p>
            <p>To fully accept your invitation, please copy the referral number below and use it during your registration or update it from your "My Account":</p>
            <p><strong>Referral Number: {{ $referralNumber }}</strong></p>
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
