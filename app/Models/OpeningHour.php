<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpeningHour extends Model
{
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
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

    public static function scopeStoreId($query, $store_id)
    {
        return $query->where('store_id', $store_id);
    }

    /**
     * mypage用の営業時間を取得.
     *
     * @param int storeId 店舗ID
     * @param int weekIndex 曜日の添字
     *
     * @return array
     */
    public function getMypageOpeningHour(int $storeId, int $weekIndex, $isHoliday)
    {
        $openingHours = OpeningHour::where('store_id', $storeId)
        ->get();

        $exp = [];
        for ($i = 0; $i < 8; ++$i) {
            if ($i === $weekIndex) {
                $exp[] = '[1]';
            } else {
                $exp[] = '[0-1]';
            }
        }
        if ($isHoliday) {
            $exp[7] = '[1]';
            $exp[$weekIndex] = '[0-1]';
        }

        $week = implode('', $exp);
        $week = '/'.$week.'/';
        // 例 week -> '/[0-1][0-1][0-1][0-1][0-1][0-1][0-1][0-1]/'

        $result = [];
        foreach ($openingHours as $openingHour) {
            if (preg_match($week, $openingHour->week) === 1) {
                $result[] = $openingHour;
            }
        }

        return $result;
    }
}
