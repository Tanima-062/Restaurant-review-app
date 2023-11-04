<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\CalcPriceMenuRequest;
use App\Http\Requests\Api\v1\menuVacancyRequest;
use App\Http\Requests\Api\v1\RestaurantChangeRequest;
use App\Http\Requests\Api\v1\RestaurantCompleteRequest;
use App\Http\Requests\Api\v1\RestaurantSaveRequest;
use App\Http\Requests\Api\v1\RestaurantDetailMenuRequest;
use App\Libs\HttpHeader;
use App\Models\Notice;
use App\Services\RestaurantReservationService;
use App\Services\RestaurantService;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function notice(Request $request, Notice $notice)
    {
        try {
            $result = $notice->getNotice(key(config('code.appCd.rs')));
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

    public function detailMenu(RestaurantDetailMenuRequest $request, RestaurantService $restaurantService)
    {
        try {
            $result = $restaurantService->detailMenu($request->route()->parameter('id'), $request->all());
        } catch (\Throwable $e) {
            \Log::error(
              sprintf(
                  '::id=%s, error=%s',
                  $request->route()->parameter('id'),
                  $e->getTraceAsString()
              ));

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return response()->toCamel($result);
    }

    public function save(RestaurantSaveRequest $request, RestaurantReservationService $reservationService)
    {
        $resValues = [];
        $result = $reservationService->save($request->all(), $resValues);

        return response()->toCamel($resValues);
    }

    public function complete(RestaurantCompleteRequest $request, RestaurantReservationService $reservationService)
    {
        set_time_limit(60);
        $resValues = [];
        $result = $reservationService->complete($request->sessionToken, $resValues);

        return response()->toCamel($resValues);
    }

    public function getStory(Request $request, RestaurantService $restaurantService)
    {
        try {
            $result = $restaurantService->getStory($request);
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

    public function searchBox(RestaurantService $restaurantService)
    {
        try {
            $result = $restaurantService->searchBox();
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

    public function getRecommendation(Request $request, RestaurantService $restaurantService)
    {
        try {
            $params = $request->all();
            $result = $restaurantService->getRecommendation($params);
        } catch (\Throwable $e) {
            \Log::error(
                sprintf(
                    '::error=%s',
                    $e->getMessage()
                ));

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return response()->toCamel($result)
            ->withHeaders(HttpHeader::getPublicCacheConfig(config('restaurant.recommendApiCdn.expiration')));
    }

    public function change(RestaurantChangeRequest $request, RestaurantReservationService $restaurantReservationService)
    {
        $resValues = [];
        $result = $restaurantReservationService->change($request->all(), $resValues);

        return response()->toCamel($resValues);
    }

    public function calcCancelFee(Request $request, RestaurantReservationService $restaurantReservationService)
    {
        try {
            $resValues = [];
            $result = $restaurantReservationService->calcCancelFee($request->reservationId, $resValues);
        } catch (\Throwable $e) {
            \Log::error(
                sprintf(
                    '::error=%s',
                    $e->getMessage()
                ));

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return response()->toCamel($resValues);
    }

    public function calcPriceMenu(CalcPriceMenuRequest $request, RestaurantReservationService $restaurantReservationService)
    {
        try {
            $resValues = [];
            $params = $request->all();
            $result = $restaurantReservationService->calcPriceMenu($params, $resValues);
        } catch (\Throwable $e) {
            \Log::error(
                sprintf(
                    '::error=%s',
                    $e->getMessage()
                ));

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return response()->toCamel($resValues);
    }

    public function cancel(Request $request, RestaurantReservationService $restaurantReservationService)
    {
        $resValues = [];
        $result = $restaurantReservationService->cancel($request->reservationId, $resValues);

        return response()->toCamel($resValues);
    }

    public function menuVacancy(menuVacancyRequest $request, RestaurantService $restaurantService)
    {
        try {
            $result = $restaurantService->menuVacancy($request->all());
        } catch (\Throwable $e) {
            throw $e;
            \Log::error(
                sprintf(
                    '::params::id=%s, error=%s', $request->route()->parameter('id'), $e->getMessage()
                ));

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return response()->toCamel($result);
    }

    public function directPayment(Request $request, RestaurantReservationService $restaurantReservationService)
    {
        $resValues = [];
        $restaurantReservationService->directPayment($request->reservationId, $resValues);

        return response()->toCamel($resValues);
    }
}
