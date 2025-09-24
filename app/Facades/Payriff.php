<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array|null createOrder(float $amount, string $description, ?string $currencyType = null, ?string $language = null, ?string $approveUrl = null, ?string $cancelUrl = null, ?string $declineUrl = null)
 * @method static array|null getOrderStatus(string $orderId, string $sessionId, ?string $language = null)
 *
 * @see \App\Services\PayriffService
 */
class Payriff extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\PayriffService::class;
    }
}
