<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kyslik\ColumnSortable\Sortable;

class Station extends Model
{
    use Sortable;
    //use SoftDeletes;

    protected $guarded = ['id'];

    public function prefecture()
    {
        return $this->hasOne('App\Models\Prefecture', 'id', 'prefecture_id');
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

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
            $query->where('name', 'like', '%'.$valid['name'].'%');
        }

        return $query;
    }
}
