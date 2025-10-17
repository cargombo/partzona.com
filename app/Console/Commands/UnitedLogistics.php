<?php

namespace App\Console\Commands;

use App\Models\AutoModel;
use App\Models\AutoModelGroup;
use App\Models\Brand;
use App\Models\Upload;
use App\Services\TurboazScrap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class UnitedLogistics extends Command
{
    protected $signature = 'united:fetch {action : Action to perform (warehouses|orders|tracking)}';

    protected $description = 'United Logistics API integration - Fetch warehouses, orders, or tracking information';

    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'warehouses':
                $this->getWarehouses();
                break;
            case 'orders':
                $this->getOrders();
                break;
            case 'tracking':
                $this->getTracking();
                break;
            default:
                $this->error("Invalid action: {$action}");
                $this->info("Available actions: warehouses, orders, tracking");
                return 1;
        }

        return 0;
    }

    /**
     * Get list of warehouses from United Logistics
     */
    private function getWarehouses()
    {
        $this->info('Fetching warehouses from United Logistics...');

        try {
            // United Logistics API endpoint
            $apiUrl = 'https://united.az/v1/3rd';
            $apiKey = '32ec214d-561f-83f1-b283-a372b6e8bc23';

            if (!$apiKey) {
                $this->error('UNITED_LOGISTICS_API_KEY not set in .env file');
                return;
            }

            // Use cURL for the request - api_key in both header AND query string
            $url = $apiUrl . '/warehouses';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'api-key: ' . $apiKey,
                'Content-Type: application/json',
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                $this->error("cURL Error: {$curlError}");
            }

            $response = json_decode($responseBody, true);

            if ($httpCode == 200 && isset($response['data'])) {
                $this->info('Warehouses retrieved successfully:');
                $this->table(
                    ['Pickup ID', 'Pickup Name'],
                    collect($response['data'])->map(function ($warehouse) {
                        return [
                            $warehouse['pickup_id'] ?? 'N/A',
                            $warehouse['pickup_name'] ?? 'N/A',
                        ];
                    })
                );

                $this->info('Total warehouses: ' . count($response['data']));
                $this->info('Response code: ' . ($response['code'] ?? $httpCode));
            } else {
                $this->error('Failed to fetch warehouses: HTTP ' . $httpCode);
                $this->error($responseBody);
            }
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }

    /**
     * Get orders from United Logistics
     */
    private function getOrders()
    {
        $this->info('Fetching orders from United Logistics...');

        try {
            $apiUrl = 'https://united.az/v1/3rd';
            $apiKey = '32ec214d-561f-83f1-b283-a372b6e8bc23';

            if (!$apiKey) {
                $this->error('UNITED_LOGISTICS_API_KEY not set in .env file');
                return;
            }

            // Use cURL for the request - api_key in header
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl . '/orders');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'api-key: ' . $apiKey,
                'Content-Type: application/json',
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $response = json_decode($responseBody, true);

            if ($httpCode == 200 && isset($response['data'])) {
                $this->info('Orders retrieved successfully:');
                $this->table(
                    ['Order ID', 'Status', 'Tracking', 'Created At'],
                    collect($response['data'])->map(function ($order) {
                        return [
                            $order['id'] ?? 'N/A',
                            $order['status'] ?? 'N/A',
                            $order['tracking_number'] ?? 'N/A',
                            $order['created_at'] ?? 'N/A',
                        ];
                    })
                );

                $this->info('Total orders: ' . count($response['data']));
                $this->info('Response code: ' . ($response['code'] ?? $httpCode));
            } else {
                $this->error('Failed to fetch orders: HTTP ' . $httpCode);
                $this->error($responseBody);
            }
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }

    /**
     * Get tracking information from United Logistics
     */
    private function getTracking()
    {
        $trackingNumber = $this->ask('Enter tracking number:');

        if (!$trackingNumber) {
            $this->error('Tracking number is required');
            return;
        }

        $this->info("Fetching tracking information for: {$trackingNumber}");

        try {
            $apiUrl = 'https://united.az/v1/3rd';
            $apiKey = '32ec214d-561f-83f1-b283-a372b6e8bc23';

            if (!$apiKey) {
                $this->error('UNITED_LOGISTICS_API_KEY not set in .env file');
                return;
            }

            // Use cURL for the request - api_key in header
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl . "/parcel-states/{$trackingNumber}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'api-key: ' . $apiKey,
                'Content-Type: application/json',
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $response = json_decode($responseBody, true);

            if ($httpCode == 200 && $response) {
                $this->info('Tracking information:');
                $this->line('Status: ' . ($response['status'] ?? 'N/A'));
                $this->line('Current Location: ' . ($response['current_location'] ?? 'N/A'));
                $this->line('Estimated Delivery: ' . ($response['estimated_delivery'] ?? 'N/A'));

                if (isset($response['history']) && count($response['history']) > 0) {
                    $this->info("\nTracking History:");
                    $this->table(
                        ['Date', 'Location', 'Status', 'Description'],
                        collect($response['history'])->map(function ($history) {
                            return [
                                $history['date'] ?? 'N/A',
                                $history['location'] ?? 'N/A',
                                $history['status'] ?? 'N/A',
                                $history['description'] ?? 'N/A',
                            ];
                        })
                    );
                }

                $this->info('Response code: ' . ($response['code'] ?? $httpCode));
            } else {
                $this->error('Failed to fetch tracking information: HTTP ' . $httpCode);
                $this->error($responseBody);
            }
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }

}
