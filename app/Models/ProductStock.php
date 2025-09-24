<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class ProductStock extends Model
{
    use PreventDemoModeChanges;
    protected $casts = [
        'datas'    => 'array',
    ];
    protected $fillable = ['product_id', 'variant', 'sku', 'price', 'qty', 'image','scraped_item_id'];
    //
    public function product(){
    	return $this->belongsTo(Product::class);
    }

    public function wholesalePrices() {
        return $this->hasMany(WholesalePrice::class);
    }
}
