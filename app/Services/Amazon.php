<?php

namespace App\Services;

class Amazon
{
    private static string $baseUrl = 'https://amazon.ini.az';
    private static string $bearerToken = "OryXos8JrzYTvU8UOxB1e1fp7SOIv0U4u7Gy4QEKazdglT"; // You can set your default token here

    /**
     * Set the bearer token for authentication
     *
     * @param string $token The bearer token
     * @return void
     */
    public static function setBearerToken(string $token): void
    {
        self::$bearerToken = $token;
    }

    /**
     * Scrape Amazon search results
     *
     * @param string $keyword Search keyword
     * @return array The search results
     */
    public static function scrapeSearch(string $keyword): array
    {

        $keyword =  strtolower($keyword);
        $url = "https://www.amazon.com/s?k=$keyword";
        return self::makeRequest('/scrape', [
            'url' => $url
        ]);
    }

    /**
     * Scrape Amazon product details
     *
     * @param string $productUrl The product URL
     * @return array The product details
     */
    public static function scrapeProduct($productUrl): array
    {
        return self::makeRequest('/scrape-product', [
            'url' => $productUrl
        ]);
    }

    public static function getProductPrice($productUrl): array
    {
        return self::makeRequest('/get-price', [
            'url' => $productUrl
        ]);
    }

    /**
     * Make an API request to the Amazon scraping service
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
            CURLOPT_CUSTOMREQUEST => 'POST',
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
