<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;

class ReservationMenu extends Model
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

    public function reservationOptions()
    {
        return $this->hasMany('App\Models\ReservationOption', 'reservation_menu_id', 'id');
    }

    public function reservation()
    {
        return $this->belongsTo('App\Models\Reservation', 'reservation_id', 'id');
    }

    public function menu()
    {
        return $this->hasOne('App\Models\Menu', 'id', 'menu_id')->withTrashed();
    }

    /**
     * テイクアウト予約.
     *
     * @param array info
     * @param Reservation reservation
     * @param array menuInfo
     * @param string errMsg
     *
     * @throws Exception
     * @throws Illuminate\Database\QueryException
     *
     * @return array 予約メニューid
     */
    public function saveTakeout(array $info, Reservation $reservation, array $menuInfo, string &$errMsg): array
    {
        $result = [];
        try {
            $stock = new Stock();
            $menuModel = new Menu();
            $orderInterval = new OrderInterval();
            $menuIdAndCount = [];
            $tmp = [];
            foreach ($info['application']['menus'] as $menu) {
                if (!array_key_exists($menu['menu']['id'], $tmp)) {
                    $tmp[$menu['menu']['id']] = $menu['menu']['count'];
                } else {
                    $tmp[$menu['menu']['id']] = $tmp[$menu['menu']['id']] + $menu['menu']['count'];
                }
            }
            $tmp3 = [];
            foreach ($tmp as $menuId => $count) {
                $tmp2 = ['menu' => []];
                $tmp2['menu']['id'] = $menuId;
                $tmp2['menu']['count'] = $count;
                $tmp3[] = $tmp2;
            }
            $menuIdAndCount = $tmp3;

            foreach ($menuIdAndCount as $menu) {
                if (!$stock->hasStock($info['application']['pickUpDate'], $menu['menu']['id'], $menu['menu']['count'])) {
                    $errMsg = Lang::get('message.stockCheckFailure');
                    throw new \Exception();
                }
            }

            foreach ($info['application']['menus'] as $menu) {
                $reservationMenu = new ReservationMenu();
                $reservationMenu->name = $menuInfo[$menu['menu']['id']]['name'];
                $reservationMenu->count = $menu['menu']['count'];
                $reservationMenu->menu_id = $menu['menu']['id'];
                $reservationMenu->unit_price = $menuInfo[$menu['menu']['id']]['menuPrice']['price'];
                $reservationMenu->price = $reservationMenu->unit_price * $reservationMenu->count;
                $reservationMenu->description = $menuInfo[$menu['menu']['id']]['description'];
                $reservationMenu->reservation_id = $reservation->id;
                $reservationMenu->save();
                $result[] = $reservationMenu->id;
            }

            return $result;
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * レストラン予約.
     *
     * @param array info
     * @param Reservation reservation
     * @param array menuInfo
     *
     * @throws Exception
     * @throws Illuminate\Database\QueryException
     *
     * @return array 予約メニューid
     */
    public function saveRestaurant(array $info, Reservation $reservation, array $menuInfo): array
    {
        $result = [];
        try {
            $menu = $info['application']['menus'][0];

            $reservationMenu = new ReservationMenu();
            $reservationMenu->name = $menuInfo[$menu['menu']['id']]['name'];
            $reservationMenu->count = $info['application']['persons'];
            $reservationMenu->menu_id = $menu['menu']['id'];
            $reservationMenu->unit_price = $menuInfo[$menu['menu']['id']]['menuPrice']['price'];
            $reservationMenu->price = $reservationMenu->unit_price *
            $info['application']['persons'];
            $reservationMenu->description = $menuInfo[$menu['menu']['id']]['description'];
            $reservationMenu->reservation_id = $reservation->id;
            $reservationMenu->save();
            $result[$menu['menu']['id']] = $reservationMenu->id;

            return $result;
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
