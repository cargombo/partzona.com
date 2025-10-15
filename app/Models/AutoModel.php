<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class AutoModel extends Model
{
    protected $table = 'auto_models';

    protected $fillable = [
        'id',
        'brand_id',
        'auto_model_group_id',
        'name',
        'slug',
        'year_from',
        'year_to',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function modelGroup()
    {
        return $this->belongsTo(AutoModelGroup::class, 'auto_model_group_id');
    }

    // Alias for backward compatibility
    public function group()
    {
        return $this->modelGroup();
    }
}
