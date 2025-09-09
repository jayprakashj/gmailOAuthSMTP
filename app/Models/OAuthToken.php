<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OAuthToken extends Model
{
    use HasFactory;

    protected $table = 'oauth_tokens';

    protected $fillable = [
        'user_email',
        'access_token',
        'refresh_token',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Check if the token is expired
     */
    public function isExpired()
    {
        if (!$this->expires_at) {
            return true;
        }
        
        return Carbon::now()->timestamp >= $this->expires_at->timestamp;
    }

    /**
     * Get time remaining until expiry in seconds
     */
    public function getTimeRemaining()
    {
        if (!$this->expires_at) {
            return 0;
        }
        
        return max(0, $this->expires_at->timestamp - Carbon::now()->timestamp);
    }

    /**
     * Get formatted time remaining (e.g., "2h 30m")
     */
    public function getFormattedTimeRemaining()
    {
        $seconds = $this->getTimeRemaining();
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        return "{$hours}h {$minutes}m";
    }

    /**
     * Find token by user email
     */
    public static function findByEmail($email)
    {
        return static::where('user_email', $email)->first();
    }

    /**
     * Update or create token for user
     */
    public static function updateOrCreateForUser($email, $accessToken, $refreshToken = null, $expiresAt = null)
    {
        return static::updateOrCreate(
            ['user_email' => $email],
            [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_at' => $expiresAt ? Carbon::createFromTimestamp($expiresAt) : null,
            ]
        );
    }
}