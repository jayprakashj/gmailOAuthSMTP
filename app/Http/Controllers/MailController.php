<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OAuthToken;
use League\OAuth2\Client\Provider\Google;

class MailController extends Controller
{
    private $fromEmail = 'jpcloudspot@gmail.com';
    private $fromName = 'Jayprakash JP Cloudspot';
    private $toEmail = 'jayprakashj@gmail.com';
    private $toName = 'Jayprakash';

    /**
     * Default Constructor
     */
    public function __construct()
    {
        // Constructor - no special configuration needed
    }

    /**
     * Send Email via Laravel Mail with OAuth2
     */
    public function doSendEmail(Request $request)
    {
        try {
            // Get valid token (refresh if needed)
            $tokenRecord = $this->getValidTokenRecord();
            
            // Send email using Laravel Mail with OAuth2
            $this->sendOAuth2Email($tokenRecord);
            
            return redirect()->back()->with('success', 'Email sent successfully using Laravel Mail with OAuth2!');
            
        } catch(\Exception $e) {
            return redirect()->back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }

    /**
     * Send email using PHPMailer with OAuth2 (integrated with Laravel Mail)
     */
    private function sendOAuth2Email($tokenRecord)
    {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_OFF;
            $mail->Host = 'smtp.gmail.com';
            $mail->Port = 465;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->SMTPAuth = true;
            $mail->AuthType = 'XOAUTH2';
            
            $provider = new Google([
                'clientId' => env('GMAIL_API_CLIENT_ID'),
                'clientSecret' => env('GMAIL_API_CLIENT_SECRET')
            ]);
            
            $mail->setOAuth(
                new \PHPMailer\PHPMailer\OAuth([
                    'provider' => $provider,
                    'clientId' => env('GMAIL_API_CLIENT_ID'),
                    'clientSecret' => env('GMAIL_API_CLIENT_SECRET'),
                    'refreshToken' => $tokenRecord->refresh_token,
                    'userName' => $this->fromEmail
                ])
            );

            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($this->toEmail, $this->toName);
            $mail->Subject = 'Laravel OAuth2 Integration Test';
            $mail->CharSet = \PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
            
            // Use the Laravel Mail template
            $mail->msgHTML(view('emails.oauth-test')->render());
            $mail->AltBody = 'This is a plain text message body';
            
            if (!$mail->send()) {
                throw new \Exception('Unable to send email.');
            }
            
        } catch(\Exception $e) {
            throw new \Exception('OAuth2 Email Error: ' . $e->getMessage());
        }
    }


    /**
     * Get valid token record (check expiry and refresh if needed)
     */
    private function getValidTokenRecord()
    {
        $userEmail = session('user_email');
        $tokenRecord = OAuthToken::findByEmail($userEmail);
        
        // If no tokens in database, throw error
        if (!$tokenRecord) {
            throw new \Exception('No OAuth token available. Please generate a new token first.');
        }
        
        // Check if token is expired
        if ($tokenRecord->isExpired()) {
            if (!$tokenRecord->refresh_token) {
                throw new \Exception('Token expired and no refresh token available. Please generate a new token.');
            }
            
            // Refresh the token
            $this->refreshToken($tokenRecord->refresh_token, $userEmail);
            
            // Get the updated token record
            $tokenRecord = OAuthToken::findByEmail($userEmail);
        }
        
        return $tokenRecord;
    }

    /**
     * Refresh access token using refresh token
     */
    private function refreshToken($refresh_token, $userEmail)
    {
        try {
            $tokenObj = $this->provider->getAccessToken('refresh_token', [
                'refresh_token' => $refresh_token
            ]);
            
            $new_access_token = $tokenObj->getToken();
            $new_refresh_token = $tokenObj->getRefreshToken();
            $new_expires_at = $tokenObj->getExpires();
            
            // Update database with new token data
            OAuthToken::updateOrCreateForUser(
                $userEmail,
                $new_access_token,
                $new_refresh_token ?: $refresh_token, // Keep old refresh token if new one not provided
                $new_expires_at
            );
            
            // Also update session for backward compatibility
            session([
                'oauth_access_token' => $new_access_token,
                'oauth_refresh_token' => $new_refresh_token ?: $refresh_token,
                'oauth_expires_at' => $new_expires_at,
                'user_email' => $userEmail
            ]);
            
            return $new_access_token;
            
        } catch (Exception $e) {
            throw new Exception('Failed to refresh token: ' . $e->getMessage());
        }
    }
}