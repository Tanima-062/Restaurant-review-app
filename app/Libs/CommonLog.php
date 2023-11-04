<?php

namespace App\Libs;

use Log;

/**
 * ログクラス
 * Class CommonLog.
 */
class CommonLog
{
    /**
     * アラートをslackへ出力.
     *
     * @param title
     * @param body
     */
    public static function notifyToChat($title, $body): void
    {
        // slackへ通知
        Log::critical($title,['body' => $body]);


    }
}
