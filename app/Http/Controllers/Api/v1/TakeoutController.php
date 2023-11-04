<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\MenuRequest;
use App\Http\Requests\Api\v1\TakeoutCloseRequest;
use App\Http\Requests\Api\v1\TakeoutCompleteRequest;
use App\Http\Requests\Api\v1\TakeoutSaveRequest;
use App\Http\Requests\Api\v1\TakeoutSearchRequest;
use App\Libs\HttpHeader;
use App\Models\Notice;
use App\Services\ReservationService;
use App\Services\TakeoutService;
use Illuminate\Http\Request;

class TakeoutController extends Controller
{
    public function search(TakeoutSearchRequest $request, TakeoutService $takeoutService)
    {
        try {
            $params = $request->all();
            $result = $takeoutService->search($params);
            $takeoutService->logSearchParameters($params);
        } catch (\Throwable $e) {
            \Log::error(
              sprintf(
                  '::params=(%s), error=%s',
                  json_encode($params),
                  $e
              ));

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return response()->toCamel($result, 'searchResult')
        ->withHeaders(HttpHeader::getPublicCacheConfig());
    }

    public function getRecommendation(Request $request, TakeoutService $takeoutService)
    {
        try {
            $params = $request->all();
            $result = $takeoutService->getRecommendation($params);
        } catch (\Throwable $e) {
            \Log::error(
              sprintf(
                  '::params=(%s), error=%s',
                  json_encode($params),
                  $e
              ));

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return response()->toCamel($result)
        ->withHeaders(HttpHeader::getPublicCacheConfig(config('takeout.recommendApiCdn.expiration')));
    }

    public function detailMenu(MenuRequest $request, TakeoutService $takeoutService)
    {
        try {
            $msg = '';
            $result = $takeoutService->detailMenu($request->route()->parameter('id'), $request->all(), $msg);
        } catch (\Throwable $e) {
            \Log::error(
              sprintf(
                  '::id=%s, params=%s, error=%s',
                  $request->route()->parameter('id'),
                  json_encode($request->all()),
                  $e->getTraceAsString()
              ));

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return response()->toCamel($result);
    }

    public function save(TakeoutSaveRequest $request, ReservationService $reservationService)
    {
        $resValues = [];
        $result = $reservationService->save($request->all(), $resValues);

        return response()->toCamel($resValues);
    }

    public function complete(TakeoutCompleteRequest $request, ReservationService $reservationService)
    {
        set_time_limit(60);
        $resValues = [];
        $reservationService->complete($request->sessionToken, $resValues, $request->cd3secResFlg);

        return response()->toCamel($resValues);
    }

    public function notice(Request $request, Notice $notice)
    {
        try {
            $result = $notice->getNotice(key(config('code.appCd.to')));
        } catch (\Throwable $e) {
            \Log::error(
              sprintf(
                  '::error=%s',
                  $e->getMessage()
              ));

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return response()->toCamel($result, 'notices');
    }

    public function getStory(Request $request, TakeoutService $takeoutService)
    {
        try {
            $result = $takeoutService->getStory($request);
        } catch (\Throwable $e) {
            \Log::error(
              sprintf(
                  '::error=%s',
                  $e->getMessage()
              ));

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return response()->toCamel($result);
    }

    public function getTakeoutGenre(Request $request, TakeoutService $takeoutService)
    {
        try {
            $genreCd = $request->route()->parameter('genreCd');
            $result = $takeoutService->getTakeoutGenre($genreCd, $request->lowerLevel);
        } catch (\Throwable $e) {
            \Log::error(
              sprintf(
                  '::genreCd=(%s), lowerLevel=(%s),error=%s',
                  $genreCd,
                  $request->lowerLevel,
                  $e
              ));

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return response()->toCamel($result);
    }

    public function close(TakeoutCloseRequest $request, TakeoutService $takeoutService)
    {
        try {
            $takeoutService->close($request);
        } catch (\Throwable $e) {
            \Log::error(
              sprintf(
                  '::error=%s',
                  $e->getMessage()
              ));

            return response()->toCamel(['status' => false]);
        }

        return response()->toCamel(['status' => true]);
    }

    public function searchBox(TakeoutService $takeoutService)
    {
        try {
            $result = $takeoutService->searchBox();
        } catch (\Throwable $e) {
            \Log::error(
                sprintf(
                    '::error=%s',
                    $e->getMessage()
                ));

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return response()->toCamel($result);
    }
}
