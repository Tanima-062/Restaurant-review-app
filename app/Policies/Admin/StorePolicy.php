<?php

namespace App\Policies\Admin;

use App\Models\Staff;
use App\Models\Store;
use Illuminate\Auth\Access\HandlesAuthorization;

class StorePolicy
{
    use HandlesAuthorization;

    /**
     * アクセス制限(スタッフ)
     *
     * @param  Staff  $user
     * @param  Store  $store
     * @return mixed
     */
    public function store(Staff $user, Store $store)
    {
        // 自身の店舗IDのみ閲覧可
        if ($user->can('client-only')) {
            return $user->store_id === $store->id;
        }

        return true; // それ以外は、アクセス可
    }
}
