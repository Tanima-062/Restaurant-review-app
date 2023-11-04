<?php

namespace App\Libs;

/**
 * 日付重複を配列
 * Class DateRange
 * @package App\Lib
 */
class DateTimeDuplicate
{
    /**
     * 日付範囲
     * @param $startDate1
     * @param $endDate1
     * @param $startDate2
     * @param $endDate2
     * @return bool
     */
    public static function isDuplicatePeriod($startDate1, $endDate1, $startDate2, $endDate2): bool
    {
        return ($startDate1 <= $endDate2 && $startDate2 <= $endDate1);
    }

    /**
     * 時間範囲
     * @param $startTime1
     * @param $endTime1
     * @param $startTime2
     * @param $endTime2
     * @return bool
     */
    public static function isTimeDuplicate($startTime1, $endTime1, $startTime2, $endTime2): bool
    {
        return ($startTime1 < $endTime2 && $startTime2 < $endTime1);
    }
}
