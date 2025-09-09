<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Google;
use App\Models\OAuthToken;

class OAuthController extends Controller
{
    private $client_id;
    private $client_secret;
    private $redirect_uri;

    private $provider;
    private $google_options;
    
    /**
     * Default Constructor
     */
    public function __construct()
    {
        $this->client_id = env('GMAIL_API_CLIENT_ID');
        $this->client_secret = env('GMAIL_API_CLIENT_SECRET');
        $this->redirect_uri = route('token.success');
        $this->google_options = [
            'scope' => [
                'https://mail.google.com/'
            ],
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        $params = [
            'clientId'      => $this->client_id,
            'clientSecret'  => $this->client_secret,
            'redirectUri'   => $this->redirect_uri,
            'accessType'    => 'offline'
        ];

        // Create Google Provider
        $this->provider = new Google($params);
    }

    /**
     * Generate url to retreive token
     */
    public function doGenerateToken()
    {
        $redirect_uri = $this->provider->getAuthorizationUrl($this->google_options);
        return redirect($redirect_uri);
    }

    /**
     * Retreive Token 
     */
    public function doSuccessToken(Request $request)
    {
        $code = $request->get('code');

        try {
            // Generate Token From Code 
            $tokenObj = $this->provider->getAccessToken(
                'authorization_code',
                [
                    'code' => $code
                ]
                );
                $token = $tokenObj->getToken();
                $refresh_token = $tokenObj->getRefreshToken();
                $expires_at = $tokenObj->getExpires();
                
                // Debug information
                \Log::info('Token generation debug:', [
                    'access_token' => $token ? 'Present' : 'Missing',
                    'refresh_token' => $refresh_token ? 'Present' : 'Missing',
                    'expires_at' => $expires_at,
                    'token_obj' => $tokenObj
                ]);
                
                // Store token data in database
                $userEmail = 'jpcloudspot@gmail.com'; // You can make this dynamic later
                OAuthToken::updateOrCreateForUser(
                    $userEmail,
                    $token,
                    $refresh_token,
                    $expires_at
                );
                
                // Also store in session for backward compatibility
                session([
                    'oauth_access_token' => $token,
                    'oauth_refresh_token' => $refresh_token,
                    'oauth_expires_at' => $expires_at,
                    'user_email' => $userEmail
                ]);
                
                if( $refresh_token != null && !empty($refresh_token) ) {
                    return redirect()->back()->with('token', $refresh_token);
                } elseif ( $token != null && !empty($token) ) {
                    return redirect()->back()->with('token', $token);
                } else {
                    return redirect()->back()->with('error', 'Unable to retreive token.');
                }
        } catch(IdentityProviderException $e) {
            return redirect()->back()->with('error', 'Exception: ' . $e->getMessage());
        } catch(Exception $e) {
            return redirect()->back()->with('error', 'Exception: ' . $e->getMessage());
        }
    }

    /**
     * Check if token is expired
     */
    public function isTokenExpired($userEmail = null)
    {
        if ($userEmail === null) {
            $userEmail = session('user_email', 'jpcloudspot@gmail.com');
        }
        
        $tokenRecord = OAuthToken::findByEmail($userEmail);
        
        if (!$tokenRecord) {
            return true; // No token means expired
        }
        
        return $tokenRecord->isExpired();
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshAccessToken()
    {
        $userEmail = session('user_email', 'jpcloudspot@gmail.com');
        $tokenRecord = OAuthToken::findByEmail($userEmail);
        
        // Debug information
        if (!$tokenRecord) {
            return redirect()->back()->with('error', 'No token record found for email: ' . $userEmail . '. Please generate a new token first.');
        }
        
        if (!$tokenRecord->refresh_token) {
            return redirect()->back()->with('error', 'No refresh token available for email: ' . $userEmail . '. Please generate a new token first.');
        }
        
        try {
            $tokenObj = $this->provider->getAccessToken('refresh_token', [
                'refresh_token' => $tokenRecord->refresh_token
            ]);
            
            $new_access_token = $tokenObj->getToken();
            $new_refresh_token = $tokenObj->getRefreshToken();
            $new_expires_at = $tokenObj->getExpires();
            
            // Update database with new token data
            OAuthToken::updateOrCreateForUser(
                $userEmail,
                $new_access_token,
                $new_refresh_token ?: $tokenRecord->refresh_token, // Keep old refresh token if new one not provided
                $new_expires_at
            );
            
            // Also update session for backward compatibility
            session([
                'oauth_access_token' => $new_access_token,
                'oauth_refresh_token' => $new_refresh_token ?: $tokenRecord->refresh_token,
                'oauth_expires_at' => $new_expires_at,
                'user_email' => $userEmail
            ]);
            
            return redirect()->back()->with('success', 'Token refreshed successfully! New expiry: ' . 
                ($new_expires_at ? date('Y-m-d H:i:s', $new_expires_at) : 'Unknown'));
            
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to refresh token: ' . $e->getMessage());
        }
    }

    /**
     * Get valid access token (refresh if needed)
     */
    public function getValidAccessToken()
    {
        $userEmail = session('user_email', 'jpcloudspot@gmail.com');
        
        if ($this->isTokenExpired($userEmail)) {
            return $this->refreshAccessToken();
        }
        
        $tokenRecord = OAuthToken::findByEmail($userEmail);
        return $tokenRecord ? $tokenRecord->access_token : null;
    }

    /**
     * Load tokens from database into session
     */
    public function loadTokensFromDatabase($userEmail = null)
    {
        if ($userEmail === null) {
            $userEmail = session('user_email', 'jpcloudspot@gmail.com');
        }
        
        $tokenRecord = OAuthToken::findByEmail($userEmail);
        
        if ($tokenRecord) {
            session([
                'oauth_access_token' => $tokenRecord->access_token,
                'oauth_refresh_token' => $tokenRecord->refresh_token,
                'oauth_expires_at' => $tokenRecord->expires_at ? $tokenRecord->expires_at->timestamp : null,
                'user_email' => $tokenRecord->user_email
            ]);
            return true;
        }
        
        return false;
    }

    /**
     * Clear existing tokens and force fresh authorization
     */
    public function clearTokens()
    {
        $userEmail = session('user_email', 'jpcloudspot@gmail.com');
        
        // Clear from database
        OAuthToken::where('user_email', $userEmail)->delete();
        
        // Clear from session
        session()->forget([
            'oauth_access_token',
            'oauth_refresh_token', 
            'oauth_expires_at',
            'user_email'
        ]);
        
        return redirect()->back()->with('success', 'Tokens cleared. Please generate a new token to get a refresh token.');
    }
}