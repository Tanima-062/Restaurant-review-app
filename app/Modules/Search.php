<?php

namespace App\Modules;

use App\Models\Menu;
use App\Models\Store;

class Search
{
    /**
     * ２地点間の距離(m)を求める.
     *
     * @param float lat1 緯度１
     * @param float lon1 経度１
     * @param Menu menu メニュー
     * @param int type 1:現在地とメニューに紐づく店の距離を求める
     *                 2:現在地とメニューに紐づく駅の距離を求める
     *
     * @return float 距離(m)
     */
    public static function getDistance($lat1, $lon1, Menu $menu, $type)
    {
        if (is_null($menu->store)) {
            return null;
        }

        if ($type === 1) {
            $lat2 = $menu->store->latitude;
            $lon2 = $menu->store->longitude;
        } elseif ($type === 2) {
            $lat2 = $menu->store->stations->latitude;
            $lon2 = $menu->store->stations->longitude;
        }

        return self::_distance($lat1, $lon1, $lat2, $lon2);
    }

    public static function getStoreDistance($lat1, $lon1, $id)
    {
        $store = Store::find($id);
        $lat2 = $store->latitude;
        $lon2 = $store->longitude;

        return self::_distance($lat1, $lon1, $lat2, $lon2);
    }

    /**
     * ２地点間の距離(m)を求める
     * ヒュベニの公式から求めるバージョン.
     *
     * @param float lat1 緯度１
     * @param float lon1 経度１
     * @param float lat2 緯度２
     * @param float lon2 経度２
     * @param bool  mode 測地系 true:世界(default) false:日本
     *
     * @return float 距離(m)
     */
    private static function _distance($lat1, $lon1, $lat2, $lon2, $mode = true)
    {
        // 緯度経度をラジアンに変換
        $radLat1 = deg2rad($lat1); // 緯度１
        $radLon1 = deg2rad($lon1); // 経度１
        $radLat2 = deg2rad($lat2); // 緯度２
        $radLon2 = deg2rad($lon2); // 経度２

        // 緯度差
        $radLatDiff = $radLat1 - $radLat2;

        // 経度差算
        $radLonDiff = $radLon1 - $radLon2;

        // 平均緯度
        $radLatAve = ($radLat1 + $radLat2) / 2.0;

        // 測地系による値の違い
        $a = $mode ? 6378137.0 : 6377397.155; // 赤道半径
        $b = $mode ? 6356752.314140356 : 6356078.963; // 極半径
        //$e2 = ($a*$a - $b*$b) / ($a*$a);
        $e2 = $mode ? 0.00669438002301188 : 0.00667436061028297; // 第一離心率^2
        //$a1e2 = $a * (1 - $e2);
        $a1e2 = $mode ? 6335439.32708317 : 6334832.10663254; // 赤道上の子午線曲率半径

        $sinLat = sin($radLatAve);
        $W2 = 1.0 - $e2 * ($sinLat * $sinLat);
        $M = $a1e2 / (sqrt($W2) * $W2); // 子午線曲率半径M
        $N = $a / sqrt($W2); // 卯酉線曲率半径

        $t1 = $M * $radLatDiff;
        $t2 = $N * cos($radLatAve) * $radLonDiff;
        $dist = sqrt(($t1 * $t1) + ($t2 * $t2));

        return $dist;
    }
}
