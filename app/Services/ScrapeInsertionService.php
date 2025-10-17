<?php

namespace App\Services;

use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Color;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\ProductTranslation;
use App\Models\Shop;
use App\Models\ShopTranslation;
use App\Models\Upload;
use App\Services\Amazon;
use App\Services\OpenAITranslationService;
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
    public static function     searchAndInsertProducts(string $keyword, $category_id, $page = null)
    {
        try {
            $searchData = Taobao::scrapeSearch($keyword);

            // Log the search data for debugging
            \Log::info('Taobao search data', [
                'keyword' => $keyword,
                'category_id' => $category_id,
                'success' => $searchData['success'] ?? false,
                'data_count' => isset($searchData['data']) ? count($searchData['data']) : 0,
                'first_item' => isset($searchData['data'][0]) ? [
                    'itemId' => $searchData['data'][0]['itemId'] ?? null,
                    'title' => $searchData['data'][0]['title'] ?? null,
                    'price' => $searchData['data'][0]['price'] ?? null,
                    'mainImgUrl' => $searchData['data'][0]['mainImgUrl'] ?? null,
                ] : null,
            ]);

            $products = [];
            if (!isset($searchData['data']) || empty($searchData['data'])) {
                \Log::warning('No data in Taobao response', [
                    'keyword' => $keyword,
                    'response' => $searchData
                ]);
                return $products;
            }

            // Step 1: Collect all Chinese titles for batch translation
            $chineseTitles = [];
            $itemsToProcess = [];

            foreach ($searchData['data'] as $index => $data) {
                if (!isset($data['itemId'])) {
                    \Log::warning('Missing itemId in search data', [
                        'keyword' => $keyword,
                        'index' => $index,
                        'data' => $data
                    ]);
                    continue;
                }

                $title = $data['title'] ?? '';
                if (empty($title)) {
                    continue;
                }

                $chineseTitles[] = $title;
                $itemsToProcess[] = $data;
            }

            // Step 2: Translate all titles to 3 languages using OpenAI
            \Log::info('Starting multi-language batch translation', [
                'keyword' => $keyword,
                'titles_count' => count($chineseTitles),
                'languages' => ['az', 'ru', 'en']
            ]);

            // Translate ALL titles in batch (efficient - 1 API call for all products)
            $translatedTitles = OpenAITranslationService::translateBatchMultiLanguage($chineseTitles, ['az', 'ru', 'en']);

            \Log::info('Multi-language batch translation completed', [
                'keyword' => $keyword,
                'original_count' => count($chineseTitles),
                'translated_count' => count($translatedTitles['az'] ?? []),
                'first_chinese' => $chineseTitles[0] ?? 'N/A',
                'first_azerbaijani' => $translatedTitles['az'][0] ?? 'N/A',
                'first_russian' => $translatedTitles['ru'][0] ?? 'N/A',
                'first_english' => $translatedTitles['en'][0] ?? 'N/A'
            ]);

            // Step 2.5: Collect unique shop names for translation
            $uniqueShopNames = [];
            $shopNameMapping = []; // Map Chinese shop name to index
            foreach ($itemsToProcess as $data) {
                $shopName = $data['shopName'] ?? null;
                if ($shopName && !isset($shopNameMapping[$shopName])) {
                    $shopNameMapping[$shopName] = count($uniqueShopNames);
                    $uniqueShopNames[] = $shopName;
                }
            }

            // Translate shop names if any exist
            $translatedShopNames = ['az' => [], 'ru' => [], 'en' => []];
            if (!empty($uniqueShopNames)) {
                $translatedShopNames = OpenAITranslationService::translateBatchMultiLanguage($uniqueShopNames, ['az', 'ru', 'en']);
                \Log::info('Shop names translation completed', [
                    'shop_count' => count($uniqueShopNames),
                    'first_shop_chinese' => $uniqueShopNames[0] ?? 'N/A',
                    'first_shop_azerbaijani' => $translatedShopNames['az'][0] ?? 'N/A'
                ]);
            }

            // Step 3: Process each item with its translated titles
            foreach ($itemsToProcess as $index => $data) {
                $chineseTitle = $chineseTitles[$index];
                $azerbaijaniTitle = $translatedTitles['az'][$index] ?? $chineseTitle;
                $russianTitle = $translatedTitles['ru'][$index] ?? $chineseTitle;
                $englishTitle = $translatedTitles['en'][$index] ?? $chineseTitle;

                $product = Product::where('scraped_item_id', $data['itemId'])->first();

                $isNewProduct = !$product;
                if (!$product) {
                    $product = new Product();
                }

                // Extract price and other data from Taobao response
                $price = isset($data['price']) ? floatval($data['price']) : 0;
                $originalPrice = isset($data['originalPrice']) ? floatval($data['originalPrice']) : $price;
                $sales = isset($data['sales']) ? intval($data['sales']) : 0;
                $sellerCount = isset($data['sellerCount']) ? intval($data['sellerCount']) : 0;
                $shopName = $data['shopName'] ?? null;
                $location = $data['location'] ?? null;

                // Use Azerbaijani title as primary name (since this is Azerbaijan market)
                $product->name           = $azerbaijaniTitle;
                $product->added_by       = 'admin';
                $product->user_id        = 11;
                $product->category_id    = $category_id;
                $product->unit_price     = $price;
                $product->purchase_price = $price * 0.8;

                // Calculate discount if original price is higher than current price
                if ($originalPrice > $price) {
                    $product->discount = $originalPrice - $price;
                    $product->discount_type = 'amount';
                } else {
                    $product->discount = 0;
                    $product->discount_type = null;
                }

                // Set number of sales/views and seller count if available
                $product->num_of_sale = $sales;
                $product->seller_count = $sellerCount;

                // Store Taobao item URL if available
                if (isset($data['itemId'])) {
                    $product->scraped_item_url = "https://item.taobao.com/item.htm?id=" . $data['itemId'];
                }

                // Create or get shop and save shop name translations
                if ($shopName) {
                    // Find or create shop by Chinese name
                    $shop = Shop::where('name', $shopName)->first();
                    if (!$shop) {
                        $shop = new Shop();
                        $shop->user_id = 11; // Admin user
                        $shop->name = $shopName; // Chinese name
                        $shop->save();

                        // Save translated shop names
                        $shopNameIndex = $shopNameMapping[$shopName] ?? null;
                        if ($shopNameIndex !== null && isset($translatedShopNames['az'][$shopNameIndex])) {
                            foreach (['az', 'ru', 'en'] as $lang) {
                                $translatedShopName = $translatedShopNames[$lang][$shopNameIndex] ?? $shopName;
                                ShopTranslation::updateOrCreate(
                                    ['shop_id' => $shop->id, 'lang' => $lang],
                                    ['name' => $translatedShopName]
                                );
                            }
                        }
                    }
                    // Link product to shop (if products table has shop_id field)
                    // $product->shop_id = $shop->id;
                }

                $product->save();

                // Save translations to product_translations table
                $languages = [
                    'az' => $azerbaijaniTitle,
                    'ru' => $russianTitle,
                    'en' => $englishTitle
                ];

                foreach ($languages as $lang => $translatedTitle) {
                    ProductTranslation::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'lang' => $lang
                        ],
                        [
                            'name' => $translatedTitle,
                            'unit' => 'pc',
                            'description' => null
                        ]
                    );
                }

                \Log::info('Product saved with multi-language translation', [
                    'keyword' => $keyword,
                    'is_new' => $isNewProduct,
                    'product_id' => $product->id,
                    'item_id' => $data['itemId'],
                    'chinese_title' => $chineseTitle,
                    'azerbaijani_title' => $azerbaijaniTitle,
                    'russian_title' => $russianTitle,
                    'english_title' => $englishTitle,
                    'price' => $price,
                    'original_price' => $originalPrice,
                    'discount' => $product->discount,
                    'sales' => $sales,
                    'shop_name' => $shopName,
                    'location' => $location,
                    'category_id' => $category_id,
                    'taobao_url' => $product->scraped_item_url
                ]);

                // Handle image upload - Taobao returns mainImgUrl
                $imageUrl = $data['mainImgUrl'] ?? null;
                if ($imageUrl) {
                    // Check if this image already exists
                    $upload = Upload::where('file_name', $imageUrl)->first();

                    if (!$upload) {
                        $upload = Upload::create([
                            'file_original_name' => null,
                            'file_name'          => $imageUrl,
                            'user_id'            => $product->user_id,
                            'extension'          => 'jpg',
                            'type'               => 'image',
                            'file_size'          => 0
                        ]);
                    }

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
                $product->slug                             = \Str::slug($azerbaijaniTitle);
                $product->frequently_bought_selection_type = 'product';
                $product->rating                           = $data['rating'] ?? 0;
                $product->scraped_item_id                  = $data['itemId'];

                // Save complete Taobao product data as JSON
                $product->scraped_basic_data               = json_encode($data, JSON_UNESCAPED_UNICODE);
                $product->scraped_date                     = now();

                $product->save();
                $product->video_provider  = 'youtube';
                $product->variant_product = 0;
                $product->attributes      = json_encode([]);
                $product->choice_options  = json_encode([]);
                $product->colors          = json_encode([]);
                $products[]               = $product;

            }
            return $products;
        }
        catch (\Exception $e) {
            \Log::error('Search and insert error: ' . $e->getMessage(), [
                'keyword' => $keyword,
                'category_id' => $category_id,
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
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
                        $valueName = $attributeValueNames[$prop['value_id']] ?? $prop['value_name'];
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
            \Log::error('Product insertion error: ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
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
