<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\SoftDeletes;
use App;

class Category extends Model
{
    use SoftDeletes;
//    use PreventDemoModeChanges;
    protected $casts = [
        'pagination'    => 'json',
    ];
    protected $with = ['category_translations'];

    public function getTranslation($field = '', $lang = false){
        $lang = $lang == false ? App::getLocale() : $lang;
        $category_translation = $this->category_translations->where('lang', $lang)->first();
        return $category_translation != null ? $category_translation->$field : $this->$field;
    }

    public function category_translations(){
    	return $this->hasMany(CategoryTranslation::class);
    }

    public function coverImage(){
    	return $this->belongsTo(Upload::class, 'cover_image');
    }

    public function catIcon(){
    	return $this->belongsTo(Upload::class, 'icon');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_categories');
    }

    public function bannerImage(){
    	return $this->belongsTo(Upload::class, 'banner');
    }

    public function classified_products(){
    	return $this->hasMany(CustomerProduct::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function childrenCategories()
    {
        return $this->hasMany(Category::class, 'parent_id')->with('categories');
    }

    public function parentCategory()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class);
    }

    public function sizeChart()
    {
        return $this->belongsTo(SizeChart::class, 'id', 'category_id');
    }
    public function getPaginationAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        $paginationData = json_decode($value, true);

        // Create an empty collection
        $items = collect([]);

        // Get current request path and query parameters
        $path = Paginator::resolveCurrentPath();
        $query = request()->query();

        // Create a length-aware paginator
        $paginator = new LengthAwarePaginator(
            $items,                                   // items (empty in this case)
            $paginationData['totalPages'] * 10,       // total items (approximate)
            10,                                       // per page
            $paginationData['currentPage'],           // current page
            [
                'path' => $path,
                'query' => array_merge($query, ['page' => $paginationData['currentPage']]),
            ]
        );

        return $paginator;
    }
}
