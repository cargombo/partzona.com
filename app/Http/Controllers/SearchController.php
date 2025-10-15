<?php

namespace App\Http\Controllers;

use App\Services\Amazon;
use App\Services\ScrapeInsertionService;
use Illuminate\Http\Request;
use App\Models\Search;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Shop;
use App\Models\Attribute;
use App\Models\AttributeCategory;
use App\Utility\CategoryUtility;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    public function index(Request $request, $category_id = null, $brand_id = null,$product_ids = [])
    {
        // Check for separate auto parts search parameters
        $searchBrand = $request->get('brand');
        $searchModel = $request->get('model');
        $searchPart = $request->get('part');

        // Build query from individual parameters if they exist
        if ($searchBrand || $searchModel || $searchPart) {
            $queryParts = array_filter([$searchBrand, $searchModel, $searchPart]);
            $query = implode(' ', $queryParts);
        } else {
            $query = $request->keyword;
        }

        $sort_by = $request->sort_by;
        $min_price = intval($request->min_price);
        $max_price = intval($request->max_price);
        $seller_id = $request->seller_id;
        $selected_attribute_values = array();
        $colors = [];
        $attributes = [];
        $selected_color = null;
        $categories = [];
        $page = $request->get('page', 1);
        $slug = Str::slug($query);
        if($category_id){
            $category = Category::where('id',$category_id)->first();
        }else{
            $category = Category::where('slug',$slug)->first();
        }

        if(!$category){
            $category = new Category();
            $category->name = $query;
            $category->slug = $slug;
            $category->deleted_at = now();
            $category->save();
        }
        $products = ScrapeInsertionService::searchAndInsertProducts($category->name,$category->id,$page);

        // Convert to collection and create paginator if products exist
        if (!empty($products)) {
            $products = collect($products);
        } else {
            $products = collect([]);
        }

        return view('frontend.product_listing', compact('products', 'query', 'category', 'categories', 'category_id', 'brand_id', 'sort_by', 'seller_id', 'min_price', 'max_price', 'attributes', 'selected_attribute_values', 'colors', 'selected_color', 'searchBrand', 'searchModel', 'searchPart'));


}

    public function listing(Request $request)
    {
        return $this->index($request);
    }

    public function listingByCategory(Request $request, $category_slug)
    {

        $page = $request->get('page',1);
        $category = Category::where('slug', $category_slug)->first();
        if ($category != null) {
            $keyword = "Bir çox Avtomobillər (Maşınlar) üçün: ".$category->category_translations->where('lang','az')->first()->name;
            $searchData  = ScrapeInsertionService::searchAndInsertProducts($keyword,$category->id,$page);
            $product_ids = isset($searchData['product_ids']) ? $searchData['product_ids'] : [];
            if(isset($searchData['pagination'])){

                $category->pagination = $searchData['pagination'];
                $category->save();
            }
            if(isset($searchData['totalProducts'])){
                $request->request->add(['limit' => $searchData['totalProducts']]);
            }

            return $this->index($request, $category->id,null,$product_ids);
        }
        abort(404);
    }

    public function listingByBrand(Request $request, $brand_slug)
    {
        $brand = Brand::where('slug', $brand_slug)->first();
        if ($brand != null) {
            return $this->index($request, null, $brand->id);
        }
        abort(404);
    }

    //Suggestional Search
    public function ajax_search(Request $request)
    {

        $keywords = array();
        $query = $request->search;
        $products = Product::where('published', 1)->where('name', 'like', '%' . $query . '%')->get();
        foreach ($products as $key => $product) {
            foreach (explode(',', $product->tags) as $key => $tag) {
                if (stripos($tag, $query) !== false) {
                    if (sizeof($keywords) > 5) {
                        break;
                    } else {
                        if (!in_array(strtolower($tag), $keywords)) {
                            array_push($keywords, strtolower($tag));
                        }
                    }
                }
            }
        }

        $products_query = filter_products(Product::query());

        $products_query = $products_query->where('published', 1)
            ->where(function ($q) use ($query) {
                foreach (explode(' ', trim($query)) as $word) {
                    $q->where('name', 'like', '%' . $word . '%')
                        ->orWhere('tags', 'like', '%' . $word . '%')
                        ->orWhereHas('product_translations', function ($q) use ($word) {
                            $q->where('name', 'like', '%' . $word . '%');
                        })
                        ->orWhereHas('stocks', function ($q) use ($word) {
                            $q->where('sku', 'like', '%' . $word . '%');
                        });
                }
            });
        $case1 = $query . '%';
        $case2 = '%' . $query . '%';

        $products_query->orderByRaw('CASE
                WHEN name LIKE "'.$case1.'" THEN 1
                WHEN name LIKE "'.$case2.'" THEN 2
                ELSE 3
                END');
        $products = $products_query->limit(5)->get();

        $categories = Category::where('name', 'like', '%' . $query . '%')->get()->take(3);

        $shops = Shop::whereIn('user_id', verified_sellers_id())->where('name', 'like', '%' . $query . '%')->get()->take(3);

        if (sizeof($keywords) > 0 || sizeof($categories) > 0 || sizeof($products) > 0 || sizeof($shops) > 0) {
            return view('frontend.partials.search_content', compact('products', 'categories', 'keywords', 'shops'));
        }
        return '0';
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $search = Search::where('query', $request->keyword)->first();
        if ($search != null) {
            $search->count = $search->count + 1;
            $search->save();
        } else {
            $search = new Search;
            $search->query = $request->keyword;
            $search->save();
        }
    }
}
