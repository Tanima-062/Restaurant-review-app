<?php

namespace App\Http\Middleware;

use App\Modules\UserLogin;
use Closure;

class UserAuth
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
        $info = UserLogin::getLoginUser();
        //$info = null;
        //if (!AuthService::getUserInfo($request->bearerToken(), $info)) {
        if (!$info) {
            return response()->json([], 401);
        }

        $request->merge(['user' => $info]);

        return $next($request);
    }
}
