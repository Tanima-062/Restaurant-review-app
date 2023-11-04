<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\UtilService;
use Illuminate\Http\Request;

class UtilController extends Controller
{
    public function saveOrderCode(Request $request, UtilService $utilService)
    {
        $params = $request->all();
        $result = $utilService->saveOrderCode($params);

        return response($result);
    }
}
