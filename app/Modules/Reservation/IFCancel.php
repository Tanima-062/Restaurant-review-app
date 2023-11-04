<?php

namespace App\Modules\Reservation;

use App\Models\Reservation;

interface IFCancel
{
    public function cancel(Reservation $reservation);
}
