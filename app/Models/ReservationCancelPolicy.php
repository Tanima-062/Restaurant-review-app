<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationCancelPolicy extends Model
{
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * テイクアウト予約.
     *
     * @param int reservationStoreId
     *
     * @return ReservationCancelPolicy 予約キャンセルポリシー
     *
     * @throws Exception
     * @throws Illuminate\Database\QueryException
     */
    public function saveTakeout(int $reservationStoreId): ReservationCancelPolicy
    {
        try {
            $reservationCancelPolicy = new ReservationCancelPolicy();
            $reservationCancelPolicy->reservation_store_id = $reservationStoreId;
            $reservationCancelPolicy->save();
        } catch (\Throwable $e) {
            throw $e;
        }

        return $reservationCancelPolicy;
    }

    /**
     * レストラン予約.
     *
     * @param int reservationStoreId
     *
     * @return ReservationCancelPolicy 予約キャンセルポリシー
     *
     * @throws Exception
     * @throws Illuminate\Database\QueryException
     */
    public function saveRestaurant(int $reservationStoreId): ReservationCancelPolicy
    {
        try {
            $reservationCancelPolicy = new ReservationCancelPolicy();
            $reservationCancelPolicy->reservation_store_id = $reservationStoreId;
            $reservationCancelPolicy->save();
        } catch (\Throwable $e) {
            throw $e;
        }

        return $reservationCancelPolicy;
    }
}
