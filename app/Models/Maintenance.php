<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;

class Maintenance extends Model
{
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
     * メンテナンス中の物があるかどうか判定.
     *
     * @param  string type 指定のtypeがある場合、指定
     * @param  string msg  表示させるメッセージ
     *
     * @return bool true:メンテナンス中 false:メンテナンスなし
     */
    public static function isInMaintenance($type = null, &$msg = null)
    {
        $query = Maintenance::where('is_under_maintenance', 1);
        if (!is_null($type)) {
            $query->where('type', $type);
        }
        $result = $query->first();

        if (is_null($result)) {
            return false;
        }

        switch ($type) {
            case config('code.maintenances.type.stopSale'):
                $msg = Lang::get('message.maintenance.stopSale');
                break;
            case config('code.maintenances.type.stopEcon'):
                $msg = '';
                break;
            default:
                $msg = Lang::get('message.maintenance.default');
        }

        return true;
    }
}
