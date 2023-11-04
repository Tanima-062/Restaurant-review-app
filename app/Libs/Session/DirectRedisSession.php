<?php

namespace App\Libs\Session;

use Redis;

class DirectRedisSession
{
    private static function connect()
    {
        $host = env('REDIS_HOST', '127.0.0.1');
        $port = env('REDIS_PORT', 6379);
        $redis = new Redis();
        $redis->connect($host, $port);
        return $redis;
    }

    /**
     * @param $key
     * @return array|null
     */
    public static function getSessionValue($key)
    {
        $con = self::connect();
        $sessionString = $con->get('PHPREDIS_SESSION:'.$key);
        $con->close();
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

        $con = self::connect();
        $con->set('PHPREDIS_SESSION:'.$key, $string);
        $con->expire('PHPREDIS_SESSION:'.$key, $expire * 60);
        $con->close();
    }
}
