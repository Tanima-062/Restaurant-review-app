<?php

namespace App\Libs;

/**
 * カンマ区切りの文字列を確認するクラス
 * Class HasProperty
 * @package App\Lib
 */
class HasProperty
{
    public static function implodedString($property, $imploded)
    {
        if (is_array($imploded)) {
            $imploded = implode(',', $imploded);
        }
        $exploded = explode(',', $imploded);

        return array_search($property, $exploded) !== false;
    }
}
