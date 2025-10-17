<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopTranslation extends Model
{
    protected $fillable = ['shop_id', 'name', 'lang'];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
