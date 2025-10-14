<?php

namespace App\Services;

use App\Exceptions\UnitedLogisticsException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UnitedLogisticsService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.united_logistics.base_url');
        $this->apiKey = config('services.united_logistics.api_key');
    }

    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        try {
            $url = $this->baseUrl . $endpoint;

            $response = Http::withHeaders([
                'api_key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->{$method}($url, $data);

            if ($response->status() === 401) {
                throw new UnitedLogisticsException('API key is invalid or unauthorized');
            }

            if ($response->status() === 404) {
                throw new UnitedLogisticsException('Parcel not found');
            }

            if ($response->failed()) {
                $error = $response->json();
                throw new UnitedLogisticsException($error['msg'] ?? 'Unknown error', $response->status());
            }

            return $response->json();

        } catch (UnitedLogisticsException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::channel('unitedlogistics')->error('United Logistics API error: ' . $e->getMessage());
            throw new UnitedLogisticsException('Service temporarily unavailable', 500);
        }
    }

    /**
     * @throws UnitedLogisticsException
     * @throws ValidationException
     */
    public function createOrUpdateParcel(array $parcelData): array
    {
        $validationRules = [
            'fm_tracking_number' => 'required|string',
            'uid' => 'required|array',
            'buyer.first_name' => 'required|string|max:50',
            'buyer.last_name' => 'required|string|max:50',
            'buyer.email_address' => 'required|email|max:50',
            'buyer.phone_number' => 'required|string|min:13',
            'buyer.zip_code' => 'required|string|size:7',
            'buyer.city' => 'required|string|max:50',
            'buyer.country' => 'required|string|max:50',
            'buyer.shipping_address' => 'required|string|max:100',
        ];

        if ($parcelData['is_door'] ?? false) {
            $validationRules['warehouse_id'] = 'required|string';
        }

        validator($parcelData, $validationRules)->validate();

        return $this->makeRequest('post', '/parcel', $parcelData);
    }

    /**
     * @throws UnitedLogisticsException
     */
    public function listWarehouses(): array
    {
        return $this->makeRequest('get', '/warehouses');
    }

    /**
     * @throws UnitedLogisticsException
     */
    public function inboundScan(string $barcode, ?float $weight = null): array
    {
        $data = ['barcode' => $barcode];

        if ($weight !== null) {
            $data['weight'] = $weight;
        }

        return $this->makeRequest('post', '/inbound-parcel', $data);
    }

    /**
     * @throws UnitedLogisticsException
     */
    public function getParcelStates(string $barcode): array
    {
        return $this->makeRequest('get', "/parcel-states/{$barcode}");
    }

}
