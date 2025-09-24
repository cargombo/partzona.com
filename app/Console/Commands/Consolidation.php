<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use Illuminate\Console\Command;
use ZipArchive;
use Artisan;
use DB;

class Consolidation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:consolidation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Consolidation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Load orders with their details in a single query using eager loading
        $orders = Order::where('delivery_status', 'picked_up')
            ->with(['orderDetails' => function($query) {
                $query->where('consolidation', 0);
            }])
            ->get();

        $tazbeebexService = new \App\Services\Tazbeebex();
        $response = [];

        foreach ($orders as $order) {
            $this->info('Start');
            $detailsToUpdate = [];

            foreach ($order->orderDetails as $orderDetail) {
                $updateResult = $tazbeebexService->updateConsolidation($orderDetail->tracking_code);
                $response[] = $updateResult;

                if (isset($updateResult['status']) && $updateResult['status']) {
                    $detailsToUpdate[] = $orderDetail->id;
                }
            }

            // Bulk update order details in a single query
            if (!empty($detailsToUpdate)) {
                $this->info('OrderDetail updated');
                OrderDetail::whereIn('id', $detailsToUpdate)->update(['consolidation' => 1]);
            }
            $this->info('End');
            // Uncomment if needed
            // $order->consolidation = 1;
            // $order->save();
        }
        $this->info(count($response)." toplan");
        return ;
        // Remove dd() calls in production code
    }

}