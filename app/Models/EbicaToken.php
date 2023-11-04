<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EbicaToken extends Model
{
    /**
     * 複数代入する属性
     *
     * @var array
     */
    protected $fillable = [
        'api_cd',
        'token',
    ];
}
