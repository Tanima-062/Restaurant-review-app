<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

class SettlementDownload extends Model
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

    public function settlementCompany()
    {
        return $this->belongsTo('App\Models\SettlementCompany', 'settlement_company_id', 'id');
    }
}
