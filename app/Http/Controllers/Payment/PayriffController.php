<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\CombinedOrder;
use Illuminate\Http\Request;
use App\Services\PayriffService; // Servisi inject etmək üçün
// Və ya Fasad istifadə edirsinizsə:
// use App\Facades\Payriff;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session; // Sessiya istifadəsi üçün

class PayriffController extends Controller
{
    protected PayriffService $payriffService;

//    public function __construct(PayriffService $payriffService)
//    {
//        $this->payriffService = $payriffService;
//    }

    public function pay($request)
    {
        $langcode = strtoupper(str_replace('_', '-', app()->getLocale())) ?? 'EN';
        // Formdan və ya başqa mənbədən məlumatları alın
        // $request->validate([...]); // Validasiya əlavə edin
        $paymentType = Session::get('payment_type');
        $paymentData = Session::get('payment_data');
        $payment_method = $paymentData['payment_method'] ?? 'payriff';
        if($paymentType == 'cart_payment'){
            $combined_order = CombinedOrder::findOrFail(Session::get('combined_order_id'));
            $amount = $combined_order->grand_total;
        }

        $description = "Order Payment";

        $payriffService = new PayriffService();
        $orderResponse = $payriffService->createOrder(
            $amount,
            $description,"USD",$langcode
        // Digər parametrləri ehtiyacınıza görə ötürə bilərsiniz
        // currencyType: 'USD',
        // language: 'EN',
        // approveUrl: route('custom.approve.route'),
        // cancelUrl: route('custom.cancel.route'),
        // declineUrl: route('custom.decline.route')
        );

        // Fasadla istifadə:
        // $orderResponse = Payriff::createOrder($amount, $description);


        if (isset($orderResponse['paymentUrl']) && isset($orderResponse['orderId']) && isset($orderResponse['sessionId'])) {
            // Sifariş ID və Sessiya ID-ni sonradan status yoxlamaq üçün saxlayın
            // Məsələn, sessiyada və ya verilənlər bazasında
            Session::put('payriff_order_id', $orderResponse['orderId']);
            Session::put('payriff_session_id', $orderResponse['sessionId']);

            // İstifadəçini Payriff ödəniş səhifəsinə yönləndirin
            return redirect()->away($orderResponse['paymentUrl']);
        } else {
            Log::error('Payriff order creation failed in controller', ['response' => $orderResponse]);
            // Xəta mesajı göstərin
            return back()->with('error', 'Ödəniş sifarişi yaradıla bilmədi: ' . ($orderResponse['message'] ?? 'Naməlum xəta'));
        }
    }

    public function handlePayriffApprove(Request $request)
    {
        $orderId = $request->query('orderID'); // Payriff tərəfindən göndərilən
        $sessionId = $request->query('sessionID'); // Payriff tərəfindən göndərilən
        $status = $request->query('status'); // Payriff tərəfindən göndərilən

        // Əgər orderId və sessionId-ni sessiyada saxlamısınızsa, onları da yoxlaya bilərsiniz
        // $storedOrderId = Session::get('payriff_order_id');
        // $storedSessionId = Session::get('payriff_session_id');
        // if ($orderId !== $storedOrderId || $sessionId !== $storedSessionId) {
        //     Log::warning('Payriff callback mismatch:', ['request' => $request->all(), 'session' => Session::all()]);
        //     return response('Sessiya uyğunsuzluğu.', 400);
        // }

        if (empty($orderId) || empty($sessionId)) {
            Log::error('Payriff Approve: orderID və ya sessionID boşdur.', ['request' => $request->all()]);
            return redirect('/')->with('error', 'Ödəniş callback-i üçün orderID və ya sessionID tapılmadı.');
        }

        // Həmişə statusu Payriff-dən yenidən yoxlayın!
        $statusResponse = $this->payriffService->getOrderStatus($orderId, $sessionId);
        // Fasadla: $statusResponse = Payriff::getOrderStatus($orderId, $sessionId);

        if (isset($statusResponse['orderStatus']) && $statusResponse['orderStatus'] === 'APPROVED') {
            // Ödəniş uğurludur!
            // Verilənlər bazasında sifariş statusunu yeniləyin
            // İstifadəçiyə uğurlu mesaj göstərin
            Log::info('Payriff Payment Approved:', ['orderId' => $orderId, 'statusResponse' => $statusResponse]);
            Session::forget(['payriff_order_id', 'payriff_session_id']); // Sessiyanı təmizləyin
            return redirect('/')->with('success', 'Ödənişiniz uğurla qəbul edildi! Sifariş ID: ' . $orderId);
        } else {
            // Ödəniş uğursuz oldu və ya başqa statusdadır
            Log::error('Payriff Payment Not Approved or Error:', [
                'orderId' => $orderId,
                'status' => $status, // Payriff-dən gələn ilkin status
                'statusResponse' => $statusResponse // API-dən yoxlanmış status
            ]);
            return redirect('/')->with('error', 'Ödənişiniz təsdiqlənmədi. Status: ' . ($statusResponse['orderStatus'] ?? 'Naməlum'));
        }
    }

    public function handlePayriffCancel(Request $request)
    {
        $orderId = $request->query('orderID');
        Log::info('Payriff Payment Canceled by user:', ['orderId' => $orderId, 'request' => $request->all()]);
        Session::forget(['payriff_order_id', 'payriff_session_id']);
        return redirect('/')->with('info', 'Ödənişiniz ləğv edildi.');
    }

    public function handlePayriffDecline(Request $request)
    {
        $orderId = $request->query('orderID');
        Log::warning('Payriff Payment Declined:', ['orderId' => $orderId, 'request' => $request->all()]);
        Session::forget(['payriff_order_id', 'payriff_session_id']);
        return redirect('/')->with('error', 'Ödənişiniz rədd edildi.');
    }
}
