<?php

namespace App\Libs\Session;

use Cache;

/**
 * localにあるredisサーバに入れる
 * Class DirectMockSession
 * @package App\Libs\Session
 */
class DirectMockSession
{
    /**
     * @param $key
     * @return array|null
     */
    public static function getSessionValue($key)
    {
        $sessionString = Cache::store('file')->get('PHPSESSID:'.$key);
        if (empty($sessionString)) {
            return null;
        }

        PhpSessionSerializer::setDefaultLogger();
        $sessionArray = PhpSessionSerializer::decodePhp($sessionString);

        return $sessionArray;
    }

    public static function setSession($key, $values, $expire)
    {
        PhpSessionSerializer::setDefaultLogger();
        $string = PhpSessionSerializer::encodePhp($values);

        Cache::store('file')->put('PHPSESSID:'.$key, $string, $expire);
    }
}
