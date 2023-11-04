<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Kyslik\ColumnSortable\Sortable;

class Notice extends Model
{
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

    protected static function boot()
    {
        parent::boot();

        self::creating(function ($notice) {
            return $notice->onCreatingHandler();
        });
        self::updating(function ($notice) {
            return $notice->onUpdatingHandler();
        });
    }

    private function onCreatingHandler()
    {
        $this->created_by = !empty(\Auth::user()) ? \Auth::user()->id : 0;
        $this->updated_by = !empty(\Auth::user()) ? \Auth::user()->id : 0;
    }

    private function onUpdatingHandler()
    {
        $this->updated_by = !empty(\Auth::user()) ? \Auth::user()->id : 0;
    }

    public function createdBy()
    {
        return $this->belongsTo('App\Models\Staff', 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo('App\Models\Staff', 'updated_by');
    }

    public static function scopeAdminSearchFilter($query, $valid)
    {
        if (isset($valid['datetime_from']) && !empty($valid['datetime_from'])) {
            $query->where('datetime_from', '>=', $valid['datetime_from']);
        }
        if (isset($valid['datetime_to']) && !empty($valid['datetime_to'])) {
            $query->where('datetime_to', '<=', $valid['datetime_to']);
        }
        if (isset($valid['updated_by']) && !empty($valid['updated_by'])) {
            $query->where('updated_by', '=', $valid['updated_by']);
        }

        return $query;
    }

    public static function scopeAdminNews($query)
    {
        $now = date('Y-m-d H:i:s');

        $query->where('ui_admin_flg', 1);
        $query->where('datetime_from', '<=', $now);
        $query->where('datetime_to', '>=', $now);
        $query->where('published', 1);

        return $query;
    }

    public static function scopeWebsiteNews($query)
    {
        $now = date('Y-m-d H:i:s');

        $query->where('ui_website_flg', 1);
        $query->where('datetime_from', '<=', $now);
        $query->where('datetime_to', '>=', $now);
        $query->where('published', 1);

        return $query;
    }

    public static function scopeWebsiteBackNumber($query)
    {
        $now = date('Y-m-d H:i:s');

        $query->where('ui_website_flg', 1);
        $query->where('datetime_to', '<', $now);
        $query->where('published', 1);

        return $query;
    }

    /**
     * お知らせ取得.
     *
     * @return Notice お知らせ
     *
     * @throws Exception
     * @throws Illuminate\Database\QueryException
     */
    public function getNotice(String $key)
    {
        $query = Notice::select(['id', 'title', 'message', 'datetime_from', 'datetime_to', 'published_at', 'updated_at']);
        $query->where('app_cd', $key);
        $query->where('datetime_from', '<=', Carbon::now());
        $query->where('datetime_to', '>', Carbon::now());
        $query->where('ui_website_flg', 1);
        $query->where('published', 1);
        $notice = $query->get();

        return ($notice->isEmpty()) ? [] : $notice->toArray();
    }
}
