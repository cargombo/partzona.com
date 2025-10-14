<?php

namespace App\Http\Controllers\United;

use App\Http\Controllers\Controller;
use App\Services\UnitedLogisticsService;
use App\DTOs\ParcelCreateDTO;
use App\DTOs\BuyerDTO;
use App\DTOs\ProductDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Exceptions\UnitedLogisticsException;

class UnitedLogisticsController extends Controller
{
    private UnitedLogisticsService $logisticsService;

    public function __construct(UnitedLogisticsService $logisticsService)
    {
        $this->logisticsService = $logisticsService;
    }

    public function createParcel(Request $request): JsonResponse
    {
        try {
            $buyer = new BuyerDTO(
                first_name: $request->input('buyer.first_name'),
                last_name: $request->input('buyer.last_name'),
                email_address: $request->input('buyer.email_address'),
                phone_number: $request->input('buyer.phone_number'),
                zip_code: $request->input('buyer.zip_code'),
                city: $request->input('buyer.city'),
                country: $request->input('buyer.country'),
                shipping_address: $request->input('buyer.shipping_address')
            );

            $products = array_map(function ($product) {
                return new ProductDTO(
                    sku: $product['sku'],
                    name: $product['name'],
                    quantity: $product['quantity'],
                    unit_price: $product['unit_price'] ?? null,
                    currency: $product['currency'] ?? null
                );
            }, $request->input('products', []));

            $parcelData = new ParcelCreateDTO(
                fm_tracking_number: $request->input('fm_tracking_number'),
                uid: $request->input('uid'),
                buyer: $buyer,
                warehouse_id: $request->input('warehouse_id'),
                domestic_cargo_company: $request->input('domestic_cargo_company'),
                comment: $request->input('comment'),
                is_door: $request->boolean('is_door', false),
                is_micro: $request->boolean('is_micro', false),
                is_liquid: $request->boolean('is_liquid', false),
                products: $products,
                weight: $request->input('weight'),
                dimensions: $request->input('dimensions')
            );

            $result = $this->logisticsService->createOrUpdateParcel((array) $parcelData);

            return response()->json([
                'success' => true,
                'data' => $result['data']
            ]);

        } catch (UnitedLogisticsException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    public function getWarehouses(): JsonResponse
    {
        try {
            $result = $this->logisticsService->listWarehouses();

            return response()->json([
                'success' => true,
                'data' => $result['data']
            ]);

        } catch (UnitedLogisticsException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    public function inboundScan(Request $request): JsonResponse
    {
        try {
            $result = $this->logisticsService->inboundScan(
                $request->input('barcode'),
                $request->input('weight')
            );

            return response()->json([
                'success' => true,
                'data' => $result['data']
            ]);

        } catch (UnitedLogisticsException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    public function getParcelStates(string $barcode): JsonResponse
    {
        try {
            $result = $this->logisticsService->getParcelStates($barcode);

            return response()->json([
                'success' => true,
                'data' => $result['data']
            ]);

        } catch (UnitedLogisticsException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

}
