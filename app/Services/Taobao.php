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

        $token = TaobaoAuthService::getValidToken();
        dd($token);


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
            CURLOPT_POSTFIELDS     => '{"access_token":"37c66819338b4562e17675b8c5c4dbd0","app_key":"1234567","item_id":"652876415053","sign_method":"sha256","sign":"D13F2A03BE94D9AAE9F933FFA7B13E0A5AD84A3DAEBC62A458A3C382EC2E91EC","timestamp":"1759216051386","item_source_market":"CBU_MARKET"}',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json;charset=utf-8'
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;

    }



}
