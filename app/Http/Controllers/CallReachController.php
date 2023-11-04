<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CallReachJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CallReachController extends Controller
{
    public function receiveResult(Request $request, CallReachJob $callReachJob)
    {
        $callReachJob->receiveResult($request);
        Log::info(json_encode($request->input()));
        return response()->json();
    }
}
