<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PaymentDetail extends Model
{
    const UPDATED_AT = null;

    protected $guarded = ['id'];

    public function getAccountCodeStrAttribute()
    {
        if (isset(config('const.payment.account_code')[$this->account_code])) {
            return config('const.payment.account_code')[$this->account_code];
        } else {
            return '';
        }
    }

    public function getSumPriceAttribute()
    {
        return $this->price * $this->count;
    }

    public function saveTakeout(array $info, Reservation $reservation, array $menuInfo)
    {
        $insert = [];
        foreach ($info['application']['menus'] as $key => $menu) {
            $insert[] = [
                'reservation_id' => $reservation->id,
                'target_id' => $menu['menu']['id'],
                'account_code' => 'MENU',
                'price' => $menuInfo[$menu['menu']['id']]['menuPrice']['price'],
                'count' => $menu['menu']['count'],
                'remarks' => '自動',
                'created_at' => Carbon::now()->toDateTimeString()
            ];
            if (isset($menu['options'])) {
                $optIds = array_column($menu['options'], 'id');
                $options = Option::whereIn('id', $optIds)->get();
                foreach ($options as $oKey => $option) {
                    $insert[] = [
                        'reservation_id' => $reservation->id,
                        'target_id' => $option->id,
                        'account_code' => $option->option_cd,
                        'price' => $option->price,
                        'count' => $menu['menu']['count'],
                        'remarks' => '自動',
                        'created_at' => Carbon::now()->toDateTimeString()
                    ];
                }
            }
        }

        \DB::table('payment_details')->insert($insert);
    }

    public function saveRestaurant(array $info, Reservation $reservation, array $menuInfo)
    {
        $insert = [];
        $menu = $info['application']['menus'][0];

        $insert[] = [
            'reservation_id' => $reservation->id,
            'target_id' => $menu['menu']['id'],
            'account_code' => 'MENU',
            'price' => $menuInfo[$menu['menu']['id']]['menuPrice']['price'],
            'count' => $info['application']['persons'],
            'remarks' => '自動',
            'created_at' => Carbon::now()->toDateTimeString()
        ];
        if (isset($menu['options'])) {
            foreach ($menu['options'] as $option) {
                $optionObj = Option::find($option['id']);
                $insert[] = [
                    'reservation_id' => $reservation->id,
                    'target_id' => $optionObj->id,
                    'account_code' => $optionObj->option_cd,
                    'price' => $optionObj->price,
                    'count' => $option['count'],
                    'remarks' => '自動',
                    'created_at' => Carbon::now()->toDateTimeString()
                ];
            }
        }

        \DB::table('payment_details')->insert($insert);
    }
}
