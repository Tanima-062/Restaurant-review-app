<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Kyslik\ColumnSortable\Sortable;

class CommissionRate extends Model
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

    public static function scopeSettlementCompanyId($query, $id)
    {
        return $query->where('settlement_company_id', $id);
    }

    /**
     * 販売手数料ポリシーの条件
     *
     * @param $query
     * @param $request
     * @return mixed
     */
    public static function scopePolicyCheckFilter($query, $request)
    {
        $fromYM = sprintf('%04d-%02d', $request['apply_term_from_year'], $request['apply_term_from_month']);
        $toYM = sprintf('%04d-%02d', $request['apply_term_to_year'], $request['apply_term_to_month']);
        $from = date('Y-m-d 00:00:00', strtotime('first day of ' . $fromYM));
        $to = date('Y-m-d 23:59:59', strtotime('last day of ' . $toYM));

        // 精算会社
        $query->where('settlement_company_id', $request['settlement_company_id']);

        // 利用サービス
        $query->where('app_cd', $request['app_cd']);

        // 期間
        $query->where(function ($query) use ($from, $to) {
            $query->whereBetween('apply_term_from', [$from, $to])
                ->orWhere(function ($query) use ($from, $to) {
                    $query->whereBetween('apply_term_to', [$from, $to]);
                })
                ->orWhere(function ($query) use ($from, $to) {
                    $query->where('apply_term_from', '<=', $from)
                        ->where('apply_term_to', '>=', $to);
                });
        });
        // 席のみ
        $query->where('only_seat', '=', $request['only_seat']);

        // 公開
        $query->where('published', '=', 1);

        // edit時は自分自身を除く
        if (isset($request['id'])) {
            $query->where('id', '<>', $request['id']);
        }

        return $query;
    }

    /**
     * 申込時に適用すべき販売手数料のレコードを探す
     * @param $query
     * @param string $appCd
     * @param string $datetime
     * @param int $settlementCompanyId
     * @return mixed
     */
    public static function scopeSearchApplyRecord($query, string $appCd, string $datetime, int $settlementCompanyId)
    {
        $query->where('settlement_company_id', $settlementCompanyId)
              ->where('app_cd', $appCd)
              ->where('apply_term_from', '<=', $datetime)
              ->where('apply_term_to', '>=', $datetime);

        return $query;
    }
}
