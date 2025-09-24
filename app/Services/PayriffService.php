<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log; // Logging üçün

class PayriffService
{
    protected string $merchantId;
    protected string $secretKey;
    protected string $apiUrl;
    protected string $defaultCurrency;
    protected string $defaultLanguage;
    protected array $endpoints;

    public function __construct()
    {
//        dd(Config::get('payriff.merchant_id'));
        $this->merchantId = Config::get('payriff.merchant_id');
        $this->secretKey = Config::get('payriff.secret_key');
        $this->apiUrl = Config::get('payriff.api_url');
        $this->defaultCurrency = Config::get('payriff.default_currency');
        $this->defaultLanguage = Config::get('payriff.default_language');
        $this->endpoints = Config::get('payriff.endpoints');

        if (empty($this->merchantId) || empty($this->secretKey)) {
            throw new \InvalidArgumentException('Payriff Merchant ID və Secret Key konfiqurasiya edilməyib.');
        }
    }

    /**
     * Payriff API-na sorğu göndərir.
     *
     * @param string $endpoint
     * @param array $data
     * @return array|null
     */
    protected function makeRequest(string $endpoint, array $payloadBody): ?array
    {
        $fullUrl = rtrim($this->apiUrl, '/') . '/' . ltrim($endpoint, '/');

        $requestData = [
            'body' => $payloadBody,
            'merchant' => $this->merchantId, // Dokumentasiyaya görə həm də burada göndərilir
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->secretKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($fullUrl, $requestData);

            if (!$response->successful()) {
                Log::error('Payriff API Error:', [
                    'url' => $fullUrl,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'request_data' => $requestData
                ]);
                // Əgər Payriff error mesajı JSON formatındadırsa, onu qaytara bilərik
                return $response->json() ?? ['error' => 'API request failed', 'status' => $response->status()];
            }

            $responseData = $response->json();

            // Uğurlu cavabları loglaya bilərik (debug üçün)
            // Log::info('Payriff API Success:', ['url' => $fullUrl, 'response' => $responseData]);

            return $responseData;

        }
        catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Payriff Connection Exception:', [
                'url' => $fullUrl,
                'message' => $e->getMessage(),
                'request_data' => $requestData
            ]);
            return ['error' => 'Connection to Payriff API failed: ' . $e->getMessage()];
        }
        catch (\Exception $e) {
            Log::error('Payriff General Exception:', [
                'url' => $fullUrl,
                'message' => $e->getMessage(),
                'request_data' => $requestData
            ]);
            return ['error' => 'An unexpected error occurred: ' . $e->getMessage()];
        }
    }

    /**
     * Yeni ödəniş sifarişi yaradır.
     *
     * @param float $amount Məbləğ
     * @param string $description Sifarişin təsviri
     * @param string|null $currencyType Valyuta (AZN, USD, EUR). Boş olarsa, konfiqurasiyadan götürülür.
     * @param string|null $language Dil (AZ, EN, RU). Boş olarsa, konfiqurasiyadan götürülür.
     * @param string|null $approveUrl Uğurlu ödənişdən sonra yönləndiriləcək URL.
     * @param string|null $cancelUrl Ləğv edildikdə yönləndiriləcək URL.
     * @param string|null $declineUrl Rədd edildikdə yönləndiriləcək URL.
     * @return array|null Cavab massivi və ya error
     */
    public function createOrder(
        float $amount,
        string $description,
        ?string $currencyType = null,
        ?string $language = null,
        ?string $approveUrl = null,
        ?string $cancelUrl = null,
        ?string $declineUrl = null
    ): ?array {
        $payloadBody = [
            'amount' => $amount,
            'currencyType' => $currencyType ?? $this->defaultCurrency,
            'description' => $description,
            'languageType' => $language ?? $this->defaultLanguage,
            'fullName' => auth()->user()->name ?? '',
            'phoneNumber' => auth()->user()->phone ?? '',
            'email' => auth()->user()->email ?? '',
            'sendSms' => auth()->user()->phone == null ? false : true,
            'customMessage' => 'test',
            'expireDate' => now()->setTimezone('Asia/Baku')->addMinutes(20)->format('Y-m-d H:i:s'),
            'approveURL' => $approveUrl ?? route(Config::get('payriff.approve_route_name')),
            'cancelURL' => $cancelUrl ?? route(Config::get('payriff.cancel_route_name')),
            'declineURL' => $declineUrl ?? route(Config::get('payriff.decline_route_name')),
//             'directPay' => false, // Əgər lazımdırsa

            // approveURL → Ödəniş uğurlu
            // cancelURL → Ödəniş ləğv edildi
            // declineURL → Ödəniş rədd edildi
        ];

        $response = $this->makeRequest($this->endpoints['create_order'], $payloadBody);

        if (isset($response['code']) && $response['code'] === '00000') {
            return $response['payload']; // paymentUrl, orderId, sessionId daxildir
        }

        Log::error('Payriff Create Order Failed:', [
            'payload_body' => $payloadBody,
            'response' => $response
        ]);
        return $response; // Erroru qaytarır
    }

    /**
     * Sifarişin statusunu yoxlayır.
     *
     * @param string $orderId Payriff tərəfindən verilən sifariş ID-si
     * @param string $sessionId Payriff tərəfindən createOrder zamanı verilən sessiya ID-si
     * @param string|null $language Dil (AZ, EN, RU). Boş olarsa, konfiqurasiyadan götürülür.
     * @return array|null Cavab massivi və ya error
     */
    public function getOrderStatus(string $orderId, string $sessionId, ?string $language = null): ?array
    {
        $payloadBody = [
            'orderId' => $orderId,
            'sessionId' => $sessionId,
            'language' => $language ?? $this->defaultLanguage,
        ];

        $response = $this->makeRequest($this->endpoints['get_status'], $payloadBody);

        if (isset($response['code']) && $response['code'] === '00000') {
            return $response['payload']; // orderStatus, amount, currency daxildir
        }

        Log::error('Payriff Get Order Status Failed:', [
            'payload_body' => $payloadBody,
            'response' => $response
        ]);
        return $response; // Erroru qaytarır
    }
}
