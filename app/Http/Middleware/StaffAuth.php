<?php

namespace App\Http\Middleware;

use App\Modules\StaffLogin;
use Closure;

class StaffAuth
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $info = null;
        if (!StaffLogin::getUserInfo($request->bearerToken(), $info)) {
            return response()->json([], 401);
        }

        $request->merge(['staff' => $info]);

        return $next($request);
    }
}
