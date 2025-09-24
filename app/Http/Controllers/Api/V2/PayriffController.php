<?php


namespace App\Http\Controllers\Api\V2;


use App\Http\Controllers\CheckoutController;
use App\Models\BusinessSetting;
use App\Models\CombinedOrder;
use App\Models\Order;
use Illuminate\Http\Request;
use Redirect;

class PayriffController extends Controller
{

    public function approve(Request $request)
    {
        $payment = session()->get('payment_data');
        $payment_type = ["status" => "Success"];

        return (new CheckoutController)->checkout_done($payment['combined_order_id'], json_encode($payment_type));
    }
    public function cancel(Request $request)
    {
        flash(translate('Payment is cancelled'))->error();
        return redirect()->route('home');
    }
    public function decline(Request $request)
    {
        dd($request->all(),'decline');
    }
    public function callback(Request $request)
    {
        dd($request->all());
    }
}
