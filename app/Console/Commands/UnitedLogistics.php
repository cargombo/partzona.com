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
            $apiUrl = env('UNITED_LOGISTICS_API_URL', 'https://api.unitedlogistics.az');
            $apiToken = env('UNITED_LOGISTICS_API_TOKEN');

            if (!$apiToken) {
                $this->error('UNITED_LOGISTICS_API_TOKEN not set in .env file');
                return;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiToken,
                'Accept' => 'application/json',
            ])->get($apiUrl . '/api/warehouses');

            if ($response->successful()) {
                $warehouses = $response->json();

                $this->info('Warehouses retrieved successfully:');
                $this->table(
                    ['ID', 'Name', 'Country', 'Address'],
                    collect($warehouses['data'] ?? [])->map(function ($warehouse) {
                        return [
                            $warehouse['id'] ?? 'N/A',
                            $warehouse['name'] ?? 'N/A',
                            $warehouse['country'] ?? 'N/A',
                            $warehouse['address'] ?? 'N/A',
                        ];
                    })
                );

                $this->info('Total warehouses: ' . count($warehouses['data'] ?? []));
            } else {
                $this->error('Failed to fetch warehouses: ' . $response->status());
                $this->error($response->body());
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
            $apiUrl = env('UNITED_LOGISTICS_API_URL', 'https://api.unitedlogistics.az');
            $apiToken = env('UNITED_LOGISTICS_API_TOKEN');

            if (!$apiToken) {
                $this->error('UNITED_LOGISTICS_API_TOKEN not set in .env file');
                return;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiToken,
                'Accept' => 'application/json',
            ])->get($apiUrl . '/api/orders');

            if ($response->successful()) {
                $orders = $response->json();

                $this->info('Orders retrieved successfully:');
                $this->table(
                    ['Order ID', 'Status', 'Tracking', 'Created At'],
                    collect($orders['data'] ?? [])->map(function ($order) {
                        return [
                            $order['id'] ?? 'N/A',
                            $order['status'] ?? 'N/A',
                            $order['tracking_number'] ?? 'N/A',
                            $order['created_at'] ?? 'N/A',
                        ];
                    })
                );

                $this->info('Total orders: ' . count($orders['data'] ?? []));
            } else {
                $this->error('Failed to fetch orders: ' . $response->status());
                $this->error($response->body());
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
            $apiUrl = env('UNITED_LOGISTICS_API_URL', 'https://api.unitedlogistics.az');
            $apiToken = env('UNITED_LOGISTICS_API_TOKEN');

            if (!$apiToken) {
                $this->error('UNITED_LOGISTICS_API_TOKEN not set in .env file');
                return;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiToken,
                'Accept' => 'application/json',
            ])->get($apiUrl . "/api/tracking/{$trackingNumber}");

            if ($response->successful()) {
                $tracking = $response->json();

                $this->info('Tracking information:');
                $this->line('Status: ' . ($tracking['status'] ?? 'N/A'));
                $this->line('Current Location: ' . ($tracking['current_location'] ?? 'N/A'));
                $this->line('Estimated Delivery: ' . ($tracking['estimated_delivery'] ?? 'N/A'));

                if (isset($tracking['history']) && count($tracking['history']) > 0) {
                    $this->info("\nTracking History:");
                    $this->table(
                        ['Date', 'Location', 'Status', 'Description'],
                        collect($tracking['history'])->map(function ($history) {
                            return [
                                $history['date'] ?? 'N/A',
                                $history['location'] ?? 'N/A',
                                $history['status'] ?? 'N/A',
                                $history['description'] ?? 'N/A',
                            ];
                        })
                    );
                }
            } else {
                $this->error('Failed to fetch tracking information: ' . $response->status());
                $this->error($response->body());
            }
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }

}
