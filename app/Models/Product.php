<?php

namespace App\Models;

use App;
use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class Product extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = [
        'name',
        'description',
        'unit_price',
        'purchase_price',
        'photos',
        'thumbnail_img',
        'current_stock',
        'scraped_date',
        'scraped_full_data',
        'meta_title',
        'meta_description',
        'meta_img',
        'choice_options',
        'attributes',
        'discount',
        'discount_type',
        'mp_id',
        'colors',
        'variant_product',
        'slug',
        'scraped_item_id',
        'scraped_item_url',
        'category_id',
        'user_id',
        'approved',
        'published',
        'auction_product',
        'digital',
        'scraped_basic_data',
        'brand_id',
        'auto_model_id',
        'auto_part_id'
    ];

    protected $casts = [
        'scraped_basic_data'    => 'array',
        'scraped_date'          => 'datetime',
    ];
    protected $guarded = ['choice_attributes'];

    protected $with = ['product_translations', 'taxes', 'thumbnail'];

    public function getTranslation($field = '', $lang = false)
    {
        $lang = $lang == false ? App::getLocale() : $lang;
        $product_translations = $this->product_translations->where('lang', $lang)->first();
        return $product_translations != null ? $product_translations->$field : $this->$field;
    }

    public function product_translations()
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function main_category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    public function frequently_bought_products()
    {
        return $this->hasMany(FrequentlyBoughtProduct::class);
    }

    public function product_categories()
    {
        return $this->hasMany(ProductCategory::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function product_queries()
    {
        return $this->hasMany(ProductQuery::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }

    public function taxes()
    {
        return $this->hasMany(ProductTax::class);
    }

    public function flash_deal_products()
    {
        return $this->hasMany(FlashDealProduct::class);
    }

    public function bids()
    {
        return $this->hasMany(AuctionProductBid::class);
    }

    public function thumbnail()
    {
        return $this->belongsTo(Upload::class, 'thumbnail_img');
    }

    public function scopePhysical($query)
    {
        return $query->where('digital', 0);
    }

    public function scopeDigital($query)
    {
        return $query->where('digital', 1);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function scopeIsApprovedPublished($query)
    {
        return $query->where('approved', '1')->where('published', 1);
    }

    public function last_viewed_products()
    {
        return $this->hasMany(LastViewedProduct::class);
    }

    public function productUpdate()
    {
        return $this->hasOne(ProductUpdate::class);
    }

    public function autoModel()
    {
        return $this->belongsTo(AutoModel::class, 'auto_model_id');
    }
}
