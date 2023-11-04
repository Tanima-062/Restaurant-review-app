<?php

namespace App\Libs;

/**
 * HTTPヘッダクラス
 * Class HttpHeaderUtil.
 */
class HttpHeader
{
    /**
     * CDNとブラウザ キャッシュを利用させる レスポンスヘッダを連想配列で返却
     * 注意：個人氏名など個人情報の入るコンテンツでは このヘッダは使わないこと！
     * オリジナルは skyticket.com include/util_inc.php _put_PublicCacheHttpResponseHeader($max_age, $last_modified=NULL).
     *
     * @param int $maxAge       キャッシュ期間を指定 [秒]
     * @param int $lastModified 最終更新日時を指定(Unixタイムスタンプ) default(現在の日時)
     *
     * @return array
     */
    public static function getPublicCacheConfig($maxAge = null, $lastModified = null)
    {
        $maxAge = is_null($maxAge) ? config('takeout.searchApiCdn.expiration') * 60 : $maxAge;

        if (is_null($lastModified)) {
            $lastModified = time();
        }

        return [
            'Expires' => -1,
            'Pragma' => '',
            'Cache-Control' => "public, max-age=$maxAge",
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified).' GMT',
        ];
    }
}
