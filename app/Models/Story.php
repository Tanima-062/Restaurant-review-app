<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Kyslik\ColumnSortable\Sortable;

class Story extends Model
{
    use Notifiable;
    use Sortable;

    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
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

    public function image()
    {
        return $this->hasOne('App\Models\Image', 'id', 'image_id');
    }

    /**
     * ストーリー取得.
     *
     * @param request request
     *
     * @return Collection ストーリー
     *
     * @throws Exception
     * @throws Illuminate\Database\QueryException
     */
    public function getStory($appCd, $page = null)
    {
        $query = Story::query();
        $query->where('published', 1);
        $query->where('app_cd', $appCd);
        $skip = !empty($page) ? config('takeout.story.perPage') * ($page - 1) : 0;
        $query->skip($skip)->take(config('takeout.story.perPage'));
        $story = $query->orderBy('created_at', 'DESC')->get();

        return $story;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed array                           $valid
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function scopeAdminSearchFilter($query, $valid)
    {
        if (isset($valid['id']) && !empty($valid['id'])) {
            $query->where('id', '=', $valid['id']);
        }

        if (isset($valid['name']) && !empty($valid['name'])) {
            $query->where('title', 'like', '%'.$valid['name'].'%');
        }

        if (isset($valid['url']) && !empty($valid['url'])) {
            $query->where('guide_url', 'like', '%'.$valid['url'].'%');
        }

        if (isset($valid['app_cd']) && !empty($valid['app_cd'])) {
            $query->where('app_cd', 'like', '%'.$valid['app_cd'].'%');
        }

        return $query;
    }
}
