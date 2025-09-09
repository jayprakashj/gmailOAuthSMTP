<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laravel OAuth2 Integration Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>🎉 Laravel OAuth2 Integration Test</h2>
    </div>
    
    <div class="content">
        <p>Hello <strong>Everyone</strong>,</p>
        
        <p>We have successfully completed our <strong>Laravel Mail Integration</strong> with <strong>Gmail OAuth2</strong>!</p>
        
        <p class="success">✅ This email was sent using Laravel's built-in Mail functionality</p>
        <p class="success">✅ OAuth2 authentication is working perfectly</p>
        <p class="success">✅ Token refresh mechanism is operational</p>
        
        <p>This demonstrates that our OAuth2 implementation is fully functional and ready for production use.</p>
        
        <p>Thank you,<br>
        <strong>Laravel OAuth2 Team</strong></p>
    </div>
    
    <div class="footer">
        <p><em>This is an automated test email sent via Laravel Mail with Gmail OAuth2 authentication.</em></p>
        <p>Sent at: {{ now()->format('Y-m-d H:i:s T') }}</p>
    </div>
</body>
</html>
