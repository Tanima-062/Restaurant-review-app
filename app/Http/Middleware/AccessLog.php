<?php

namespace App\Http\Middleware;

use Closure;

class AccessLog
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
        \Log::info(request()->fullUrl());

        return $next($request);
    }
}
