<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

class Area extends Model
{
    use Sortable;

    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function stores()
    {
        return $this->hasMany('App\Models\Store', 'area_id', 'id');
    }

    public static function scopeGetListByPath($query, string $areaCd, int $level)
    {
        return $query->where('path', 'like', '%'.strtolower($areaCd))
                     ->where('level', $level);
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * @param $query
     * @param $valid
     *
     * @return mixed
     */
    public static function scopeAdminSearchFilter($query, $valid)
    {
        if (isset($valid['name']) && !empty($valid['name'])) {
            $query->where('name', 'like', '%'.$valid['name'].'%');
        }
        if (isset($valid['area_cd']) && !empty($valid['area_cd'])) {
            $query->where('area_cd', 'like', '%'.$valid['area_cd'].'%');
        }
        if (isset($valid['path']) && !empty($valid['path'])) {
            $query->where('path', 'like', '%'.$valid['path'].'%');
        }

        return $query;
    }

    public static function scopeGetStartWithPath($query, string $areaCd, int $level)
    {
        return $query->where('path', 'like', '%'.strtolower($areaCd).'%')
                     ->where('level', '>', $level);
    }

    public static function scopeGetAreaIdWithAreaCd($query, string $areaCd)
    {
        return $query->select('id')
                     ->where('area_cd', $areaCd)
                     ->where('level', 2);
    }

    public static function scopeOrderBySort($query)
    {
        return $query->orderByRaw('sort IS NULL ASC')
                     ->orderBy('sort', 'asc');
    }

    public static function getParentAreas(array $pathArr)
    {
        $query = Area::whereIn('area_cd', $pathArr);
        $query->where('published', 1);
        $query->orderBy('weight', 'DESC');
        $query->orderBy('id');

        return $query->get();
    }
}
