<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\TaobaoOrderService;
use Illuminate\Console\Command;

class SyncOrderStatus extends Command
{
    protected $signature = 'sync:order-status {--purchase_id= : Check specific purchase ID}';

    protected $description = 'Sync order status from Taobao API';

    public function handle()
    {
        $specificPurchaseId = $this->option('purchase_id');

        if ($specificPurchaseId) {
            // Check specific order
            $orders = Order::where('purchase_id', $specificPurchaseId)
                ->whereNotNull('purchase_id')
                ->get();
        } else {
            // Check all orders with purchase_id that are not in final status
            $orders = Order::whereNotNull('purchase_id')
                ->whereIn('taobao_order_status', ['created', 'paid', 'pending'])
                ->get();
        }

        if ($orders->isEmpty()) {
            $this->info('No orders found to sync');
            return Command::SUCCESS;
        }

        $this->info("Found {$orders->count()} order(s) to check");

        // Group orders by batch (max 20 per request)
        $batches = $orders->chunk(20);

        foreach ($batches as $batch) {
            $purchaseIds = $batch->pluck('purchase_id')->toArray();

            $this->info("Checking batch of " . count($purchaseIds) . " orders...");

            $result = TaobaoOrderService::queryOrderStatus($purchaseIds);

            if (!$result['success']) {
                $this->error("Failed to query orders: " . ($result['error'] ?? 'Unknown error'));
                continue;
            }

            $taobaoOrders = $result['orders'] ?? [];

            if (empty($taobaoOrders)) {
                $this->warn("No order data returned from Taobao");
                continue;
            }

            // Update local orders based on Taobao response
            foreach ($taobaoOrders as $taobaoOrder) {
                $purchaseId = $taobaoOrder['purchase_id'] ?? null;
                $orderStatus = $taobaoOrder['order_status'] ?? null;
                $paymentStatus = $taobaoOrder['payment_status'] ?? null;

                if (!$purchaseId) {
                    continue;
                }

                $localOrder = Order::where('purchase_id', $purchaseId)->first();

                if (!$localOrder) {
                    $this->warn("Local order not found for purchase_id: {$purchaseId}");
                    continue;
                }

                $oldStatus = $localOrder->taobao_order_status;
                $updated = false;

                // Map Taobao status to local status
                $newStatus = $this->mapTaobaoStatus($orderStatus, $paymentStatus);

                if ($newStatus && $oldStatus !== $newStatus) {
                    $localOrder->taobao_order_status = $newStatus;
                    $localOrder->save();
                    $updated = true;

                    $this->info("✓ Order {$localOrder->code} (Purchase ID: {$purchaseId})");
                    $this->line("  Status changed: {$oldStatus} → {$newStatus}");
                    $this->line("  Taobao Status: {$orderStatus}, Payment: {$paymentStatus}");
                } else {
                    $this->line("○ Order {$localOrder->code} - No change (Status: {$oldStatus})");
                }
            }
        }

        $this->info('✓ Order status sync completed!');
        return Command::SUCCESS;
    }

    /**
     * Map Taobao order status to local status
     */
    private function mapTaobaoStatus($orderStatus, $paymentStatus)
    {
        // Common Taobao statuses:
        // WAIT_BUYER_PAY - waiting for payment
        // WAIT_SELLER_SEND_GOODS - paid, waiting for shipment
        // WAIT_BUYER_CONFIRM_GOODS - shipped, waiting for confirmation
        // TRADE_FINISHED - completed
        // TRADE_CLOSED - cancelled/closed

        $statusMap = [
            'WAIT_BUYER_PAY' => 'created',
            'WAIT_SELLER_SEND_GOODS' => 'paid',
            'WAIT_BUYER_CONFIRM_GOODS' => 'shipped',
            'TRADE_FINISHED' => 'completed',
            'TRADE_CLOSED' => 'cancelled',
            'TRADE_CLOSED_BY_TAOBAO' => 'cancelled',
        ];

        return $statusMap[$orderStatus] ?? null;
    }
}
