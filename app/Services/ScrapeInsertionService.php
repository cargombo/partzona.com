<?php

namespace App\Services;

use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Color;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\ProductTranslation;
use App\Models\Upload;
use App\Services\Amazon;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use App\Models\ProductAttribute;
use Exception;
use Carbon\Carbon;
use App\Models\Attribute;
use App\Models\AttributeCategory;
use App\Utility\CategoryUtility;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
class ScrapeInsertionService
{
    /**
     * Search products on Amazon and insert them into database
     *
     * @param string $keyword
     * @param int|null $categoryId
     *
     * @return array
     */
    public static function searchAndInsertProducts(string $keyword, $category_id, $page = null)
    {
        try {

            $cacheKey = $keyword;

           if ($page && is_numeric($page)) {
                $cacheKey .= '_page_' . $page;
            }

            $cachedProducts = Cache::get($cacheKey);

            if ($cachedProducts !== null) {
               return $cachedProducts;
            }
            if ($page && is_numeric($page)) {
                $searchQuery = "{$keyword}&page={$page}";
            }
            $searchData = Taobao::scrapeSearch($keyword);
//            dd($searchData);
            $products = [];
            if (!isset($searchData['data'])) {
                return $products;
            }
            foreach ($searchData['data'] as $data) {
                if (!isset($data['itemId'])) {
                    return $products;
                }

                $product = Product::where('scraped_item_id', $data['itemId'])->first();

                if (!$product) {
                    $product = new Product();
                }
                $price = 0;
                if (isset($data['price'])) {
                    $price = $data['price'];
                }


                $product->name           = isset($data['title_az']) ? $data['title_az'] : $data['title'];
                $product->added_by       = 'admin';
                $product->user_id        = 11;
                $product->category_id    = $category_id;
                $product->unit_price     = $price;
                $product->purchase_price = $price * 0.8;
                $product->save();

                // Handle image upload
                $imageUrl = $data['mainImgUrl'] ?? null;
                if ($imageUrl) {
                    $upload = Upload::create([
                        'file_original_name' => null,
                        'file_name'          => $imageUrl,
                        'user_id'            => $product->user_id,
                        'extension'          => 0,
                        'type'               => 'image',
                        'file_size'          => 0
                    ]);

                    $product->photos        = $upload->id;
                    $product->thumbnail_img = $upload->id;
                }

                // Set remaining product data
                $product->variations                       = json_encode([]);
                $product->todays_deal                      = 0;
                $product->published                        = 1;
                $product->approved                         = 1;
                $product->auction_product                  = 0;
                $product->wholesale_product                = 0;
                $product->stock_visibility_state           = 'quantity';
                $product->cash_on_delivery                 = 1;
                $product->featured                         = 0;
                $product->seller_featured                  = 0;
                $product->unit                             = 'pc';
                $product->current_stock                    = 10;
                $product->slug                             = \Str::slug(isset($data['title_az']) ? $data['title_az'] : $data['title']);
                $product->frequently_bought_selection_type = 'product';
                $product->rating                           = $data['rating'] ?? 0;
                $product->scraped_item_id                  = $data['itemId'];
                $product->scraped_basic_data               = $data;
                $product->save();
                $product->video_provider  = 'youtube';
                $product->variant_product = 0;
                $product->attributes      = json_encode([]);
                $product->choice_options  = json_encode([]);
                $product->colors          = json_encode([]);
                $products[]               = $product;

            }
            Cache::put($cacheKey, $products, now()->addHours(24));
            return $products;
        }
        catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Insert a product into the database
     *
     * @param array $productData
     * @param int|null $categoryId
     *
     * @return int
     */


    public static function insertProduct($slug)
    {
        $product = Product::select('id', 'scraped_item_id', 'scraped_item_url', 'scraped_date', 'category_id', 'user_id')
            ->where('auction_product', 0)
            ->where('slug', $slug)
            ->where('approved', 1)
            ->first();

        if (!$product) {
            return ['error' => 'Product not found'];
        }

        try {
            // Taobao-dan məhsul məlumatlarını çək
            $response = Taobao::scrapeProduct($product->scraped_item_id);

            if (!$response['success'] || !isset($response['data'])) {
                return ['error' => 'Failed to fetch product data'];
            }

            $searchData = $response['data'];

            $price = $searchData['price'] ?? 0;
            $promotionPrice = $searchData['promotion_price'] ?? $price;

            $photos = [];
            if (isset($searchData['pic_urls']) && count($searchData['pic_urls']) > 0) {
                foreach ($searchData['pic_urls'] as $key => $imageUrl) {
                    if ($key > 0) { // İlk şəkil thumbnail kimi istifadə olunacaq
                        $upload = Upload::updateOrCreate(
                            ['file_name' => $imageUrl],
                            [
                                'file_original_name' => null,
                                'user_id'            => $product->user_id,
                                'extension'          => 'jpg',
                                'type'               => 'image',
                                'file_size'          => 0
                            ]
                        );
                        $photos[] = $upload->id;
                    }
                }
            }

            $thumbnailImg = null;
            if (isset($searchData['pic_urls'][0])) {
                $thumbnail = Upload::updateOrCreate(
                    ['file_name' => $searchData['pic_urls'][0]],
                    [
                        'file_original_name' => null,
                        'user_id'            => $product->user_id,
                        'extension'          => 'jpg',
                        'type'               => 'image',
                        'file_size'          => 0
                    ]
                );
                $thumbnailImg = $thumbnail->id;
            }

            $description = $searchData['description'] ?? '';

            if (isset($searchData['sku_list']) && count($searchData['sku_list']) > 0) {
                foreach ($searchData['sku_list'] as $sku) {
                    $productStock = ProductStock::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'sku'        => $sku['mp_skuId']
                        ],
                        [
                            'scraped_item_id' => $searchData['mp_id'],
                            'variant'         => 'default',
                            'price'           => $sku['price'] / 100,
                            'qty'             => $sku['quantity'],
                            'image'           => null
                        ]
                    );
                }
            } else {
                ProductStock::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'sku'        => $searchData['mp_id']
                    ],
                    [
                        'scraped_item_id' => $searchData['mp_id'],
                        'variant'         => 'default',
                        'price'           => $price / 100,
                        'qty'             => $searchData['quantity'] ?? 0,
                        'image'           => null
                    ]
                );
            }

            $product->name                = $searchData['title'];
            $product->description         = $description;
            $product->unit_price          = $promotionPrice / 100;
            $product->purchase_price      = $price / 100;
            $product->photos              = implode(',', $photos);
            $product->thumbnail_img       = $thumbnailImg;
            $product->current_stock       = $searchData['quantity'] ?? 0;
            $product->scraped_date        = now();
            $product->scraped_full_data   = json_encode($searchData);
            $product->meta_title          = $searchData['title'];
            $product->meta_description    = strip_tags($description);

            if ($promotionPrice < $price) {
                $discountAmount = $price - $promotionPrice;
                $product->discount = $discountAmount / 100;
                $product->discount_type = 'amount';
            }

            $product->save();

            return [
                'success' => true,
                'product' => $product,
                'message' => 'Product updated successfully'
            ];

        } catch (\Exception $e) {
            \Log::error('Product insert error: ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'product_id' => $product->id ?? null
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ];
        }
    }

    /**
     * Insert product images
     *
     * @param int $productId
     * @param array $images
     *
     * @return void
     */
    public static function insertProductImages(int $productId, array $images)
    : void {
        // Məhsulun şəkillərini ProductImage modelinə əlavə edir
        // Hər bir şəklin URL-ni, sırasını və digər məlumatlarını əlavə edir
        // Əgər şəkillər artıq yüklənibsə, təkrarları aradan qaldırır
        // Şəkillərin düzgün formatda saxlanılmasını təmin edir
    }

    /**
     * Insert product attributes
     *
     * @param int $productId
     * @param array $attributes
     *
     * @return void
     */


    public static function insertProductAttributes(int $productId, array $attributes)
    : void {
        // Məhsulun atributlarını (rəng, ölçü, material və s.) əlavə edir
        // ProductAttribute modelindən istifadə edərək verilənlər bazasına yazır
        // Atributları düzgün formatda təşkil edir
        // Mövcud atributları yeniləyir və ya yeni atributlar əlavə edir
    }

    /**
     * Update product stock status
     *
     * @param int $productId
     *
     *
     * @param bool $inStock
     *
     * @return bool
     */
    public static function updateProductStock(int $productId, bool $inStock)
    : bool {
        // Məhsulun stokda olub-olmadığını yeniləyir
        // Məhsul modelini əldə edir və stok statusunu yeniləyir
        // Yeniləmənin uğurlu olub-olmadığını boolean dəyər kimi qaytarır
        // Əgər məhsul tapılmazsa, uyğun xəta qaytarır
    }

    /**
     * Update product price
     *
     * @param int $productId
     * @param float $price
     * @param float|null $salePrice
     *
     * @return bool
     */
    public static function updateProductPrice(int $productId, float $price, ?float $salePrice = null)
    : bool {
        // Məhsulun qiymətini və (varsa) endirimli qiymətini yeniləyir
        // Məhsul modelini əldə edir və qiymət məlumatlarını yeniləyir
        // Yeniləmənin uğurlu olub-olmadığını boolean dəyər kimi qaytarır
        // Qiymət dəyişikliyini tarixçəyə əlavə edə bilər
    }

    /**
     * Check if product already exists in database
     *
     * @param string $asin
     *
     * @return bool
     */
    public static function productExists(string $asin)
    : bool {
        // Amazon ASIN-ə əsasən məhsulun artıq verilənlər bazasında mövcud olub-olmadığını yoxlayır
        // Product modelindən istifadə edərək sorğu göndərir
        // Məhsul mövcuddursa true, əks halda false qaytarır
    }

    /**
     * Find product by ASIN
     *
     * @param string $asin
     *
     * @return array|null
     */
    public static function findProductByAsin(string $asin)
    : ?array {
        // Verilmiş ASIN kodu ilə məhsulu axtarır
        // Məhsul tapılarsa, məlumatları massiv formatında qaytarır
        // Məhsul tapılmazsa null qaytarır
        // İstəyə bağlı olaraq əlaqəli şəkilləri və atributları da əlavə edə bilər
    }
}
