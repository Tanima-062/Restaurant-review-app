<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\DishUpListRequest;
use App\Http\Requests\Api\v1\DishUpLoginRequest;
use App\Http\Requests\Api\v1\DishUpStartCookingRequest;
use App\Modules\StaffLogin;
use App\Services\DishUpService;

class DishUpController extends Controller
{
    private $dishUpService;

    public function __construct(DishUpService $dishUpService)
    {
        $this->dishUpService = $dishUpService;
    }

    public function startCooking(DishUpStartCookingRequest $request)
    {
        $ret = $this->dishUpService->startCooking($request->reservationId);
        if (!$ret) {
            return response()->toCamel(['status' => false])->setStatusCode(500);
        }

        return response()->toCamel(['status' => true]);
    }

    public function list(DishUpListRequest $request)
    {
        try {
            $result = $this->dishUpService->list($request['staff']['id'], $request->reservationDate);
        } catch (\Throwable $e) {
            \Log::error(sprintf(
                '::params::reservationDate=[%s], error=%s', $request->reservationDate, $e->getMessage()
            ));

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return response()->toCamel($result);
    }

    public function checkLogin(\Illuminate\Http\Request $request)
    {
        $info = null;
        $ret = staffLogin::getUserInfo($request->bearerToken(), $info);

        if ($ret === false) {
            return response()->toCamel(['error' => 'ログインしていません。'])->setStatusCode(401);
        }

        return response()->toCamel($info);
    }

    public function login(DishUpLoginRequest $request)
    {
        $rememberToken = isset($request->rememberToken) ? $request->rememberToken : '';
        $staff = staffLogin::login($request, $rememberToken);
        if (empty($staff)) {
            return response()->toCamel(['error' => 'ログインに失敗しました。'])->setStatusCode(401);
        }

        return response()->toCamel($staff);
    }

    public function logout(\Illuminate\Http\Request $request)
    {
        $ret = staffLogin::logout($request->bearerToken());

        if ($ret === false) {
            return response()->toCamel(['error' => 'ログアウトに失敗しました。'])->setStatusCode(401);
        }

        $res = response()->toCamel($ret, 'status');

        return $res;
    }
}
