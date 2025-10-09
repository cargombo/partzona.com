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
use Illuminate\Support\Facades\Http;
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
            $products   = [];
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
            $response = Taobao::scrapeProduct($product->scraped_item_id);
            if (!$response['success'] || !isset($response['data'])) {
                return ['error' => 'Failed to fetch product data'];
            }

            $searchData = $response['data'];
            // Tərcümə ediləcək mətnləri topla
            $title             =  $searchData['title'];
//            $description       =  $searchData['description'];
            $description       =  '';

            $translates  = self::translateText([
                'title'       => $title,
//                'description' => $description,
            ], 'zh', 'az');
            $title       = $translates['data']['title'] ?? $title;
//            $description = $translates['data']['description'] ?? $description;

            $price          = isset($searchData['price']) ? $searchData['price'] / 100 : 0;
            $promotionPrice = isset($searchData['promotion_price']) ? $searchData['promotion_price'] / 100 : $price;

            // Şəkilləri emal et
            $photos       = [];
            $thumbnailImg = null;

            if (isset($searchData['pic_urls']) && is_array($searchData['pic_urls']) && count($searchData['pic_urls']) > 0) {
                $thumbnail    = Upload::updateOrCreate(
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

                foreach ($searchData['pic_urls'] as $key => $imageUrl) {
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

                    if ($key > 0) {
                        $photos[] = $upload->id;
                    }
                }
            }
            $choiceOptions = [];
            $attributeIds  = [];
            $attributeValueIds  = [];
            $totalQuantity = 0;

            // SKU variantlarını emal et
            if (isset($searchData['sku_list']) && is_array($searchData['sku_list']) && count($searchData['sku_list']) > 0) {
                $attributeNames      = [];
                $attributeValueNames = [];
                $all_data = [];
                foreach ($searchData['sku_list'] as $sku) {
                    foreach ($sku['properties'] as $prop) {
                        $attributeNames[$prop['prop_id']] = $prop['prop_name'];
                        $attributeValueNames[$prop['value_id']] = $prop['value_name'];
                        $all_data[$prop['prop_id']][$prop['value_id']] = [
                            'value_name'      => $prop['value_name'],
                            'sku_id'          => $sku['sku_id'],
                            'quantity'        => $sku['quantity'],
                            'price'           => $sku['price'],
                            'postFee'         => $sku['postFee'],
                            'coupon_price'    => $sku['coupon_price']  ?? 0,
                            'promotion_price' => $sku['promotion_price'],
                            'status'          => $sku['status'],
//                            'prop_ids'        => $sku['prop_id'],
                            "variant"         => isset($all_data[$prop['prop_id']][$prop['value_id']]['variant']) ? $all_data[$prop['prop_id']][$prop['value_id']]['variant'].'-'.$prop['value_name'] : $prop['value_name'],
                        ];
                    }
                }
                $attributeNames      = self::translateText($attributeNames, 'zh', 'az')['data'] ?? $attributeValueNames;
                $attributeValueNames = self::translateText($attributeValueNames, 'zh', 'az')['data'] ?? $attributeValueNames;
//

                foreach ($searchData['sku_list'] as $sku) {
                    $_price        = round($sku['promotion_price'] / 100, 2);
                    $productStock = ProductStock::where('sku', $sku['sku_id'])->first();
                    if (!$productStock) {
                        $productStock = new ProductStock;
                    }
                    $variant = null;
                    foreach ($sku['properties'] as $prop) {

                        $propName  = $prop['prop_name'] ?? null;
                        $valueName = $prop['value_name'] ?? null;
                        $variant   = $variant ? $variant . "-" . trim($valueName) : trim($valueName);
//                            $propName = self::translateText($propName, 'zh', 'az');
//                            $valueName = self::translateText($valueName, 'zh', 'az');

                        $attribute = Attribute::where('prop_id', $prop['prop_id'])->first();
                        if (!$attribute) {
                            $attribute          = new Attribute;
                            $attribute->prop_id = $prop['prop_id'];
                            $attribute->name    = $attributeNames[$prop['prop_id']] ?? $propName;
                            $attribute->key     = Str::slug($attributeNames[$prop['prop_id']] ?? $propName);
                            $attribute->save();
                        }
                        $attributeIds[] = $attribute->id;

                        $attributeValue = AttributeValue::where('attribute_id', $attribute->id)
                            ->where('value_id', $prop['value_id'])
                            ->first();


                        if (!$attributeValue) {
                            $attributeValue               = new AttributeValue;
                            $attributeValue->value_id     = $prop['value_id'];
                            $attributeValue->attribute_id = $attribute->id;
                            $attributeValue->value        = $attributeValueNames[$prop['value_id']] ?? $valueName;
                            $attributeValue->save();
                        }
                        $attributeValueIds[] = $attributeValue->id;

                    }
                    $productStock->product_id      = $product->id;
                    $productStock->scraped_item_id = $sku['sku_id'];
                    $productStock->variant         = $variant;
                    $productStock->sku             = $sku['sku_id'];
                    $productStock->price           = $_price;
                    $productStock->qty             = $sku['quantity'];
                    $productStock->image           = null;
                    $productStock->save();
                }

                $attributes = Attribute::whereIn('id', $attributeIds)->get();
                foreach ($attributes as $attribute) {
                    $choiceOptions[] = [
                        'attribute_id' => (string)$attribute->id,
                        'values'       => AttributeValue::where('attribute_id', $attribute->id)->whereIn('id', $attributeValueIds)
                            ->pluck('value')
                            ->toArray()
                    ];
                };


                $product->variant_product = 1;
            } else {
                // Variant olmayan məhsul
                ProductStock::where('product_id', $product->id)->delete();
                $totalQuantity = $searchData['quantity'] ?? 0;

                $skuValue = $searchData['mp_skuId'] ?? null;

                ProductStock::create([
                    'product_id' => $product->id,
                    'variant'    => '',
                    'sku'        => $skuValue,
                    'price'      => $price,
                    'qty'        => $totalQuantity,
                    'image'      => null
                ]);

                $product->variant_product = 0;
            }

            // Endirim hesabla
            $discount     = 0;
            $discountType = null;
//            if ($promotionPrice < $price) {
//                $discount     = $price - $promotionPrice;
//                $discountType = 'amount';
//            }

            $mpIdValue = $searchData['mp_id'];


            // Məhsulu yenilə (tərcümə edilmiş mətnlərlə)
            $product->name              = $title;
            $product->description       = $description;
            $product->unit_price        = $promotionPrice;
            $product->purchase_price    = $price;
            $product->photos            = implode(',', $photos);
            $product->thumbnail_img     = $thumbnailImg;
            $product->current_stock     = $totalQuantity;
            $product->scraped_date      = now();
            $product->scraped_full_data = json_encode($searchData);
            $product->meta_title        = $title;
            $product->meta_description  = $description;
            $product->meta_img          = $thumbnailImg;
            $product->choice_options    = !empty($choiceOptions) ? json_encode($choiceOptions, JSON_UNESCAPED_UNICODE) : null;
            $product->attributes        = !empty($attributeIds) ? json_encode($attributeIds) : null;
            $product->discount          = $discount;
            $product->discount_type     = $discountType;
            $product->mp_id             = $mpIdValue;
            $product->colors            = json_encode([]);


            $product->save();

            return [
                'success' => true,
                'product' => $product,
                'message' => 'Product updated successfully'
            ];

        }
        catch (\Exception $e) {
            dd($e->getMessage(), $e->getLine());
        }
    }

// Tərcümə funksiyası
    private static function translateText($data, $sourceLang, $targetLang)
    {
        try {


            $body = [
                "data"       => $data,
                "sourceLang" => $sourceLang,
                "targetLang" => $targetLang
            ];
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL            => 'https://rapidapi.brmsg.site/api/translate/data',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     =>json_encode($body),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer OryXos8JrzYTvU8UOxB1e1fp7SOIv0U4u7Gy4QEKazdglT'
                ],
            ]);

            $response = curl_exec($curl);

            curl_close($curl);
            return json_decode($response, true);
        }
        catch (\Exception $e) {
            \Log::error('Translation error: ' . $e->getMessage());
            return $text;
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
