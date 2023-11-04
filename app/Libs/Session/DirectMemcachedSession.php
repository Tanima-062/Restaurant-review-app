<?php

namespace App\Libs\Session;

use Cache;

class DirectMemcachedSession
{
    /**
     * @param $key
     * @return array|null
     */
    public static function getSessionValue($key)
    {
        $sessionString = Cache::store('memcached')->get('PHP_SESSION:'.$key);
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

        // keyのprefixは正しいか不明
        Cache::store('memcached')->put('PHP_SESSION:'.$key, $string, $expire);
    }
}
