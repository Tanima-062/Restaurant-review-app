<?php

namespace App\Modules;

use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Log;

class StaffLogin
{
    /**
     * ユーザログイン(共通セッションに保存する).
     *
     * @return bool|array
     */
    public static function login(Request $request, &$rememberToken = '')
    {
        $output = [];
        $isLogin = false;
        $user = null;
        try {
            $query = Staff::whereIn('staff_authority_id', [
                config('const.staff.authority.CLIENT_ADMINISTRATOR'),
                config('const.staff.authority.CLIENT_GENERAL'),
            ]);
            if ($rememberToken === '') {
                $query = Staff::where('username', $request->userName);
                $user = $query->first();

                if (!$user || !Hash::check($request->password, $user->password)) {
                    $output[] = 'fail';
                }
            } else {
                // 最新のトークンはDBにあるのでDBからチェック
                $query = Staff::where('remember_token', $rememberToken);
                $user = $query->first();
                // DBにないトークンなら古いのでキャッシュが残ってればそこからユーザを取得
                if (is_null($user)) {
                    self::getUserInfo($rememberToken, $user);
                }
                // キャッシュにもないなら期限切れなので普通にログインしてください
                if (is_null($user)) {
                    $output[] = 'fail';
                } else {
                    return self::_returnStaff($user);
                }
            }
            if (count($output) === 0 && empty($rememberToken)) {
                // remember_token生成
                $staff = null;
                do {
                    //$token = Str::random(60);
                    $token = hash_hmac('sha256', uniqid($user->username), env('APP_KEY'));
                    $staff = Staff::where('remember_token', $token)
                    ->first();
                } while (!is_null($staff));

                // ユーザー情報をセット
                $loginUser = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'userName' => $user->username,
                    'staffAuthorityId' => $user->staff_authority_id,
                    'staffAuthorityName' => $user->staff_authority_name,
                    'lastLoginAt' => $user->last_login_at,
                    'rememberToken' => $token,
                ];

                // kvs上でユーザ情報と紐付け
                $key = config('takeout.staffApiToken.prefix').$token;
                Redis::set($key, json_encode($loginUser), config('takeout.staffApiToken.expiration'));
                $oldKey = config('takeout.staffApiToken.prefix').$user->remember_token;
                Redis::set($oldKey, json_encode($loginUser), config('takeout.staffApiToken.expiration'));

                // 保存チェック
                $cache = Redis::get($key);
                if (empty($cache)) {
                    throw new \Exception();
                }

                $output[] = 'ok';
                $isLogin = true;

                $user->remember_token = $token;
                $user->save();
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }

        return ($isLogin) ? self::_returnStaff(Staff::find($user->id)) : $isLogin;
    }

    public static function getUserInfo($token, &$info)
    {
        $key = config('takeout.staffApiToken.prefix').$token;
        $info = json_decode(Redis::get($key), true);
        if (empty($info)) {
            return false;
        }

        return true;
    }

    public static function logout($token)
    {
        $key = config('takeout.staffApiToken.prefix').$token;
        Redis::del($key);
        $info = Redis::get($key);
        if (empty($info)) {
            return true;
        }

        return false;
    }

    private static function _returnStaff($staff)
    {
        // ユーザー情報をセット
        $returnStaff = [
            'id' => $staff->id,
            'name' => $staff->name,
            'userName' => $staff->username,
            'staffAuthorityId' => $staff->staff_authority_id,
            'staffAuthorityName' => $staff->staffAuthority->name,
            'lastLoginAt' => $staff->last_login_at,
            'rememberToken' => $staff->remember_token,
        ];

        return $returnStaff;
    }
}
