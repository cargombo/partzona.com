<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TazbeebexController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('tazbeebex.auth');
    }

    /**
     * Change delivery number for a package.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */


    /**
     * Update delivery status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request)
    {

        try {
            // Validate the request data
            $validated = $request->validate([
                'parent_custom_id' => 'required|string',
                'status' => 'required|string'
            ]);

            $child_custom_id  = $request->get('child_custom_id',null);
            $parent_custom_id = $request->get('parent_custom_id',null);
            $status           = $request->get('status',null);
            $order_id = null;

            $delivery = Delivery::where('tazbeebex_delivery_number',$parent_custom_id)->first();
            if($status && $status == 'consolidation_package'){

                if(is_array($request->child_custom_id) && count($request->child_custom_id)>0){

                    $deliveries = Delivery::whereIn('tazbeebex_delivery_number',$child_custom_id)->get();
                    foreach ($deliveries as $_delivery){

                        $old_tazbeebex_delivery_number        =  $_delivery->tazbeebex_delivery_number;
                        $_delivery->tazbeebex_delivery_number = $parent_custom_id;


                        $order_detail                     = OrderDetail::where('delivery_id', $_delivery->id)->first();
                        $order_detail->tracking_code      = $parent_custom_id;
                        $order_detail->save();
                        $order_id = $order_detail->order_id;
                        $currentLogs = $_delivery->activity_logs ?? [];
                        $_delivery->activity_logs = [
                            ...$currentLogs,
                            [
                                'activity' => "Update tazbeebex_delivery_number: Old:{$old_tazbeebex_delivery_number}, New:{$request->parent_custom_id}",
                                'date' => now(),
                                'admin_id' => 'API'
                            ]
                        ];
                        $_delivery->save();

                    }
                }else{
                    $order_detail                     = OrderDetail::where('delivery_id', $delivery->id)->first();
                    $order_id = $order_detail->order_id;
                }

                $order          = Order::where('id', $order_id)->first();
                $order->tracking_code = $parent_custom_id;
                $order->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Delivery status updated successfully',
//                'data' => $delivery
                ]);

            }



            if (!$delivery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Delivery not found'
                ], 404);
            }
            $status = intval($status);
            if(!$status){

                $delivery->delivery_status = 'picked_up';
                $delivery->save();
                $order_detail                     = OrderDetail::where('delivery_id', $delivery->id)->first();
                $order_detail->delivery_status = 'picked_up';
                $order_detail->save();


                $order          = Order::where('id', $order_detail->order_id)->first();
                $_order_details  =  OrderDetail::where('order_id',$order->id)
                    ->where('delivery_status','!=','picked_up')
                    ->count();
                if($_order_details == 0){
                    $order->delivery_status = 'picked_up';
                    $order->save();
                }
            }
            elseif($status && $status == 1){
                $delivery->delivery_status = 'on_the_way';
                $delivery->save();
                $order_detail                     = OrderDetail::where('delivery_id', $delivery->id)->first();
                $order_detail->delivery_status    = 'on_the_way';
                $order_detail->save();

                $order          = Order::where('id', $order_detail->order_id)->first();
                $order->delivery_status    = 'on_the_way';
                $order->save();

            }

            return response()->json([
                'success' => true,
                'message' => 'Delivery status updated successfully',
//                'data' => $delivery
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update delivery status: ' . $e->getMessage().' '.$e->getLine()
            ], 500);
        }
    }
}