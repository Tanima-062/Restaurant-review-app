<?php

namespace App\Libs\Session;

class DirectSessionAdapter
{
    public static function getSessionValue($key)
    {
        switch (config('session.sky_driver')) {
            case 'redis':
                return DirectRedisSession::getSessionValue($key);
            case 'memcached':
                return DirectMemcachedSession::getSessionValue($key);
            default:
                return DirectMockSession::getSessionValue($key);
        }
    }

    public static function setSession($key, $values, $expire)
    {
        switch (config('session.sky_driver')) {
            case 'redis':
                DirectRedisSession::setSession($key, $values, $expire);
                break;
            case 'memcached':
                DirectMemcachedSession::setSession($key, $values, $expire);
                break;
            default:
                DirectMockSession::setSession($key, $values, $expire);
                break;
        }
    }
}