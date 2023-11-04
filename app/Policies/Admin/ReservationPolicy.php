<?php

namespace App\Policies\Admin;

use App\Models\Reservation;
use App\Models\Staff;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReservationPolicy
{
    use HandlesAuthorization;

    /**
     * アクセス制限(予約)
     *
     * @param Staff $user
     * @param Reservation $reservation
     * @return mixed
     */
    public function reservation(Staff $user, Reservation $reservation)
    {
        // 自身の店舗IDのみ閲覧可
        if ($user->can('client-only')) {
            return $user->store_id === $reservation->reservationStore->store_id;
        }

        return true;
    }
}
