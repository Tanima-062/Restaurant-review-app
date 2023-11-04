<?php

/**
 * badge_color ライブラリ
 */
if (! function_exists('bage_color')) {
    function badge_color($id, $n)
    {
        $colors = config('const.theme.colors');

        return $colors[$id % $n];
    }
}

if (! function_exists('sidebar_menu_active')) {
    function sidebar_menu_active($path)
    {
        return (preg_match('%^'.$path.'.*%', Request::path()) ? 'active' : '');
    }
}

if (! function_exists('reservation_no_to_id')) {
    function reservation_no_to_id($reservationNo)
    {
        return substr($reservationNo, 2);
    }
}

if (! function_exists('host_url')) {
    function host_url()
    {
        if (App::environment('local')) {
            return 'https://jp.skyticket.jp/gourmet/';
        }
        if (App::environment('develop')) {
            return 'https://jp.skyticket.jp/gourmet/';
        }
        if (App::environment('staging')) {
            return 'https://skyticket.jp/gourmet/';
        }
        if (App::environment('production')) {
            return 'https://skyticket.jp/gourmet/';
        }
    }
}
    if (! function_exists('qs_url')) {
        function qs_url($path = null, $qs = array(), $secure = null)
        {
            $url = app('url')->to($path, $secure);
            if (count($qs)){

                foreach($qs as $key => $value){
                    $qs[$key] = sprintf('%s=%s',$key, urlencode($value));
                }
                $url = sprintf('%s?%s', $url, implode('&', $qs));
            }
            return $url;
        }
    }