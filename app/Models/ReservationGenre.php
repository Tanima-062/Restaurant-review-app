<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationGenre extends Model
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
     * @param array info
     * @param array reservationMenuIds
     * @param int reservationStoreId
     * @param array menuInfo
     *
     * @throws Exception
     * @throws Illuminate\Database\QueryException
     */
    public function saveTakeout(array $info, array $reservationMenuIdsWithMenuIdAsKey, int $reservationStoreId, array $menuInfo): void
    {
        try {
            foreach ($info['application']['menus'] as $parentKey => $val) {
                foreach ($menuInfo[$val['menu']['id']]['genres'] as $genre) {
                    $reservationGenre = new ReservationGenre();
                    $reservationGenre->name = $genre['name'];
                    $reservationGenre->reservation_store_id = $reservationStoreId;
                    $reservationGenre->reservation_menu_id = $reservationMenuIdsWithMenuIdAsKey[$parentKey];
                    $reservationGenre->save();
                }
            }
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * レストラン予約
     *
     * @param array info
     * @param array reservationMenuIds
     * @param int reservationStoreId
     * @param array menuInfo
     *
     * @throws Exception
     * @throws Illuminate\Database\QueryException
     */
    public function saveRestaurant(array $info, array $reservationMenuIdsWithMenuIdAsKey, int $reservationStoreId, array $menuInfo): void
    {
        try {
            $val = $info['application']['menus'][0];
            foreach ($menuInfo[$val['menu']['id']]['genres'] as $genre) {
                $reservationGenre = new ReservationGenre();
                $reservationGenre->name = $genre['name'];
                $reservationGenre->reservation_store_id = $reservationStoreId;
                $reservationGenre->reservation_menu_id = $reservationMenuIdsWithMenuIdAsKey[$val['menu']['id']];
                $reservationGenre->save();
            }
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
