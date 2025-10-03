<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\TaobaoOrderService;
use Illuminate\Console\Command;

class OrderPay extends Command
{
    protected $signature = 'sync:pay';
    protected $description = 'Sync orders to Taobao payment';

    public function handle()
    {
        $orders = Order::where('taobao_order_status', 'created')
            ->whereNotNull('purchase_id')
            ->pluck('purchase_id')
            ->toArray();

        if (!empty($orders)) {
            TaobaoOrderService::payOrder($orders);
        }

        return Command::SUCCESS;
    }
}

