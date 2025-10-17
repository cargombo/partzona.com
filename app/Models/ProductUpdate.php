<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductUpdate extends Model
{
    protected $table = 'product_updates';

    protected $fillable = [
        'product_id',
        'scraped_item_id',
        'details_fetched',
        'error_message',
        'fetched_at',
    ];

    protected $casts = [
        'details_fetched' => 'boolean',
        'fetched_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
