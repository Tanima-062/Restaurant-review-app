<?php

namespace App\Models;

use App\Libs\Cipher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Review extends Model
{
    const LIMIT = 200;
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->hasOne('App\Models\CmTmUser', 'user_id', 'user_id');
    }

    public function image()
    {
        return $this->hasOne('App\Models\Image', 'id', 'image_id');
    }

    public function menu()
    {
        return $this->hasOne('App\Models\Menu', 'id', 'menu_id');
    }

    public function getUserNameAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    /**
     * created_atの日時フォーマット.
     *
     * @return string
     */
    public function getCreatedAtAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->format('Y/m/d/ H:i:s');
    }

    /**
     * 指定の店舗IDのカテゴリーコードごとの口コミ件数を取得する.
     *
     * @param int 店舗ID
     *
     * @return array
     */
    public function getCountGroupedByEvaluationCd($storeId)
    {
        $query = 'SELECT reviews.evaluation_cd, COUNT(reviews.id) AS count';
        $query .= ' FROM reviews INNER JOIN menus ON reviews.menu_id = menus.id';
        $query .= ' WHERE menus.store_id = ?';
        $query .= ' GROUP BY reviews.evaluation_cd';
        $query .= ' UNION ALL';
        $query .= ' SELECT "total", COUNT(reviews.id) AS count';
        $query .= ' FROM reviews INNER JOIN menus ON reviews.menu_id = menus.id';
        $query .= ' WHERE menus.store_id = ?';

        return DB::select($query, [$storeId, $storeId]);
    }

    public static function scopeGetCountByEvaluationCd($query, int $menuId)
    {
        return $query->select('evaluation_cd', DB::raw('count(id) as cnt'))
            ->where('menu_id', $menuId)
            ->groupBy('evaluation_cd');
    }

    public static function scopeGetReviews($query, int $menuId)
    {
        return $query->select('id', 'user_name', 'user_id', 'body', 'image_id', 'evaluation_cd', 'created_at')
            ->where('menu_id', $menuId)
            ->where('published', 1);
    }

    public function getReviewsByStoreId($storeId)
    {
        $query = 'SELECT reviews.id, reviews.user_id, reviews.user_name, reviews.body, reviews.evaluation_cd, reviews.created_at,';
        $query .= ' images.id AS images_id, images.image_cd, images.url';
        $query .= ' FROM reviews LEFT JOIN images ON reviews.image_id = images.id';
        $query .= ' WHERE reviews.store_id = ?';
        $query .= ' AND reviews.published = 1';
        $query .= ' AND reviews.body != ""';
        $query .= ' ORDER BY reviews.created_at DESC';
        $query .= ' LIMIT ?';

        return DB::select($query, [$storeId, self::LIMIT]);
    }

    public function getReviewsByMenuId($menuId)
    {
        $query = 'SELECT reviews.id, reviews.user_id, reviews.user_name, reviews.body, reviews.evaluation_cd, reviews.created_at,';
        $query .= ' images.id AS images_id, images.image_cd, images.url';
        $query .= ' FROM reviews LEFT JOIN images ON reviews.image_id = images.id';
        $query .= ' WHERE reviews.menu_id = ?';
        $query .= ' AND reviews.body != ""';
        $query .= ' AND reviews.published = 1';
        $query .= ' ORDER BY reviews.created_at DESC';
        $query .= ' LIMIT ?';

        return DB::select($query, [$menuId, self::LIMIT]);
    }
}
