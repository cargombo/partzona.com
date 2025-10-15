<?php

namespace App\Services;

use App\Models\TaobaoToken;
use Carbon\Carbon;
use Exception;

class TaobaoAuthService
{
    private static string  $appKey = "503494";
    private static string  $appSecret = "6irVZUUB5Va5BwdRPFrjbLenkWhbf5OF";
    private static string  $code = "2_503494_qeQMqUVs1ZKyl6YtqKrEV4EW238";

    /**
     * Access Token yarat və saxla
     */
    public static function createAndSaveAccessToken()
    {
        // Token yaradırıq
        $response = self::createAccessToken();
        if (isset($response['code']) && $response['code'] !== '0') {
            throw new Exception("Taobao Error: " . $response['message']);
        }
        TaobaoToken::deactivateOldTokens($response['seller_id']);

        $token = TaobaoToken::create([
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'],
            'user_id' => $response['user_id'],
            'seller_id' => $response['seller_id'],
            'account' => $response['account'],
            'account_platform' => $response['account_platform'] ?? 'seller_center',
            'short_code' => $response['short_code'] ?? null,
            'expires_in' => $response['expires_in'],
            'refresh_expires_in' => $response['refresh_expires_in'],
            'access_token_expires_at' => Carbon::now()->addSeconds($response['expires_in']),
            'refresh_token_expires_at' => Carbon::now()->addSeconds($response['refresh_expires_in']),
            'code' => self::$code,
            'request_id' => $response['request_id'] ?? null,
            'trace_id' => $response['_trace_id_'] ?? null,
            'is_active' => true
        ]);

        return $token;
    }


    public static function refreshAccessToken($refreshToken)
    {

        $timestamp = (string)(round(microtime(true) * 1000));

        $params = [
            'app_key' => self::$appKey,
            'refresh_token' => $refreshToken,
            'sign_method' => 'sha256',
            'timestamp' => $timestamp
        ];

        ksort($params);

        $apiPath = '/auth/token/refresh';
        $stringToSign = $apiPath;

        foreach ($params as $key => $value) {
            $stringToSign .= $key . $value;
        }

        $sign = strtoupper(hash_hmac('sha256', $stringToSign, self::$appSecret));
        $params['sign'] = $sign;

        $queryParts = [];
        foreach ($params as $key => $value) {
            $queryParts[] = $key . '=' . $value;
        }
        $queryString = implode('&', $queryParts);

        $url = 'https://api.taobao.global/rest' . $apiPath . '?' . $queryString;

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($response, true);

        if (isset($result['code']) && $result['code'] !== '0') {
            throw new Exception("Refresh Error: " . $result['message']);
        }

        return $result;
    }


    public static function getValidToken()
    {
        try {
            $token = TaobaoToken::getActiveToken();
            if (!$token) {
                $token = self::createAndSaveAccessToken();
            }

            if ($token->isAccessTokenValid() && !$token->needsRefresh()) {
                return $token->access_token;
            }

            if ($token->isRefreshTokenValid()) {

                $refreshedData = self::refreshAccessToken($token->refresh_token);
                $token->update(['is_active' => false]);

                $newToken = TaobaoToken::create([
                    'access_token' => $refreshedData['access_token'],
                    'refresh_token' => $refreshedData['refresh_token'] ?? $token->refresh_token,
                    'user_id' => $token->user_id,
                    'seller_id' => $token->seller_id,
                    'account' => $token->account,
                    'account_platform' => $token->account_platform,
                    'short_code' => $refreshedData['short_code'] ?? null,
                    'expires_in' => $refreshedData['expires_in'],
                    'refresh_expires_in' => $refreshedData['refresh_expires_in'] ?? $token->refresh_expires_in,
                    'access_token_expires_at' => Carbon::now()->addSeconds($refreshedData['expires_in']),
                    'refresh_token_expires_at' => $token->refresh_token_expires_at,
                    'request_id' => $refreshedData['request_id'] ?? null,
                    'trace_id' => $refreshedData['_trace_id_'] ?? null,
                    'is_active' => true
                ]);

                return $newToken->access_token;
            }
        } catch (\Exception $e) {
            \Log::warning('Taobao auth error: ' . $e->getMessage());
            return null;
        }

        return null;
    }

    /**
     * Access token yaradır (əvvəlki funksiya)
     */
    private static function createAccessToken()
    {
        $appKey = self::$appKey;
        $appSecret = self::$appSecret;
        $code = self::$code;

        $timestamp = (string)(round(microtime(true) * 1000));

        $params = [
            'app_key' => $appKey,
            'code' => $code,
            'sign_method' => 'sha256',
            'timestamp' => $timestamp
        ];

        ksort($params);
        $apiPath = '/auth/token/create';
        $stringToSign = $apiPath;

        foreach ($params as $key => $value) {
            $stringToSign .= $key . $value;
        }


        $sign = strtoupper(hash_hmac('sha256', $stringToSign, $appSecret));

        $params['sign'] = $sign;

        $queryParts = [];
        foreach ($params as $key => $value) {
            $queryParts[] = $key . '=' . $value;
        }
        $queryString = implode('&', $queryParts);

        $url = 'https://api.taobao.global/rest' . $apiPath . '?' . $queryString;

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return json_decode($response, true);
    }
}
