<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\StoreRestaurantMenuRequest;
use App\Http\Requests\Api\v1\StoreBuffetRequest;
use App\Http\Requests\Api\v1\StoreSearchRequest;
use App\Http\Requests\Api\v1\StoreTakeoutMenuRequest;
use App\Services\StoreService;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    private $storeService;

    public function __construct(StoreService $storeService)
    {
        $this->storeService = $storeService;
    }

    public function get(Request $request)
    {
        try {
            $result = $this->storeService->get((int) $request->route()->parameter('id'));
        } catch (\Throwable $e) {
            \Log::error(
                sprintf(
                    '::params::id=%s, error=%s', $request->route()->parameter('id'), $e->getMessage()
                ));

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        // 店舗がない場合 or 店舗が非公開の場合
        if (is_null($result)) {
            return response()->toCamel($result)->setStatusCode(404);
        }

        return response()->toCamel($result);
    }

    public function getStoreTakeoutMenu(StoreTakeoutMenuRequest $request)
    {
        try {
            $result = $this->storeService->getStoreTakeoutMenu((int) $request->route()->parameter('id'), $request->pickUpDate, $request->pickUpTime);
        } catch (\Throwable $e) {
            \Log::error(
                sprintf(
                    '::params::id=%s, error=%s', $request->route()->parameter('id'), $e->getMessage()
                ));

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return response()->toCamel($result);
    }

    public function getStoreRestaurantMenu(StoreRestaurantMenuRequest $request)
    {
        try {
            $result = $this->storeService->getStoreRestaurantMenu((int) $request->route()->parameter('id'), $request->visitDate, $request->visitTime, $request->visitPeople, $request->dateUndecided);
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

    public function getStoreReview(Request $request)
    {
        try {
            $result = $this->storeService->getStoreReview((int) $request->route()->parameter('id'));
        } catch (\Throwable $e) {
            \Log::error(
                sprintf(
                    '::params::id=%s, error=%s', $request->route()->parameter('id'), $e->getMessage()
                ));

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return response()->toCamel($result);
    }

    public function getStoreImage(Request $request)
    {
        try {
            $result = $this->storeService->getStoreImage((int) $request->route()->parameter('id'));
        } catch (\Throwable $e) {
            \Log::error(__FILE__.'::'.__LINE__.'::'.__FUNCTION__.
                sprintf(
                    '::params::id=[%s], error=%s', $request->route()->parameter('id'), $e->getMessage()
                ));

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return response()->toCamel($result);
    }

    public function getBreadcrumb(Request $request)
    {
        try {
            $result = $this->storeService->getBreadcrumb((int) $request->route()->parameter('id'), $request->all());
        } catch (\Throwable $e) {
            \Log::error(__FILE__.'::'.__LINE__.'::'.__FUNCTION__.
                sprintf(
                    '::params::id=[%s], error=%s', $request->route()->parameter('id'), $e->getMessage()
                ));

            return response()->toCamel($result);
        }

        return response()->toCamel($result);
    }

    public function getCancelPolicy(Request $request)
    {
        try {
            $result = $this->storeService->getCancelPolicy((int) $request->route()->parameter('id'), $request->appCd);
        } catch (\Throwable $e) {
            \Log::error(__FILE__.'::'.__LINE__.'::'.__FUNCTION__.
                sprintf(
                    '::params::id=[%s], appCd=[%s], error=%s', $request->route()->parameter('id'), $request->appCd, $e->getMessage()
                ));

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return response()->toCamel($result);
    }

    public function storeSearch(StoreSearchRequest $request, StoreService $storeService)
    {
        try {
            $params = $request->all();
            $result = $storeService->storeSearch($params);
        } catch (\Throwable $e) {
            \Log::error(
              sprintf(
                  '::params=(%s), error=%s',
                  json_encode($params),
                  $e
              ));

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return response()->toCamel($result, 'searchResult');
    }

    /**
     * 指定した営業時間に該当するメニューを取得する
     *
     * @param StoreBuffetRequest $request
     * @return void
     */
    public function getStoreBuffet(StoreBuffetRequest $request)
    {
        try {
            $result = $this->storeService->getStoreBuffet((int) $request->route()->parameter('id'), $request->genreId);
        } catch (\Throwable $e) {
            \Log::error(
                sprintf(
                    '::params::id=%s, genreId=[%s], error=%s', $request->route()->parameter('id'), $request->genreId, $e->getMessage()
                ));

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return response()->toCamel($result);
    }
}
