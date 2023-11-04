<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

class SettlementCompany extends Model
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
        return $this->hasMany('App\Models\Store', 'settlement_company_id', 'id');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed array $valid
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
        if (isset($valid['tel']) && !empty($valid['tel'])) {
            $query->where('tel', 'like', '%'.$valid['tel'].'%');
        }
        if (isset($valid['postal_code']) && !empty($valid['postal_code'])) {
            $query->where('postal_code', 'like', '%'.$valid['postal_code'].'%');
        }

        return $query;
    }
}
