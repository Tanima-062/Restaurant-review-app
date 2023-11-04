<?php

namespace App\Policies\Admin;

use App\Models\Menu;
use App\Models\Staff;
use Illuminate\Auth\Access\HandlesAuthorization;

class MenuPolicy
{
    use HandlesAuthorization;

    /**
     * アクセス制限(メニュー)
     *
     * @param Staff $user
     * @param Menu $menu
     * @return mixed
     */
    public function menu(Staff $user, Menu $menu)
    {
        // 自身の店舗IDのみ閲覧可
        if ($user->can('client-only')) {
            return $user->store_id === $menu->store_id;
        }

        return true;
    }
}
