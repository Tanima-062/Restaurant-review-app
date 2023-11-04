<?php

namespace App\Modules\Payment\Econ;

class EcontextConfig
{

    public static function load()
    {
        // 二重定義防止
        if (!defined('ECONTEXT_LOADED')) {
            define('ECONTEXT_LOADED', 1);
        } else {
            return;
        }

        if (env('APP_ENV') == 'local' || env('APP_ENV') == 'develop') {
            // 検証用の接続先
            define('ECONTEXT_PAYMENT_URL', 'https://test.econ.ne.jp/multiexapi/'); // トークンAPI用エンドポイント
            define('ECONTEXT_PAYMENT_MULTI_URL', 'https://test.econ.ne.jp/odr/rcv/rcv_odr.aspx'); // マルチAPI用エンドポイント
            define('ECONTEXT_PAYMENT_MULTI_ORDER_STATUS_URL', 'https://test.econ.ne.jp/odr/Nyukinsyoukai/ReqInfo.aspx'); // マルチAPI(入金照会のみ)用エンドポイント

            define('ECONTEXT_SITE_NAME', 'ｓｋｙｔｉｃｋｅｔ');                  //サイト名
            define('ECONTEXT_CASH_SITE_CODE', '366401');                      // 現金決済用サイトコード
            define('ECONTEXT_PAYEASY_SITE_CODE', '366403');                   // Payeasyサイトコード
            define('ECONTEXT_CREDIT_SITE_CODE', '366402');                    // クレジットカード用サイトコード
            define('ECONTEXT_CASH_WITHIN_4DAYS_SITE_CODE', '366404');         // 現金決済用サイトコード(出発4日以内)
            define('ECONTEXT_CASH_CHECK_CODE', '366401000000');               // 現金決済用チェックコード
            define('ECONTEXT_PAYEASY_CHECK_CODE', '366403000000');            // Payeasyチェックコード
            define('ECONTEXT_CREDIT_CHECK_CODE', '366402000000');             // クレジットカード用チェックコード
            define('ECONTEXT_CASH_WITHIN_4DAYS_CHECK_CODE', '366404000000');  // 現金決済用チェックコード(出発4日以内)
        } else {
            // 本番接続
            define('ECONTEXT_PAYMENT_URL', 'https://www.econ.ne.jp/multiexapi/'); // トークンAPI用エンドポイント
            define('ECONTEXT_PAYMENT_MULTI_URL', 'https://www.econ.ne.jp/odr/rcv/rcv_odr.aspx'); // マルチAPI用エンドポイント
            define('ECONTEXT_PAYMENT_MULTI_ORDER_STATUS_URL', 'https://www.econ.ne.jp/odr/Nyukinsyoukai/ReqInfo.aspx'); // マルチAPI(入金照会のみ)用エンドポイント

            define('ECONTEXT_SITE_NAME', 'ｓｋｙｔｉｃｋｅｔ');                  //サイト名
            define('ECONTEXT_CASH_SITE_CODE', '366401');                      // 現金決済用サイトコード
            define('ECONTEXT_PAYEASY_SITE_CODE', '366403');                   // Payeasyサイトコード
            define('ECONTEXT_CREDIT_SITE_CODE', '366402');                    // クレジットカード用サイトコード
            define('ECONTEXT_CASH_WITHIN_4DAYS_SITE_CODE', '366404');         // 現金決済用サイトコード(出発4日以内)
            define('ECONTEXT_CASH_CHECK_CODE', '919054567663');               // 現金決済用チェックコード
            define('ECONTEXT_PAYEASY_CHECK_CODE', '130034366653');            // Payeasyチェックコード
            define('ECONTEXT_CREDIT_CHECK_CODE', '329014166603');             // クレジットカード用チェックコード
            define('ECONTEXT_CASH_WITHIN_4DAYS_CHECK_CODE', '645004061643');  // 現金決済用チェックコード(出発4日以内)
        }
    }
}
