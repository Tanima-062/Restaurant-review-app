<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReservationStore extends Model
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

    public function store()
    {
        return $this->hasOne('App\Models\Store', 'id', 'store_id')->withTrashed();
    }

    public function reservation()
    {
        return $this->belongsTo('App\Models\Reservation', 'reservation_id', 'id');
    }

    public function cancelFeesPublished()
    {
        return $this->hasMany('App\Models\CancelFee', 'store_id', 'store_id')->where('published', 1);
    }

    /**
     * テイクアウト予約.
     *
     * @param array info
     * @param Reservation reservation
     * @param array menuInfo
     *
     * @throws Exception
     * @throws Illuminate\Database\QueryException
     *
     * @return int 予約店舗ID
     */
    public function saveTakeout(array $info, Reservation $reservation, array $menuInfo): int
    {
        try {
            $insert = [];
            foreach ($info['application']['menus'] as $menu) {
                $insert['name'] = $menuInfo[$menu['menu']['id']]['store']['name'];
                $address = $menuInfo[$menu['menu']['id']]['store']['address_1'].
                $menuInfo[$menu['menu']['id']]['store']['address_2'].
                $menuInfo[$menu['menu']['id']]['store']['address_3'];
                $insert['address'] = $address;
                $insert['tel'] = $menuInfo[$menu['menu']['id']]['store']['tel'];
                $insert['email'] = $menuInfo[$menu['menu']['id']]['store']['email_1'];
                $insert['latitude'] = $menuInfo[$menu['menu']['id']]['store']['latitude'];
                $insert['longitude'] = $menuInfo[$menu['menu']['id']]['store']['longitude'];
                $insert['reservation_id'] = $reservation->id;
                $insert['store_id'] = $menuInfo[$menu['menu']['id']]['store_id'];
                $insert['created_at'] = Carbon::now()->toDateTimeString();
            }

            return DB::table('reservation_stores')->insertGetId($insert);
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
     * @return int 予約店舗ID
     */
    public function saveRestaurant(array $info, Reservation $reservation, array $menuInfo): int
    {
        try {
            $insert = [];
            $menu = $info['application']['menus'][0];

            $insert['name'] = $menuInfo[$menu['menu']['id']]['store']['name'];
            $address = $menuInfo[$menu['menu']['id']]['store']['address_1'].
            $menuInfo[$menu['menu']['id']]['store']['address_2'].
            $menuInfo[$menu['menu']['id']]['store']['address_3'];
            $insert['address'] = $address;
            $insert['tel'] = $menuInfo[$menu['menu']['id']]['store']['tel'];
            $insert['email'] = $menuInfo[$menu['menu']['id']]['store']['email_1'];
            $insert['latitude'] = $menuInfo[$menu['menu']['id']]['store']['latitude'];
            $insert['longitude'] = $menuInfo[$menu['menu']['id']]['store']['longitude'];
            $insert['reservation_id'] = $reservation->id;
            $insert['store_id'] = $menuInfo[$menu['menu']['id']]['store_id'];
            $insert['created_at'] = Carbon::now()->toDateTimeString();

            return DB::table('reservation_stores')->insertGetId($insert);
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
