<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use League\OAuth2\Client\Provider\Google;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\OAuth;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use App\Models\OAuthToken;

class MailController extends Controller
{

    private $email;
    private $name;
    private $client_id;
    private $client_secret;
    private $provider;

    /**
     * Default Constructor
     */
    public function __construct()
    {
        $this->email            = 'jpcloudspot@gmail.com'; // ex. example@gmail.com
        $this->email_name       = 'Jayprakash JP Cloudspot';     // ex. Abidhusain
        $this->client_id        = env('GMAIL_API_CLIENT_ID');
        $this->client_secret    = env('GMAIL_API_CLIENT_SECRET');
        $this->provider         = new Google(
            [
                'clientId'      => $this->client_id,
                'clientSecret'  => $this->client_secret
            ]
        );

    }

    /**
     * Send Email via PHPMailer Library
     */
    public function doSendEmail(Request $request)
    {
        try {
            // Get valid access token (refresh if needed)
            $validToken = $this->getValidToken();
            
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->Host = 'smtp.gmail.com';
            $mail->Port = 465;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->SMTPAuth = true;
            $mail->AuthType = 'XOAUTH2';
            $mail->setOAuth(
                new OAuth(
                    [
                        'provider'          => $this->provider,
                        'clientId'          => $this->client_id,
                        'clientSecret'      => $this->client_secret,
                        'refreshToken'      => $validToken,
                        'userName'          => $this->email
                    ]
                )
            );

            $mail->setFrom($this->email, $this->name);
            $mail->addAddress('jayprakashj@gmail.com', 'Jayprakash');
            $mail->Subject = 'Laravel PHPMailer OAuth2 Integration';
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $body = 'Hello <b>Everyone</b>,<br><br>We successfully completed our PHPMailer Integration in Laravel Project with Gmail OAuth2.<br><br>Thank you,<br><b>Abidhusain Chidi</b>';
            $mail->msgHTML($body);
            $mail->AltBody = 'This is a plain text message body';
            
            if( $mail->send() ) {
                return redirect()->back()->with('success', 'Successfully send email!');
            } else {
                return redirect()->back()->with('error', 'Unable to send email.');
            }
        } catch(Exception $e) {
            return redirect()->back()->with('error', 'Exception: ' . $e->getMessage());
        }
    }

    /**
     * Get valid token (check expiry and refresh if needed)
     */
    private function getValidToken()
    {
        $userEmail = session('user_email', 'jpcloudspot@gmail.com');
        $tokenRecord = OAuthToken::findByEmail($userEmail);
        
        // If no tokens in database, throw error
        if (!$tokenRecord) {
            throw new Exception('No OAuth token available. Please generate a new token first.');
        }
        
        // Check if token is expired
        if ($tokenRecord->isExpired()) {
            if (!$tokenRecord->refresh_token) {
                throw new Exception('Token expired and no refresh token available. Please generate a new token.');
            }
            
            // Refresh the token
            $new_token = $this->refreshToken($tokenRecord->refresh_token, $userEmail);
            return $new_token;
        }
        
        return $tokenRecord->access_token;
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