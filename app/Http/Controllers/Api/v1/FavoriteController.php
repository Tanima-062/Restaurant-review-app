<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\FavoriteDeleteRequest;
use App\Http\Requests\Api\v1\FavoriteRegisterRequest;
use App\Http\Requests\Api\v1\FavoriteRequest;
use App\Models\Favorite;
use App\Modules\UserLogin;
use App\Services\FavoriteService;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    private $favorite;

    public function __construct(Favorite $favorite)
    {
        $this->favorite = $favorite;
    }

    public function get(FavoriteRequest $request, FavoriteService $favoriteService)
    {
        try {
            // ログインしてるならユーザ情報をセット
            //$info = null;
            //authService::getUserInfo($request->bearerToken(), $info);
            $info = UserLogin::getLoginUser();
            $userId = empty($info) ? 0 : $info['userId'];
            if (strtoupper($request->appCd) === key(config('code.appCd.to'))) {
                $result = $favoriteService->get($userId, $request->pickUpDate, $request->pickUpTime, $request->input('menuIds', ''));
            } else {
                $result = $favoriteService->getFavoriteStores($userId, $request->all());
            }
        } catch (\Throwable $e) {
            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return response()->toCamel($result);
    }

    public function register(FavoriteRegisterRequest $request)
    {
        try {
            $httpStatusCode = 200;
            list($result, $msg) = DB::transaction(function () use ($request) {
                $result = $this->favorite->registerFavorite(
                    $request->user['userId'],
                    $request->id,
                    $request->appCd,
                    $msg
                );

                return [$result, $msg];
            });
        } catch (\Throwable $e) {
            \Log::error($e->getMessage());
            $httpStatusCode = 500;
            $result = false;
            $msg = 'fail to register a favorite';
        }
        $res = [
            'status' => $result,
            'message' => $msg,
        ];

        return response()->toCamel($res)->setStatusCode($httpStatusCode);
    }

    public function delete(FavoriteDeleteRequest $request)
    {
        $result = DB::transaction(function () use ($request) {
            return $this->favorite->deleteFavorite(
                $request->user['userId'],
                $request->id,
                $request->appCd
            );
        });

        $httpStatusCode = ($result === false) ? 500 : 200;
        $message = ($result === false) ? ['error' => 'fail to delete a favorite'] : [];

        return response()->toCamel($message)->setStatusCode($httpStatusCode);
    }
}
