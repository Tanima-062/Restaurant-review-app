<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\AreaService;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function getArea(Request $request, AreaService $areaService)
    {
        try {
            $areaCd = $request->route()->parameter('areaCd');
            $result = $areaService->getArea($areaCd, $request->lowerLevel);
        } catch (\Throwable $e) {
            \Log::error(
                sprintf(
                    '::areaCd=(%s), lowerLevel=(%s),error=%s',
                    $areaCd,
                    $request->lowerLevel,
                    $e
                ));

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return response()->toCamel($result);
    }

    public function getAreaAdmin(Request $request, AreaService $areaService)
    {
        try {
            $params = $request->all();

            $result = $areaService->getAreaAdmin($params);
        } catch (\Throwable $e) {
            \Log::error($e);

            return response()->toCamel(['error' => $e->getMessage()])->setStatusCode(500);
        }

        // return response()->toCamel($result)にすると中エリアの結果が1つ足りなくなるため注意
        return response($result);
    }
}
