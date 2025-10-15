<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class AutoPart extends Model
{
    protected $table = 'auto_parts';

    protected $fillable = [
        'slug',
    ];

    public function translations()
    {
        return $this->hasMany(AutoPartTranslation::class, 'part_id');
    }

    public function getTranslation($field = '', $lang = false)
    {
        $lang = $lang == false ? App::getLocale() : $lang;
        $translation = $this->translations->where('lang', $lang)->first();
        return $translation != null ? $translation->$field : '';
    }
}
