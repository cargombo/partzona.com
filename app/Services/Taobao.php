<?php

namespace App\Services;

class Taobao
{
    private static string $baseUrl     = 'https://amazon.ini.az';
    private static string $bearerToken = "yGPUjC5AFTC8VgJL1twOZ1hvytv9BkchTh3TgTadyG9tsH"; // You can set your default token here

    private static string  $appKey = "503494";
    private static string  $appSecret = "6irVZUUB5Va5BwdRPFrjbLenkWhbf5OF";
    private static string  $code = "2_503494_byob3EmVD8jbW4PLX4VIDI7d158";
    /**
     * Set the bearer token for authentication
     *
     * @param string $token The bearer token
     *
     * @return void
     */


    /**
     * Scrape Taobao search results
     *
     * @param string $keyword Search keyword
     *
     * @return array The search results
     */
    public static function scrapeSearch($keyword, $page = 1)
    : array {

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => "https://amazon.ini.az/taobao-curl/{$keyword}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . self::$bearerToken
            ],
        ]);

        $response = curl_exec($curl);
        $err      = curl_error($curl);


        curl_close($curl);

        if ($err) {
            throw new \Exception('cURL Error: ' . $err);
        }
//        dd(json_decode($response));

        return json_decode($response, true) ?: [];
    }

    /**
     * Scrape Taobao product details
     *
     * @param string $productUrl The product URL
     *
     * @return array The product details
     */
    public static function scrapeProduct($item_id)
    {
        $app_key = 503494;
        $app_secret = '6irVZUUB5Va5BwdRPFrjbLenkWhbf5OF';
        $token = TaobaoAuthService::getValidToken();
        $timestamp = round(microtime(true) * 1000);

        $params = [
            'access_token'       => $token,
            'app_key'            => $app_key,
            'item_id'            => $item_id,
            'sign_method'        => 'hmac-sha256',
            'timestamp'          => $timestamp,
            'item_source_market' => 'CBU',
        ];

        $sign = self::generateSign($params, $app_secret, '/product/get', 'hmac-sha256');
        $params['sign'] = $sign;
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://api.taobao.global/rest/product/get',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($params, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json;charset=utf-8'
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        // JSON formatında qaytarır (assoc = true olarsa array qaytarır, false olarsa object)
        return json_decode($response, true);
    }

    /**
     * Signature yaradacaq funksiya
     *
     * @param array $params API parametrləri (sign daxil deyil)
     * @param string $secret App secret
     * @param string $apiName API endpoint, məsələn '/product/get'
     * @param string $signMethod 'hmac-sha256', 'hmac' və ya 'md5'
     * @return string
     */
    public static function generateSign(array $params, string $secret, string $apiName, string $signMethod = 'hmac-sha256'): string
    {
        // Parametrləri stringə çevir və sort et (ASCII order)
        $params = array_map(function($item) {
            if (is_array($item)) {
                return array_map(function($subItem) {
                    if (is_array($subItem)) {
                        return array_map(function($subSubItem) {
                            return is_array($subSubItem) ? $subSubItem : strval($subSubItem);
                        }, $subItem);
                    }
                    return strval($subItem);
                }, $item);
            }
            return strval($item);
        }, $params);

        ksort($params, SORT_STRING);

        // Concatenate key + value
        $stringToSign = $apiName; // API adı başlanğıca əlavə olunur
        foreach ($params as $key => $value) {
            if ($value !== '') {
                if (is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE); // array-i string-ə çevir
                }
                $stringToSign .= $key . $value;
            }
        }

        // HMAC_SHA256 ilə sign yarat
        if ($signMethod === 'hmac-sha256') {
            $hash = hash_hmac('sha256', $stringToSign, $secret, false); // false → hex output
        } elseif ($signMethod === 'hmac') {
            $hash = hash_hmac('md5', $stringToSign, $secret, false);
        } else { // md5
            $hash = md5($secret . $stringToSign . $secret);
        }

        return strtoupper($hash);
    }

}
