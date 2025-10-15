<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDetail;

class TaobaoOrderService
{
    private static int $appKey = 503494;
    private static string $secretKey = "6irVZUUB5Va5BwdRPFrjbLenkWhbf5OF";
    private static string $accessToken = "37c66819338b4562e17675b8c5c4dbd0";
    private static string $apiUrl = "https://api.taobao.global/rest";

    /**
     * Generate Taobao API signature
     */
    private static function generateSign(array $params, string $path, string $secret): string
    {
        unset($params['sign']);
        ksort($params);

        $stringToSign = $path;
        foreach ($params as $key => $value) {
            if ($value !== '' && $value !== null) {
                $stringToSign .= $key . $value;
            }
        }

        $sign = strtoupper(hash_hmac('sha256', $stringToSign, $secret));

        return $sign;
    }

    public static function createOrder(Order $order)
    {
        $shippingAddress = json_decode($order->shipping_address, true);

        $orderDetails = OrderDetail::where('order_id', $order->id)->get();

        if ($orderDetails->isEmpty()) {
            return [
                'success' => false,
                'error' => 'No order details found'
            ];
        }

        $orderLineList = [];
        $totalAmount = 0;

        foreach ($orderDetails as $key => $detail) {
            $product = \App\Models\Product::find($detail->product_id);

            if (!$product) {
                \Log::warning('Product not found', ['product_id' => $detail->product_id]);
                continue;
            }

            if (!$product->scraped_item_id) {
                \Log::warning('Product missing scraped_item_id', [
                    'product_id' => $detail->product_id,
                    'product_name' => $product->name
                ]);
                continue;
            }


            $productStock = \App\Models\ProductStock::where('product_id', $detail->product_id)
                ->where('variant', $detail->variation)
                ->first();

            if (!$productStock || !$productStock->sku) {
                \Log::warning('Product missing SKU', [
                    'product_id' => $detail->product_id,
                    'variation' => $detail->variation
                ]);
            }

            $priceInFen = (int)($detail->price * 100);
            $totalAmount += ($priceInFen * $detail->quantity);

            $orderLine = [
                'itemId' => 2048545554413405,
                'orderLineNo' => (string)($key + 1),
                'quantity' => (string)$detail->quantity,
                'price' => (int)$priceInFen,
                'currency' => 'CNY',
                'title' => $product->name,
            ];

            if ($productStock && $productStock->sku) {
                $orderLine['skuId'] = 10297700095837;
//                dd($orderLine['skuId']);
            }



            if (!empty($detail->variation)) {
                $orderLine['orderRemark'] = $detail->variation;
            }

            $orderLineList[] = $orderLine;
        }

        if (empty($orderLineList)) {
            return [
                'success' => false,
                'error' => 'No valid products found for Taobao order. Check if products have scraped_item_id.'
            ];
        }

        $timestamp = (string)round(microtime(true) * 1000);

        $receiver = [
            'name' => $shippingAddress['name'] ?? 'Unknown',
            'mobile_phone' => $shippingAddress['phone'] ?? '994501234567',
            'phone' => $shippingAddress['phone'] ?? '994501234567',
            'country' => $shippingAddress['country'] ?? 'Azerbaijan',
            'state' => $shippingAddress['state'] ?? 'Baku',
            'city' => $shippingAddress['city'] ?? 'Baku',
            'district' => $shippingAddress['district'] ?? '',
            'address' => $shippingAddress['address'] ?? '',
            'zip' => $shippingAddress['postal_code'] ?? '1000',
            'taxId' => ''
        ];

        $warehouseAddress = [
            'name' => 'Guangzhou Boleti Trading Co., Ltd.',
            'mobile_phone' => '15626113000',
            'phone' => '15626113000',
            'country' => '中国',
            'state' => '广东',
            'city' => '广州市',
            'district' => '白云区',
            'address' => '夏花二路961号横和沙仓库814仓',
            'zip' => '510000',
            'taxId' => ''
        ];

        $token = TaobaoAuthService::getValidToken();

        // Fallback to hardcoded token if auth service fails
        if (!$token) {
            $token = self::$accessToken;
            \Log::info('Using fallback access token for order creation');
        }

        $receiverJson = json_encode($receiver, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $orderLineListJson = json_encode($orderLineList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $warehouseAddressJson = json_encode($warehouseAddress, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $params = [
            'access_token' => $token,
            'app_key' => (string)self::$appKey,
            'channel_order_type' => 'NORMAL',
            'need_supply_chain_service' => 'false',
            'need_sys_retry' => 'true',
            'order_line_list' => $orderLineListJson,
            'order_remark' => $shippingAddress['additional_info'] ?? '-',
            'order_source' => 'web',
            'outer_purchase_id' => $order->id,
            'purchase_amount' => (string)$totalAmount,
            'receiver' => $receiverJson,
            'seller_order_number' => $order->code,
            'sign_method' => 'sha256',
            'support_partial_success' => 'false',
            'timestamp' => $timestamp,
            'warehouse_address_info' => $warehouseAddressJson,
        ];
//        dd($params);
        $path = '/purchase/order/create';
        $sign = self::generateSign($params, $path, self::$secretKey);
        $params['sign'] = $sign;

        // CURL nümunəsinə uyğun - JSON body kimi göndər
        $jsonBody = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $ch = curl_init(self::$apiUrl . $path);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json;charset=utf-8',
                'Content-Length: ' . strlen($jsonBody)
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
//            dd($response);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                \Log::error('cURL Error:', ['error' => $error]);
                curl_close($ch);

                return [
                    'success' => false,
                    'error' => $error
                ];
            }

            curl_close($ch);

            if (empty($response)) {
                return [
                    'success' => false,
                    'error' => 'Empty response from Taobao API',
                    'http_code' => $httpCode
                ];
            }

            $responseData = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'error' => 'Invalid JSON response: ' . json_last_error_msg(),
                    'raw_response' => $response
                ];
            }

            // Check for error in response
            if (isset($responseData['error_code']) && $responseData['error_code'] !== '0') {
                \Log::error('Taobao API Error:', $responseData);

                return [
                    'success' => false,
                    'error' => $responseData['error_msg'] ?? 'Unknown error',
                    'error_code' => $responseData['error_code'] ?? null,
                    'response' => $responseData
                ];
            }

            // Check success
            $success = false;
            if (isset($responseData['success'])) {
                $success = ($responseData['success'] === true || $responseData['success'] === 'true');
            }

            // Log the API response for debugging
            \Log::info('Taobao API Response', [
                'order_id' => $order->id,
                'success' => $success,
                'has_data' => isset($responseData['data']),
                'response' => $responseData
            ]);

            if ($success && isset($responseData['data'])) {
                $order->taobao_order_status = 'created';
//                dd($order);

                $data = $responseData['data'] ?? null;
                $orderList = $data['order_list'] ?? null;
//                dd($orderList[0]['purchase_id']);
                $order->purchase_id = $orderList && isset($orderList[0]['purchase_id'])
                    ? $orderList[0]['purchase_id']
                    : null;
                $order->save();
                \Log::info('Taobao order created successfully', [
                    'order_id' => $order->id,
                    'purchase_id' => $order->purchase_id,
                    'taobao_order_status' => $order->taobao_order_status
                ]);

            }

            return [
                'success' => $success,
                'response' => $responseData,
                'http_code' => $httpCode
            ];

        } catch (\Exception $e) {
            \Log::error('Taobao order creation error: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public static function payOrder($purchaseIds)
    {
        if (empty($purchaseIds) || !is_array($purchaseIds)) {
            return [
                'success' => false,
                'error' => 'Invalid purchase IDs'
            ];
        }

        $token = TaobaoAuthService::getValidToken();

        // Fallback to hardcoded token if auth service fails
        if (!$token) {
            $token = self::$accessToken;
            \Log::info('Using fallback access token for payment');
        }

        $timestamp = (string)round(microtime(true) * 1000);

        // JSON array string (without encoding)
        $purchaseIdListJson = json_encode($purchaseIds);

        // Parameters for signature calculation
        $params = [
            'access_token' => $token,
            'app_key' => (string)self::$appKey,
            'purchaseOrderIdList' => $purchaseIdListJson,  // API expects purchaseOrderIdList
            'sign_method' => 'sha256',
            'timestamp' => $timestamp,
        ];

        // Generate signature
        $path = '/purchase/order/batch/pay';
        $sign = self::generateSign($params, $path, self::$secretKey);
        $params['sign'] = $sign;

        // Create JSON body - NO URL ENCODING
        $jsonBody = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $ch = curl_init(self::$apiUrl . $path);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json;charset=utf-8',
                'Content-Length: ' . strlen($jsonBody)
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            \Log::info('Taobao pay order response', [
                'response' => $response,
                'http_code' => $httpCode
            ]);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                \Log::error('cURL Error in payOrder:', ['error' => $error]);
                curl_close($ch);

                return [
                    'success' => false,
                    'error' => $error
                ];
            }

            curl_close($ch);

            if (empty($response)) {
                return [
                    'success' => false,
                    'error' => 'Empty response from Taobao API',
                    'http_code' => $httpCode
                ];
            }

            $responseData = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'error' => 'Invalid JSON response: ' . json_last_error_msg(),
                    'raw_response' => $response
                ];
            }

            if (isset($responseData['error_code']) && $responseData['error_code'] !== '0') {
                \Log::error('Taobao Pay API Error:', $responseData);

                return [
                    'success' => false,
                    'error' => $responseData['error_msg'] ?? 'Unknown error',
                    'error_code' => $responseData['error_code'] ?? null,
                    'response' => $responseData
                ];
            }

            $success = false;
            if (isset($responseData['success'])) {
                $success = ($responseData['success'] === true || $responseData['success'] === 'true');
            }

            if ($success) {
                $data = $responseData['data'] ?? null;
                $payFailedResults = $data['pay_failed_results'] ?? [];
                $payFailedIds = $data['pay_failure_purchase_order_ids'] ?? [];

                // Mark successfully paid orders
                foreach ($purchaseIds as $purchaseId) {
                    if (!in_array($purchaseId, $payFailedIds) && !in_array((string)$purchaseId, $payFailedIds)) {
                        $order = Order::where('purchase_id', $purchaseId)->first();
                        if ($order) {
                            $order->taobao_order_status = 'paid';
                            $order->save();
                        }
                    }
                }

                // Log failed payments
                if (!empty($payFailedResults)) {
                    foreach ($payFailedResults as $failure) {
                        \Log::warning('Payment failed for purchase order', [
                            'purchase_id' => $failure['gspOrderId'] ?? null,
                            'error_code' => $failure['errorCode'] ?? null,
                            'error_message' => $failure['errorMessage'] ?? null
                        ]);
                    }
                }
            }

            return [
                'success' => $success,
                'response' => $responseData,
                'http_code' => $httpCode,
                'payment_failures' => $responseData['data']['pay_failed_results'] ?? []
            ];

        } catch (\Exception $e) {
            \Log::error('Taobao pay order error: ' . $e->getMessage(), [
                'purchase_ids' => $purchaseIds,
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Query order status from Taobao
     * Based on the pattern from /purchase/order/batch/pay, trying /purchase/order/batch/query
     */
    public static function queryOrderStatus($purchaseIds)
    {
        if (empty($purchaseIds) || !is_array($purchaseIds)) {
            return [
                'success' => false,
                'error' => 'Invalid purchase IDs'
            ];
        }

        $token = TaobaoAuthService::getValidToken();

        // Fallback to hardcoded token if auth service fails
        if (!$token) {
            $token = self::$accessToken;
            \Log::info('Using fallback access token for order query');
        }

        $timestamp = (string)round(microtime(true) * 1000);

        // JSON array string - using same parameter name as pay endpoint
        $purchaseIdListJson = json_encode($purchaseIds);

        // Parameters for signature calculation
        $params = [
            'access_token' => $token,
            'app_key' => (string)self::$appKey,
            'purchaseOrderIdList' => $purchaseIdListJson,
            'sign_method' => 'sha256',
            'timestamp' => $timestamp,
        ];

        // Generate signature - trying /purchase/order/get (common REST pattern)
        $path = '/purchase/order/get';
        $sign = self::generateSign($params, $path, self::$secretKey);
        $params['sign'] = $sign;

        // Create JSON body
        $jsonBody = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $ch = curl_init(self::$apiUrl . $path);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json;charset=utf-8',
                'Content-Length: ' . strlen($jsonBody)
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                \Log::error('cURL Error in queryOrderStatus:', ['error' => $error]);
                curl_close($ch);

                return [
                    'success' => false,
                    'error' => $error
                ];
            }

            curl_close($ch);

            if (empty($response)) {
                return [
                    'success' => false,
                    'error' => 'Empty response from Taobao API',
                    'http_code' => $httpCode
                ];
            }

            $responseData = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'error' => 'Invalid JSON response: ' . json_last_error_msg(),
                    'raw_response' => $response
                ];
            }

            if (isset($responseData['error_code']) && $responseData['error_code'] !== '0') {
                \Log::error('Taobao Query API Error:', $responseData);

                return [
                    'success' => false,
                    'error' => $responseData['error_msg'] ?? 'Unknown error',
                    'error_code' => $responseData['error_code'] ?? null,
                    'response' => $responseData
                ];
            }

            $success = false;
            if (isset($responseData['success'])) {
                $success = ($responseData['success'] === true || $responseData['success'] === 'true');
            }

            // Log the query response
            \Log::info('Taobao order query response', [
                'purchase_ids' => $purchaseIds,
                'success' => $success,
                'response' => $responseData
            ]);

            return [
                'success' => $success,
                'response' => $responseData,
                'http_code' => $httpCode,
                'orders' => $responseData['data']['order_list'] ?? []
            ];

        } catch (\Exception $e) {
            \Log::error('Taobao query order error: ' . $e->getMessage(), [
                'purchase_ids' => $purchaseIds,
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
