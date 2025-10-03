<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\TaobaoOrderService;
use Illuminate\Console\Command;

class TaoBaoOrder extends Command
{
    protected $signature = 'sync:orders-to-taobao';

    protected $description = 'Send local orders to Taobao via API';

    public function handle()
    {
        $orders = Order::where('taobao_order_status', 'pending')->with('shop', 'orderDetails')->get();
        foreach($orders as $order) {
//            dd($order);

            TaobaoOrderService::createOrder($order);
        }
        return Command::SUCCESS;
    }
}
