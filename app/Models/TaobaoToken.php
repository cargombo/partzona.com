<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TaobaoToken extends Model
{
    protected $fillable = [
        'access_token',
        'refresh_token',
        'user_id',
        'seller_id',
        'account',
        'account_platform',
        'short_code',
        'expires_in',
        'refresh_expires_in',
        'access_token_expires_at',
        'refresh_token_expires_at',
        'code',
        'request_id',
        'trace_id',
        'is_active'
    ];

    protected $casts = [
        'access_token_expires_at' => 'datetime',
        'refresh_token_expires_at' => 'datetime',
        'is_active' => 'boolean',
        'expires_in' => 'integer',
        'refresh_expires_in' => 'integer',
    ];

    /**
     * Access token aktiv və etibarlıdır?
     */
    public function isAccessTokenValid(): bool
    {
        return $this->is_active &&
            $this->access_token_expires_at &&
            $this->access_token_expires_at->isFuture();
    }

    /**
     * Refresh token etibarlıdır?
     */
    public function isRefreshTokenValid(): bool
    {
        return $this->is_active &&
            $this->refresh_token_expires_at &&
            $this->refresh_token_expires_at->isFuture();
    }

    /**
     * Token-u yeniləmək lazımdır?
     * (Bitməsinə 7 gün qalıbsa)
     */
    public function needsRefresh(): bool
    {
        if (!$this->access_token_expires_at) {
            return false;
        }

        return $this->access_token_expires_at->diffInDays(now()) <= 7;
    }

    /**
     * Aktiv token-u gətir
     */
    public static function getActiveToken($sellerId = null)
    {
        $query = self::where('is_active', true)
            ->where('access_token_expires_at', '>', now());

        return $query->latest()->first();
    }

    /**
     * Köhnə token-ları deaktiv et
     */
    public static function deactivateOldTokens($sellerId)
    {
        return self::where('seller_id', $sellerId)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
