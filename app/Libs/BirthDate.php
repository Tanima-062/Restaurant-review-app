<?php

namespace App\Libs;

/**
 * 誕生日クラス
 * Class Cipher
 * @package App\Lib
 */
class BirthDate
{
    /**
     * 誕生日から年齢に変換する
     * @param $birthDate
     * @return string
     */
    public static function convertAge($birthDate)
    {
        $birthday = date('Ymd', strtotime($birthDate));
        $now = date("Ymd");
        return floor(($now-$birthday)/10000);
    }
}
