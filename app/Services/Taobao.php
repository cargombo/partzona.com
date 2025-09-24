<?php

namespace App\Services;

class Taobao
{
    private static string $baseUrl = 'https://amazon.ini.az';
    private static string $bearerToken = "yGPUjC5AFTC8VgJL1twOZ1hvytv9BkchTh3TgTadyG9tsH"; // You can set your default token here

    /**
     * Set the bearer token for authentication
     *
     * @param string $token The bearer token
     * @return void
     */


    /**
     * Scrape Taobao search results
     *
     * @param string $keyword Search keyword
     * @return array The search results
     */
    public static function scrapeSearch($keyword,$page = 1): array
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://amazon.ini.az/taobao-curl/{$keyword}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . self::$bearerToken
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

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
     * @return array The product details
     */
    public static function scrapeProduct($asin): array
    {
        return self::makeRequest('/get-product', [
            'asin'   => $asin,
            "domen"  => "tmall",
        ]);
    }

    /**
     * Make an API request to the Taobao scraping service
     *
     * @param string $endpoint The API endpoint
     * @param array $data The request data
     * @return array The response data
     */
    private static function makeRequest(string $endpoint, array $data): array
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => self::$baseUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . self::$bearerToken
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new \Exception('cURL Error: ' . $err);
        }

        return json_decode($response, true) ?: [];
    }
}
