<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kyslik\ColumnSortable\Sortable;

class Staff extends Authenticatable
{
    use Notifiable;
    use Sortable;

    /**
     * @var string
     */
    protected $table = 'staff';

    protected $guarded = ['id'];

    public function staffAuthority(): belongsTo
    {
        return $this->belongsTo(StaffAuthority::class, 'staff_authority_id', 'id');
    }

    public function store(): belongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    public function settlementCompany()
    {
        return $this->hasOneThrough(SettlementCompany::class, Store::class, 'id', 'id', 'store_id', 'settlement_company_id');
    }

    public function checkFirstLogin()
    {
        return ($this->password_modified != '0000-00-00 00:00:00');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed array $valid
     * @param int $storeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function scopeAdminSearchFilter($query, $valid, $storeId = 0)
    {
        if (isset($valid['id']) && !empty($valid['id'])) {
            $query->where('id', '=', $valid['id']);
        }
        if (isset($valid['name']) && !empty($valid['name'])) {
            $query->where('name', 'like', '%'.$valid['name'].'%');
        }
        if (isset($valid['username']) && !empty($valid['username'])) {
            $query->where('username', 'like', '%'.$valid['username'].'%');
        }
        if (isset($valid['client_id']) && !empty($valid['client_id'])) {
            $query->where('client_id', '=', $valid['client_id']);
        }
        if (isset($valid['staff_authority_id']) && !empty($valid['staff_authority_id'])) {
            $query->where('staff_authority_id', '=', $valid['staff_authority_id']);
        }
        if ($storeId > 0) {
            $query->where('store_id', '=', $storeId);
        }

        return $query;
    }

    public static function scopeAdmin($query)
    {
        // 社内管理者
        return $query->where('staff_authority_id', 1);
    }

    /**
     * スタッフ一覧の出し分け
     *
     * @param $query
     * @return mixed
     */
    public function scopeList($query)
    {
        if (\Gate::check('clientAdmin-only')) {
            return $query->where('staff_authority_id', '=', 3)
                ->where('id', \Auth::user()->id)
                ->orWhere(function ($query) {
                    $query->where('staff_authority_id', '=', 4);
                });
        }

        return null;
    }

    public static function scopePublished($query)
    {
        return $query->where('published', 1);
    }
}
