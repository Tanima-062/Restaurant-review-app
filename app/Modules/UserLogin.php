<?php

namespace App\Modules;

use App\Libs\BirthDate;
use App\Libs\Cipher;
use App\Libs\Session\DirectSessionAdapter;
use App\Models\CmTmLang;
use App\Models\CmTmUser;
use App\Models\MailDBQueue;
use Cookie;
use Illuminate\Http\Request;
use Log;

class UserLogin
{
    const FLAG_LANG_LIST = [
        'subdomain' => ['en', 'jp', 'zh', 'zh-tw', 'ko', 'ar', 'nl', 'fr', 'de', 'it', 'ms', 'pt', 'ru', 'es', 'th', 'tl', 'tr', 'vi'],
        'description' => [
            'English', '日本語', '简体中文', '繁體中文', '한국어', 'العربية', 'Nederlands', 'Française', 'Deutsch',
            'Italiano', 'Bahasa Melayu', 'Português', 'Русский', 'Español', 'ภาษาไทย', 'Tagalog', 'Türkçe', 'Tiếng Việt',
        ],
    ];

    public static $testMode = false;
    public static $phpSessId = '';
    public static $phpSessValue = '';

    private static function getMetaData()
    {
        return [
            'title' => app('ConstConfig')->getName('content.META_DATA_TITLE'),
            'info_title' => app('ConstConfig')->getName('content.META_DATA_INFO_TITLE'),
            'short_title' => app('ConstConfig')->getName('content.META_DATA_SHORT_TITLE'),
            'description' => app('ConstConfig')->getName('content.META_DATA_DESCRIPTION'),
            'keywords' => app('ConstConfig')->getName('content.META_DATA_KEYWORDS'),
        ];
    }

    public static function setMode($mode)
    {
        self::$testMode = $mode;
    }

    private static function getSessionKey()
    {
        if (empty(self::$phpSessId)) {
            return Cookie::get('PHPSESSID', '');
        }

        return self::$phpSessId;
    }

    private static function getSessionValue()
    {
        if (!config('session.direct_access')) {
            return [];
        }

        return DirectSessionAdapter::getSessionValue(self::getSessionKey());
    }

    private static function setSession($key, $values, $expire)
    {
        if (!config('session.direct_access')) {
            return;
        }
        if (\App::runningInConsole()) {
            self::$phpSessId = $key;
        }

        DirectSessionAdapter::setSession($key, $values, $expire);
    }

    private static function resetSession($value)
    {
        session()->regenerate();
        self::setSession(session()->getId(), $value, env('SESSION_LIFETIME'));

        if (config('session.direct_access')) {
            $phpSessionId = self::getSessionKey();

            if (!$phpSessionId || $phpSessionId != session()->getId()) {
                if (isset($value['user']['unread_cnt'])) {
                    $unreadCnt = $value['user']['unread_cnt'];
                } else {
                    $unreadCnt = 0;
                }

                $dest = [
                    'name' => 'PHPSESSID',
                    'value' => session()->getId(),
                    'lifeTime' => env('SESSION_LIFETIME'),
                    'unreadCnt' => $unreadCnt,
                ];

                self::$phpSessValue = base64_encode(json_encode($dest));
            }
        }
    }

    /**
     * 会員か否か.
     *
     * @return bool true:会員 false:非会員
     */
    public static function isMember($email)
    {
        $user = CmTmUser::where('email_enc', Cipher::encrypt($email))->first();

        return is_null($user) ? false : true;
    }

    /**
     * ユーザログイン(共通セッションに保存する).
     *
     * @return bool|array
     */
    public static function login(Request $request)
    {
        $output = [];
        $isLogin = false;
        try {
            $user = CmTmUser::where('email_enc', Cipher::encrypt($request->loginId))
                ->where('password_enc', Cipher::encrypt(hash('sha384', $request->password)))
                ->where('member_status', 1)
                ->first();
            if (!$user || $user->password_enc != hash('sha384', $request->password)) {
                $output[] = 'fail';
            } else {
                $lang = CmTmLang::iso639CountryCd('ja')->get()->toArray()[0];
                // ユーザー情報をセット
                $loginUser = [
                    'user_id' => $user->user_id,
                    'user_age' => BirthDate::convertAge($user->birth_date),
                    'user_name' => $user->family_name_passport_enc,
                    'user_email' => $user->email_enc,
                    'user_member_status' => $user->member_status,
                    'user' => ['unread_cnt' => self::getUnreadMail($user->user_id)],
                    //'meta_data'          => self::getMetaData(),
                    'flag_lang_list' => self::FLAG_LANG_LIST,
                    'access_allow' => 1,
                    'country_code' => 'jp',
                    'lang' => $lang,
                    'lang_id' => $lang['lang_id'],
                ];

                self::resetSession($loginUser);

                $output[] = 'ok';
                $isLogin = true;
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        } finally {
            $output[] = $request->loginId;
            $output[] = session()->getId();
            $server = $request->server();
            $output[] = (!empty($server['REMOTE_ADDR'])) ? $server['REMOTE_ADDR'] : '';
            $output[] = (!empty($server['HTTP_USER_AGENT'])) ? $server['HTTP_USER_AGENT'] : '';
            //Log::info(implode(',', $output));

            return ($isLogin) ? self::getLoginUser($user->user_id) : $isLogin;
        }
    }

    private static function getUserId()
    {
        $sessionArray = self::getSessionValue();

        if (empty($sessionArray['user_id'])) {
            return 0;
        }

        $user = CmTmUser::find($sessionArray['user_id']);
        if ($user) {
            return $user->user_id;
        }

        return 0;
    }

    public static function isLogin()
    {
        return (self::getUserId()) ? true : false;
    }

    public static function skyLogout()
    {
        return redirect(url('/', null, true).'/user/logout.php');
    }

    public static function logout()
    {
        if (!self::isLogin()) {
            return false;
        }

        self::setSession(self::getSessionKey(), [], 1);
        self::resetSession(self::getLoginUser());

        return true;
    }

    /**
     * ユーザ情報を取得する.
     *
     * @param int userId
     *
     * @return array
     */
    public static function getLoginUser($userId = 0)
    {
        if ($userId == 0 && !self::isLogin()) {
            return [];
        }

        if ($userId == 0) {
            $userId = self::getUserId();
        }

        if ($userId == 0) {
            return [];
        }

        $user = CmTmUser::find($userId);

        $loginUser = [
            'userId' => $userId,
            'email' => $user->email_enc,
            'tel' => $user->tel_enc,
            'lastName' => $user->family_name_passport_enc,
            'firstName' => $user->first_name_passport_enc,
            'birthDate' => explode('-', $user->birth_date),
            'unreadCnt' => self::getUnreadMail($userId),
        ];

        $gender = (function ($user) {
            if ($user->gender_id == 1) {
                return config('code.gender.MAN.code');
            } else {
                return config('code.gender.WOMAN.code');
            }
        })($user);

        $loginUser['gender'] = $gender;

        return $loginUser;
    }

    public static function getUnreadMail($userId)
    {
        $sessionArray = self::getSessionValue();
        if (!empty($sessionArray['user']['unread_cnt'])) {
            return $sessionArray['user']['unread_cnt'];
        }

        $mails = MailDBQueue::with(['cmThReadMail'])
            ->unreadMail($userId)->get();

        $count = 0;
        foreach ($mails as $mail) {
            if (is_null($mail->cmThReadMail)) {
                ++$count;
            }
        }

        return $count;
    }
}
