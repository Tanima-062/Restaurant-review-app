<?php

namespace App\Http\Middleware;

use Closure;

class RedirectIfFirstLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$request->user()->checkFirstLogin()) {
            return redirect('/admin/staff/edit_password_first_login');
        }
        return $next($request);
    }
}
