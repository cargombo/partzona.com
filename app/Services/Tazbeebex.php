<?php

namespace App\Services;

class Tazbeebex
{
    /**
     * The base URL for the Tazbeebex API.
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * The API authorization token.
     *
     * @var string
     */
    protected $apiToken;

    /**
     * Create a new Tazbeebex service instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->baseUrl = env('TAZBEEBEX_BASE_API_URL');
        $this->apiToken = env('TAZBEEBEX_API_TOKEN');
    }

    /**
     * Create a new package via the Tazbeebex API.
     *
     * @param array $data Package data
     * @return mixed Response from the API
     */
    public function createPackage(array $data)
    {
        return $this->makeRequest('create-package', 'POST', $data);
    }

    /**
     * Update consolidation status via the Tazbeebex API.
     *
     * @param string $customId The custom ID to update
     * @return mixed Response from the API
     */
    public function updateConsolidation(string $customId)
    {
        return $this->makeRequest('consolidation-update', 'POST', [
            'custom_id' => $customId
        ]);
    }

    public function deliveryNumberUpdate(array $data)
    {
        return $this->makeRequest('delivery-number-update', 'POST',$data);
    }

    /**
     * Make a request to the Tazbeebex API.
     *
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param array $data Request data
     * @return mixed Response from the API
     */
    protected function makeRequest(string $endpoint, string $method = 'GET', array $data = [])
    {
        $curl = curl_init();

        $url = $this->baseUrl . '/' . $endpoint;

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->apiToken,
                'Content-Type: application/json'
            ],
        ];

        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            throw new \Exception("cURL Error: {$error}");
        }

        return json_decode($response,true);
    }
}