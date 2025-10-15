<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\TaobaoOrderService;
use Illuminate\Console\Command;

class TaoBaoOrder extends Command
{
    protected $signature = 'sync:orders-to-taobao {--order_id= : Process only specific order ID}';

    protected $description = 'Send local orders to Taobao via API';

    public function handle()
    {
        // Check if specific order ID is provided
        $orderId = $this->option('order_id');

        if ($orderId) {
            $orders = Order::where('id', $orderId)
                ->where('taobao_order_status', 'pending')
                ->with('shop', 'orderDetails')
                ->get();
        } else {
            $orders = Order::where('taobao_order_status', 'pending')
                ->with('shop', 'orderDetails')
                ->get();
        }

        foreach($orders as $order) {
            $this->info("Processing order ID: {$order->id}, Code: {$order->code}");
            $result = TaobaoOrderService::createOrder($order);

            if (isset($result['success']) && $result['success']) {
                $this->info("✓ Order {$order->id} processed successfully");
            } else {
                $error = $result['error'] ?? 'Unknown error';
                $this->error("✗ Order {$order->id} failed: {$error}");
            }
        }

        return Command::SUCCESS;
    }
}
