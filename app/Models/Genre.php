<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

class Genre extends Model
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

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
    ];

    public function genreGroups()
    {
        return $this->hasMany('App\Models\GenreGroup', 'genre_id', 'id');
    }

    public static function scopeGetListByPath($query, string $appCd, string $genreCd, int $level)
    {
        if (!empty($appCd)) {
            $query = $query->where('app_cd', 'like', '%'.$appCd.'%');
        }

        return $query->where('path', 'like', '%'.strtolower($genreCd))
                     ->where('level', $level);
    }

    public static function scopeGetStartWithPath($query, string $appCd, string $genreCd, int $level)
    {
        $appCds = str_split($appCd, 2);
        $query = $query->where(function ($query) use ($appCds) {
            foreach ($appCds as $cd) {
                $query->orWhere('app_cd', 'like', '%'.$cd.'%');
            }
        });

        return $query->where('path', 'like', '%'.strtolower($genreCd).'%')
                     ->where('level', '>', $level);
    }

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
        if (isset($valid['genre_cd']) && !empty($valid['genre_cd'])) {
            $query->where('genre_cd', 'like', '%'.$valid['genre_cd'].'%');
        }
        if (isset($valid['app_cd']) && !empty($valid['app_cd'])) {
            $query->where('app_cd', 'like', '%'.$valid['app_cd'].'%');
        }
        if (isset($valid['path']) && !empty($valid['path'])) {
            $query->where('path', 'like', '%'.$valid['path'].'%');
        }

        return $query;
    }

    public function scopeGetGenreMenu($query, string $path, string $appCd, string $genreCd)
    {
        return $query->where('path', $path)
              ->where('app_cd', 'like', '%'.$appCd.'%')
              ->where('genre_cd', $genreCd)
              ->where('published', 1);
    }
}
