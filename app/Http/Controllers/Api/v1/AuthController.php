<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\AuthLoginRequest;
use App\Http\Requests\Api\v1\AuthMypageRequest;
use App\Http\Requests\Api\v1\AuthReviewRequest;
use App\Modules\UserLogin;
use App\Services\AuthService;

class AuthController extends Controller
{

    public function checkLogin(\Illuminate\Http\Request $request)
    {
        $info = null;
        //$retToken = authService::getUserInfo($request->bearerToken(), $info);
        \Log::debug('1.PHPSESSID:'.$request->cookie('PHPSESSID'));
        \Log::debug('2.PHPSESSID:'.\Cookie::get('PHPSESSID', ''));
        $ret = userLogin::getLoginUser();
        if ($ret === false || (is_array($ret) && count($ret) == 0)) {
            return response()->toCamel(['error' => 'ログインしていません。'])->setStatusCode(401);
        }

        return response()->toCamel($ret);
    }

    public function login(AuthLoginRequest $request)
    {
        $ret = userLogin::login($request);

        $result = false;
        if (isset($ret['userId'])) {
            $result = authService::getApiToken($ret);
        }

        if ($result === false) {
            return response()->toCamel(['error' => 'ログインに失敗しました。'])->setStatusCode(401);
        }

        return response()->toCamel($ret)->cookie('PHPSESSID', session()->getId(), config('session.lifetime'));
    }

    public function logout(\Illuminate\Http\Request $request)
    {
        $retToken = authService::clearToken($request->bearerToken());

        $ret = userLogin::logout();

        if ($retToken === false || $ret === false) {
            return response()->toCamel(['error' => 'ログアウトに失敗しました。'])->setStatusCode(401);
        }

        return response()->toCamel($ret, 'status');
    }

    public function getMypage(AuthMypageRequest $request, AuthService $authService)
    {
        $resValues = [];
        $result = $authService->getMypage($request->reservationNo, $request->tel, $resValues);

        if (!$result) {
            return response()->toCamel($resValues);
        }

        return response()->toCamel($resValues);
    }

    public function registerReview(AuthReviewRequest $request, AuthService $authService)
    {
        $resValues = [];
        $result = $authService->registerReview($request, $resValues);

        if (!$result) {
            return response()->toCamel($resValues)->setStatusCode(500);
        }

        return response()->toCamel($resValues);
    }
}
