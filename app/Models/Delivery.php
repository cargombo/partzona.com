<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Delivery extends Model
{
    protected $guarded = [];
    protected $casts = [
        'user'              => 'json',
        'shipping_address'  => 'json',
        'activity_logs'     => 'array',
    ];
}
