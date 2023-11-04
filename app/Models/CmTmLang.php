<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmTmLang extends Model
{
    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $table = 'common.cm_tm_lang';
    protected $primaryKey = 'lang_id';
    protected $guarded = ['lang_id'];

    public static function scopeIso639CountryCd($query, $code)
    {
        return $query->where('iso639_country_cd', '=', $code);
    }
}
