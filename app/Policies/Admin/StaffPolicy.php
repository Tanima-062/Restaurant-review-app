<?php

namespace App\Policies\Admin;

use App\Models\Staff;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class StaffPolicy
{
    use HandlesAuthorization;

    /**
     * アクセス制限(スタッフ)
     *
     * @param Staff $user
     * @param $staff
     * @return mixed
     */
    public function staff(Staff $user, $staff)
    {
        // クライアント管理者は、自身のみと全てのクライアント一般者の編集可
        if ($user->can('client-only')) {

            return $user->store_id === $staff->store_id &&
                $staff->staff_authority_id === 3 && $user->id === $staff->id ?
                Response::allow() : Response::deny() &&
                $staff->staff_authority_id === 4;
        }

        return true;
    }
}
