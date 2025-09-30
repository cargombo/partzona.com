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

        $product = Product::select('id', 'scraped_item_id', 'scraped_item_url', 'scraped_date', 'category_id')->where('auction_product', 0)
            ->where('slug', $slug)
            ->where('approved', 1)
            ->first();



        try {
//            $url        = "https://www.amazon.com/dp/$product->scraped_item_id?th=1";
            $searchData = Taobao::scrapeProduct($product->scraped_item_id);
            dd($searchData);

//            $searchData = Amazon::scrapeProduct($product->scraped_item_url);
            $searchData = $searchData['data'];
            if (count($searchData) == 0) {
                return [];
            }
            $description    = "";
            $techSpecs      = "";
            $details        = "";
            $specifications = "";
            $features       = "";

            if (isset($searchData['techSpecs']) && count($searchData['techSpecs']) > 0) {
                $techSpecs = "<h5>Technical Specifications</h5><ul>";
                foreach ($searchData['details'] as $key => $value) {
                    $techSpecs = "$techSpecs <li><span style='font-weight: bolder;'>$key:&nbsp;</span>$value</li>";
                }
                $techSpecs = "$techSpecs</ul>";
            }
            if (isset($searchData['features']) && count($searchData['features']) > 0) {
                $features = "<h5>Features</h5><ul>";
                foreach ($searchData['features'] as $key => $value) {
                    $features = "$features <li>$value</li>";
                }
                $features = "$features</ul>";
            }
            if (isset($searchData['specifications']) && $searchData['specifications'] !== "") {
                $specifications = "<h5>Specifications</h5><ul>";
                foreach ($searchData['specifications'] as $key => $value) {
                    $specifications = "$specifications <li><span style='font-weight: bolder;'>$key:&nbsp;</span>$value</li>";
                }
                $specifications = "$specifications</ul>";
            }
            if (isset($searchData['details']) && count($searchData['details']) > 0) {
                $details = "<h5>Details</h5><ul>";
                foreach ($searchData['details'] as $key => $value) {
                    $details = "$details <li><span style='font-weight: bolder;'>$key:&nbsp;</span>$value</li>";
                }
                $details = "$details</ul>";
            }
            if (isset($searchData['description']) && $searchData['description'] !== "") {
                $_description = $searchData['description'];
                $description  = "$details $techSpecs <p>$_description</p>";
            }
            $price  = str_replace('$', '', $searchData['price']);
            $photos = [];
            if (count($searchData['images']) > 0) {
                foreach ($searchData['images'] as $key => $image) {
                    if ($key > 0) {
                        $upload   = Upload::updateOrCreate(
                            [
                                'file_name' => $image, // Unique identifier (checks if exists)
                            ],
                            [
                                'file_original_name' => null,
                                'user_id'            => $product->user_id,
                                'extension'          => 0,
                                'type'               => 'image',
                                'file_size'          => 0
                            ]
                        );
                        $photos[] = $upload->id;
                    }
                }
            }
            $variationDisplayLabels = $searchData['twisterData']['variationDisplayLabels'] ?? [];
            $variationValues        = $searchData['twisterData']['variationValues'] ?? [];

//            dd($variationDisplayLabels,$variationValues);
//
            $colors         = [];
            $attributes     = [];
            $attribute_ids  = [];
            $choice_options = [];
            $i              = 0;
            ksort($variationValues);
            foreach ($variationValues as $key => $values) {
                if ($key !== 'color_name') {
                    // CHECK Attribute
                    $attribute = Attribute::where('name', $variationDisplayLabels[$key])->where('key', $key)->first();
                    if (!$attribute) {
                        $attribute       = new Attribute();
                        $attribute->name = $variationDisplayLabels[$key];
                        $attribute->key  = $key;
                        $attribute->save();
                    }
                    $attribute_ids[] = $attribute->id;

                }
                $_values = [];
                foreach ($values as $value) {
                    if ($key == 'color_name') {
                        $_color  = Color::where('code', $value)->first();
                        $__color = str_replace([" ", '/'], "-", $value);

                        if (!$_color) {
                            $_color = new Color();
                        }
                        $_color->name  = $value;
                        $_color->image = $searchData['colors'][$value]['image'] ?? null;
                        $_color->code  = $__color;
                        $_color->save();
                        $colors[] = $__color;
                    } else {
                        // CHECK Attribute Value
                        $__value        = str_replace([" ", '/'], "-", $value);
                        $_values[]      = $__value;
                        $attributeValue = AttributeValue::where('attribute_id', $attribute->id)
                            ->where('value', $__value)
                            ->first();
                        if (!$attributeValue) {
                            $attributeValue               = new AttributeValue();
                            $attributeValue->attribute_id = $attribute->id;
                            $attributeValue->value        = $value;
                            $attributeValue->save();
                        }
                    }
                }
                if ($key !== 'color_name') {
                    $choice_options[$i] = [
                        'attribute_id' => $attribute->id,
                        'values'       => $_values
                    ];
                }
                $i++;
            }
            $_attributes = $searchData['attributes'];
            foreach ($_attributes as $_attribute) {
                $scraped_item_id = $_attribute['asin'];
                $productStock    = ProductStock::where('product_id', $product->id)->where('scraped_item_id',
                    $scraped_item_id)->first();
                if (!$productStock) {
                    $productStock = new ProductStock();
                }

                $variant    = null;
                $dimensions = $searchData['twisterData']['dimensions'];
                sort($dimensions);
                foreach ($dimensions as $dimension) {
                    $variant = $variant ? $variant . "-" . $_attribute[$dimension] : $_attribute[$dimension];
                }
                $variant                       = str_replace([" ", '/'], "-", $variant);
                $productStock->product_id      = $product->id;
                $productStock->scraped_item_id = $_attribute['asin'];
                $productStock->variant         = $variant;
                $productStock->sku             = $_attribute['asin'];
                $productStock->price           = $price;
                $productStock->qty             = 100;
                $productStock->image           = $_attribute['image'] ?? null;
                $productStock->datas           = $variationValues;
                $productStock->save();
            }
            $description = str_replace('›  See more product details', '', $description);

            $product->description    = $description;
            $product->photos         = implode(',', $photos);
            $product->attributes     = json_encode($attribute_ids);
            $product->colors         = json_encode($colors);
            $product->choice_options = json_encode(empty($choice_options) ? [] : $choice_options);
            $product->scraped_date   = now();
            $product->save();

            $ProductTranslation = ProductTranslation::where('product_id', $product->id)->where('lang', 'en')->first();
            if (!$ProductTranslation) {
                $ProductTranslation = new ProductTranslation();
            }
            $ProductTranslation->product_id  = $product->id;
            $ProductTranslation->name        = $searchData['title'];
            $ProductTranslation->unit        = 'Pc';
            $ProductTranslation->lang        = 'en';
            $ProductTranslation->description = $description;
            $ProductTranslation->save();


            return $product;

            /**
             * foreach ($searchData['colors'] as $color) {
             *
             * $_color   = Color::where('code', $color['color_name'])->first();
             * $__color  = str_replace('/', '-', $color['color_name']);
             * $__color  = str_replace(' ', '-', $__color);
             * $colors[] = $__color;
             *
             * $category_ids = CategoryUtility::children_ids($product->category_id);
             * $category_ids[] = $product->category_id;
             *
             * if (!$_color) {
             * $_color = new Color();
             * }
             * $_color->name  = $__color;
             * $_color->image = $color['image'];
             * $_color->code  = $__color;
             * $_color->save();
             *
             * if (count($searchData['sizes']) > 0) {
             * $choice_options_for_stocks = [
             * 'attribute_id' => 1,
             * 'values'       => []
             * ];
             *
             *
             * foreach ($color['sizes'] as $size) {
             * $scraped_item_id = $size['asin'];
             *
             * $__size  = str_replace('/', '-', $size['size']);
             * $__size  = str_replace(' ', '-', $__size);
             * $attributes[$__size] = $__size;
             * $variant = $__color .'-'. $__size;
             *
             * $productStock               = ProductStock::where('product_id', $product->id)
             * ->where('variant', $variant)
             * ->first();
             *
             * $choice_options_for_stocks['values'][] = $size['size'];
             * if (!$productStock) {
             * $productStock = new ProductStock();
             * }
             * $attributeValue = AttributeValue::where('value', $__size)->first();
             *
             * if (!$attributeValue) {
             * $attributeValue               = new AttributeValue();
             * $attributeValue->attribute_id = 1;
             * $attributeValue->value        = $__size;
             * $attributeValue->save();
             *
             * }
             *
             * $productStock->product_id      = $product->id;
             * $productStock->scraped_item_id = $scraped_item_id;
             * $productStock->variant         = $variant;
             * $productStock->sku             = $size['asin'];
             * $productStock->price           = $price;
             * $productStock->qty             = 100;
             * $productStock->datas           = ['color' => $color, 'size' => $size];
             *
             * $productStock->attributes     = json_encode([1]);
             * $productStock->colors         = json_encode($colors);
             * $productStock->choice_options = json_encode(empty($choice_options_for_stocks) ? [] : [$choice_options_for_stocks]);
             *
             * $productStock->save();
             * }
             * }
             * else {
             * $scraped_item_id = $color['asin'];
             * $productStock    = ProductStock::where('product_id', $product->id)->where('scraped_item_id',
             * $scraped_item_id)->first();
             * if (!$productStock) {
             * $productStock = new ProductStock();
             * }
             *
             * $productStock->product_id      = $product->id;
             * $productStock->scraped_item_id = $scraped_item_id;
             * $productStock->variant         = $__color;
             * $productStock->sku             = $color['asin'];
             * $productStock->price           = $price;
             * $productStock->qty             = 100;
             * $productStock->image           = $color['image'] ?? null;
             * $productStock->datas           = ['color' => $color];
             *
             * $productStock->attributes     = json_encode([1]);
             * $productStock->colors         = json_encode($colors);
             * $productStock->choice_options = json_encode(empty($choice_options_for_stocks) ? [] : [$choice_options_for_stocks]);
             *
             * $productStock->save();
             * }
             * }
             *
             *
             * $description = str_replace('›  See more product details', '', $description);
             * $attributes = array_values($attributes);
             * $choice_options['values'] = array_values($attributes);
             *
             * $product->description    = $description;
             * $product->photos         = implode(',', $photos);
             * $product->attributes     = json_encode([1]);
             * $product->colors         = json_encode($colors);
             * $product->choice_options = json_encode(empty($choice_options) ? [] : [$choice_options]);
             * $product->scraped_date   = now();
             * $product->save();
             *
             * $ProductTranslation = ProductTranslation::where('product_id', $product->id)->where('lang', 'en')->first();
             * if (!$ProductTranslation) {
             * $ProductTranslation = new ProductTranslation();
             * }
             * $ProductTranslation->product_id  = $product->id;
             * $ProductTranslation->name        = $searchData['title'];
             * $ProductTranslation->unit        = 'Pc';
             * $ProductTranslation->lang        = 'en';
             * $ProductTranslation->description = $description;
             * $ProductTranslation->save();
             *
             *
             * return $product;
             **/

        }
        catch (\Exception $e) {
            dd($e->getMessage(), $e->getLine());
            return [];
        }

        // Məhsul modelini yaradır və verilənlər bazasına əlavə edir
        // productExists() metodu ilə məhsulun artıq mövcud olub-olmadığını yoxlayır
        // Məhsul məlumatlarını düzgün formatda hazırlayır (adı, təsviri, qiyməti və s.)
        // Əgər kateqoriya ID-si təqdim edilmişdirsə, məhsulu həmin kateqoriya ilə əlaqələndirir
        // insertProductImages() və insertProductAttributes() metodlarını çağırır
        // Əlavə edilmiş məhsulun ID-sini geri qaytarır
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
