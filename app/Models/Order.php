<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class Order extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = [
        'combined_order_id',
        'user_id',
        'guest_id',
        'seller_id',
        'shipping_address',
        'additional_info',
        'shipping_type',
        'order_from',
        'pickup_point_id',
        'carrier_id',
        'delivery_status',
        'payment_type',
        'payment_status',
        'payment_details',
        'grand_total',
        'coupon_discount',
        'code',
        'tracking_code',
        'date',
        'viewed',
        'delivery_viewed',
        'payment_status_viewed',
        'commission_calculated',
        'notified',
        'consolidation',
        'manual_payment',
        'manual_payment_data',
        'taobao_order_status',
        'purchase_id',
    ];

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function refund_requests()
    {
        return $this->hasMany(RefundRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shop()
    {
        return $this->hasOne(Shop::class, 'user_id', 'seller_id');
    }

    public function pickup_point()
    {
        return $this->belongsTo(PickupPoint::class);
    }

    public function carrier()
    {
        return $this->belongsTo(Carrier::class);
    }

    public function affiliate_log()
    {
        return $this->hasMany(AffiliateLog::class);
    }

    public function club_point()
    {
        return $this->hasMany(ClubPoint::class);
    }

    public function delivery_boy()
    {
        return $this->belongsTo(User::class, 'assign_delivery_boy', 'id');
    }

    public function proxy_cart_reference_id()
    {
        return $this->hasMany(ProxyPayment::class)->select('reference_id');
    }

    public function commissionHistory()
    {
        return $this->hasOne(CommissionHistory::class);
    }
}
