<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use stdClass;

class Stock extends Model
{
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
     * 在庫チェック.
     *
     * @param  string date
     * @param  int menuId
     * @param  int addition
     *
     * @return bool true:在庫あり false:在庫なし
     */
    public function hasStock(string $date, int $menuId, int $addition = 0): bool
    {
        $query = 'SELECT stock FROM
        (
        SELECT SUM(stock_number) AS stock FROM stocks AS s
        WHERE date = ?
        AND menu_id = ?
        UNION ALL
        SELECT SUM(rm.count) AS CN
        FROM reservations AS r
        INNER JOIN reservation_menus AS rm on rm.reservation_id = r.id
        WHERE DATE(pick_up_datetime) = ?
        AND rm.menu_id = ?
        ) AS FR';

        $queryResult = DB::select($query, [$date, $menuId, $date, $menuId]);

        $class = new stdClass();
        $class->stock = 0;
        $result[0] = isset($queryResult[0]) ? $queryResult[0] : $class;
        $result[1] = isset($queryResult[1]) ? $queryResult[1] : $class;
        $result[1]->stock = ($addition > 0) ? (int) $result[1]->stock + $addition : (int) $result[1]->stock;

        if (is_null($result[0]->stock) || $result[0]->stock < $result[1]->stock) {
            return false;
        }

        return true;
    }

    public static function scopeMenuId($query, $menu_id)
    {
        return $query->where('menu_id', $menu_id);
    }
}
