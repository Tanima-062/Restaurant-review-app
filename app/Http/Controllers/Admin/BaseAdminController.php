<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class BaseAdminController extends Controller
{
    public function __construct()
    {
    }

    /**
     * クライアントの使用端末がMobileかPCか判定.
     *
     * @param $request
     */
    protected function isMobile($request): string
    {
        $user_agent = $request->header('User-Agent');
        if ((strpos($user_agent, 'iPhone') !== false)
            || (strpos($user_agent, 'iPod') !== false)
            || (strpos($user_agent, 'Android') !== false)) {
            $agent = 'mobile';
        } else {
            $agent = 'pc';
        }

        return $agent === 'mobile';
    }
}
