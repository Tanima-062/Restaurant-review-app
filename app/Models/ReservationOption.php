<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ReservationOption extends Model
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

    public function option()
    {
        return $this->hasOne('App\Models\Option', 'id', 'option_id');
    }

    /**
     * テイクアウト予約.
     *
     * @param array info
     * @param array reservationMenuIdsWithMenuIdAsKey
     *
     * @throws Exception
     * @throws Illuminate\Database\QueryException
     */
    public function saveTakeout(array $info, array $reservationMenuIdsWithMenuIdAsKey): void
    {
        try {
            foreach ($info['application']['menus'] as $parentKey => $menu) {
                if (!isset($menu['options'])) {
                    continue;
                }
                $optIds = array_column($menu['options'], 'id');
                $options = Option::whereIn('id', $optIds)->get();

                foreach ($options as $key => $option) {
                    $reservationOption = new ReservationOption();
                    $reservationOption->option_cd = $option->option_cd;
                    $reservationOption->keyword_id = $option->keyword_id;
                    $reservationOption->keyword = $option->keyword;
                    $reservationOption->contents_id = $option->contents_id;
                    $reservationOption->contents = $option->contents;
                    $reservationOption->count = $menu['menu']['count'];
                    $reservationOption->unit_price = $option->price;
                    $reservationOption->price = $option->price * $menu['menu']['count'];
                    $reservationOption->reservation_menu_id = $reservationMenuIdsWithMenuIdAsKey[$parentKey];
                    $reservationOption->option_id = $option->id;
                    $reservationOption->created_at = Carbon::now()->toDateTimeString();
                    $reservationOption->save();
                }
            }
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * レストラン予約.
     *
     * @param array info
     * @param array reservationMenuIdsWithMenuIdAsKey
     *
     * @throws Exception
     * @throws Illuminate\Database\QueryException
     */
    public function saveRestaurant(array $info, array $reservationMenuIdsWithMenuIdAsKey): void
    {
        try {
            $menu = $info['application']['menus'][0];
            if (!isset($menu['options'])) {
                return;
            }
            foreach ($menu['options'] as $option) {
                $optionObj = Option::find($option['id']);
                $reservationOption = new ReservationOption();
                $reservationOption->option_cd = $optionObj->option_cd;
                $reservationOption->keyword_id = $optionObj->keyword_id;
                $reservationOption->keyword = $optionObj->keyword;
                $reservationOption->contents_id = $optionObj->contents_id;
                $reservationOption->contents = $optionObj->contents;
                $reservationOption->count = $option['count'];
                $reservationOption->unit_price = $optionObj->price;
                $reservationOption->price = $optionObj->price * $option['count'];
                $reservationOption->reservation_menu_id = $reservationMenuIdsWithMenuIdAsKey[$optionObj->menu_id];
                $reservationOption->option_id = $optionObj->id;
                $reservationOption->created_at = Carbon::now()->toDateTimeString();
                $reservationOption->save();
            }
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
