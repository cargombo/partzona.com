<?php

namespace App\Services;

class TurboazScrap
{
    private static string $apiUrl = 'https://turbo.az/api/v2/catalog/makes';

    public static function getBrands(): array
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => self::$apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept: application/json',
                'X-Requested-With: XMLHttpRequest',
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new \Exception('cURL Error: ' . $err);
        }

        $data = json_decode($response, true);

        if (!isset($data['makes']) || !isset($data['popular_makes'])) {
            throw new \Exception('Invalid response from turbo.az API');
        }

        $popularIds = array_column($data['popular_makes'], 'id');


        $brands = [];
        foreach ($data['makes'] as $make) {
            $brands[] = [
                'id' => $make['id'],
                'name' => $make['name'],
                'logo' => $make['logo_url'],
                'is_popular' => in_array($make['id'], $popularIds) ? true : false,
            ];
        }

        return $brands;
    }
}
