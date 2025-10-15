<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoPartTranslation extends Model
{
    protected $table = 'auto_parts_translations';

    protected $fillable = [
        'part_id',
        'lang',
        'name',
        'description',
        'search_keywords',
    ];

    public $timestamps = false;

    public function autoPart()
    {
        return $this->belongsTo(AutoPart::class, 'part_id');
    }
}
