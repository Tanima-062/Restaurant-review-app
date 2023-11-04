<?php

namespace App\Models;

use App\Libs\Cipher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

class Reservation extends Model
{
    use Sortable;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'updated_at',
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

    public function reservationMenus()
    {
        return $this->hasMany('App\Models\ReservationMenu', 'reservation_id', 'id');
    }

    public function reservationStore()
    {
        return $this->hasOne('App\Models\ReservationStore', 'reservation_id', 'id');
    }

    public function review()
    {
        return $this->hasOne('App\Models\Review', 'reservation_id', 'id');
    }

    public function paymentDetails()
    {
        return $this->hasMany('App\Models\PaymentDetail', 'reservation_id', 'id');
    }

    public function cancelDetails()
    {
        return $this->hasMany('App\Models\CancelDetail', 'reservation_id', 'id');
    }

    public function getFullNameAttribute()
    {
        return "{$this->last_name} {$this->first_name}";
    }

    public function getReservationNoAttribute()
    {
        return $this->app_cd.$this->id;
    }

    public function getFirstNameAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getLastNameAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getEmailAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getTelAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getRequestAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getIsCloseStrAttribute()
    {
        return ($this->is_close) ? '済' : '未';
    }

    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = Cipher::encrypt($value);
    }

    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = Cipher::encrypt($value);
    }

    public function setTelAttribute($value)
    {
        $this->attributes['tel'] = Cipher::encrypt($value);
    }

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = Cipher::encrypt($value);
    }

    public function setRequestAttribute($value)
    {
        $this->attributes['request'] = Cipher::encrypt($value);
    }

    /**
     * @param $query
     * @param $valid
     *
     * @return mixed
     */
    public static function scopeAdminSearchFilter($query, $valid)
    {
        if (isset($valid['id']) && !empty($valid['id'])) {
            $list = array_filter(preg_split("/[\s\t,\n]/", $valid['id']), 'strlen');
            $query->whereIn('id', array_map('reservation_no_to_id', $list));
        }
        if (isset($valid['reservation_status']) && !empty($valid['reservation_status'])) {
            $query->where('reservation_status', $valid['reservation_status']);
        }
        if (isset($valid['payment_status']) && !empty($valid['payment_status'])) {
            $query->where('payment_status', $valid['payment_status']);
        }
        if (isset($valid['first_name']) && !empty($valid['first_name'])) {
            $query->where('first_name', Cipher::encrypt($valid['first_name']));
        }
        if (isset($valid['last_name']) && !empty($valid['last_name'])) {
            $query->where('last_name', Cipher::encrypt($valid['last_name']));
        }
        if (isset($valid['email']) && !empty($valid['email'])) {
            $query->where('email', Cipher::encrypt($valid['email']));
        }
        if (isset($valid['tel']) && !empty($valid['tel'])) {
            $query->where('tel', Cipher::encrypt($valid['tel']));
        }
        if (isset($valid['created_at_from']) && !empty($valid['created_at_from'])) {
            $query->where('created_at', '>=', $valid['created_at_from']);
        }
        if (isset($valid['created_at_to']) && !empty($valid['created_at_to'])) {
            $query->where('created_at', '<=', $valid['created_at_to']);
        }
        if (isset($valid['pick_up_datetime_from']) && !empty($valid['pick_up_datetime_from'])) {
            $query->where('pick_up_datetime', '>=', $valid['pick_up_datetime_from']);
        }
        if (isset($valid['pick_up_datetime_to']) && !empty($valid['pick_up_datetime_to'])) {
            $query->where('pick_up_datetime', '<=', $valid['pick_up_datetime_to']);
        }
        if (isset($valid['store_name']) && !empty($valid['store_name'])) {
            $storeName = $valid['store_name'];
            $query->whereHas('reservationStore', function ($query) use ($storeName) {
                $query->where('name', 'like', "%$storeName%");
            });
        }
        if (isset($valid['store_tel']) && !empty($valid['store_tel'])) {
            $query->whereHas('reservationStore', function ($query) use ($valid) {
                $query->where('tel', $valid['store_tel']);
            });
        }

        return $query;
    }

    /**
     *   レストラン予約.
     *
     * @param array info
     * @param array menuInfo
     *
     * @return Reservation 予約
     *
     * @throws Exception
     * @throws Illuminate\Database\QueryException
     */
    public function saveRestaurant(array $info, array $menuInfo): Reservation
    {
        try {
            $reservation = new Reservation();
            $reservation->app_cd = key(config('code.appCd.rs'));
            $total = 0;

            $menu = $info['application']['menus'][0];
            $optionTotal = 0;
            $menuTotal = 0;
            if (isset($menu['options'])) {
                foreach ($menu['options'] as $option) {
                    $optionCount = $option['count'];
                    $option = Option::where('id', $option['id'])->where('menu_id', $menu['menu']['id'])->firstOrFail();
                    $optionTotal += $option['price'] * $optionCount;
                }
            }
            $menuTotal = $menuInfo[$menu['menu']['id']]['menuPrice']['price'] * $info['application']['persons'];

            $total = $menuTotal + $optionTotal;
            $reservation->total = $total;
            $reservation->tax = config('restaurant.tax');
            $reservation->reservation_status = config('code.reservationStatus.reserve.key');
            $reservation->payment_status = $total === 0 ? config('code.paymentStatus.unpaid.key') : config('code.paymentStatus.auth.key');
            $reservation->payment_method = $total === 0 ? null : config('const.payment.payment_method.credit');
            $reservation->first_name = Cipher::decrypt($info['customer']['firstName']);
            $reservation->last_name = Cipher::decrypt($info['customer']['lastName']);
            $reservation->email = Cipher::decrypt($info['customer']['email']);
            $reservation->tel = Cipher::decrypt($info['customer']['tel']);
            if (isset($info['customer']['request'])) {
                $reservation->request = Cipher::decrypt($info['customer']['request']);
            }
            $reservation->pick_up_datetime = $info['application']['visitDate'].' '.$info['application']['visitTime'];
            $reservation->administrative_fee = 0;
            $reservation->persons = $info['application']['persons'];
            $reservation->user_agent = request()->header('User-Agent');

            $settlementCompanyId = (Store::find((Menu::find($info['application']['menus'][0]['menu']['id']))->store_id))->settlement_company_id;
            $commissionRate = CommissionRate::searchApplyRecord(
                $reservation->app_cd,
                Carbon::today()->format('Y-m-d H:i:s'),
                $settlementCompanyId)
                ->first();
            $reservation->commission_rate = $commissionRate->fee;
            $reservation->accounting_condition = $commissionRate->accounting_condition;
            $reservation->save();
        } catch (\Throwable $e) {
            throw $e;
        }
        return $reservation;
    }
    /**
     * テイクアウト予約.
     *
     * @param array info
     * @param array menuInfo
     *
     * @return Reservation 予約
     *
     * @throws Exception
     * @throws Illuminate\Database\QueryException
     */
    public function saveTakeout(array $info, array $menuInfo): Reservation
    {
        try {
            $reservation = new Reservation();
            $reservation->app_cd = key(config('code.appCd.to'));
            $total = 0;
            foreach ($info['application']['menus'] as $menu) {
                $optionTotal = 0;
                $menuTotal = 0;
                if (isset($menu['options'])) {
                    foreach ($menu['options'] as $option) {
                        $option = Option::where('id', $option['id'])->where('menu_id', $menu['menu']['id'])->firstOrFail();
                        $optionTotal += $option['price'] * $menu['menu']['count'];
                    }
                }
                $menuTotal = $optionTotal + ($menuInfo[$menu['menu']['id']]['menuPrice']['price'] * $menu['menu']['count']);
                $total += $menuTotal;
            }

            $reservation->total = $total;
            $reservation->tax = config('takeout.tax');
            $reservation->reservation_status = config('code.reservationStatus.reserve.key');
            $reservation->payment_status = config('code.paymentStatus.auth.key');
            $reservation->payment_method = config('const.payment.payment_method.credit');
            $reservation->first_name = Cipher::decrypt($info['customer']['firstName']);
            $reservation->last_name = Cipher::decrypt($info['customer']['lastName']);
            $reservation->email = Cipher::decrypt($info['customer']['email']);
            $reservation->tel = Cipher::decrypt($info['customer']['tel']);
            if (isset($info['customer']['request'])) {
                $reservation->request = Cipher::decrypt($info['customer']['request']);
            }
            $reservation->pick_up_datetime = $info['application']['pickUpDate'].' '.$info['application']['pickUpTime'];
            $reservation->administrative_fee = 0;
            $reservation->persons = 1;
            $reservation->user_agent = request()->header('User-Agent');

            $settlementCompanyId = (Store::find((Menu::find($info['application']['menus'][0]['menu']['id']))->store_id))->settlement_company_id;
            $commissionRate = CommissionRate::searchApplyRecord(
                $reservation->app_cd,
                Carbon::today()->format('Y-m-d H:i:s'),
                $settlementCompanyId)
                ->first();

            $reservation->commission_rate = $commissionRate->fee;
            $reservation->accounting_condition = $commissionRate->accounting_condition;
            $reservation->save();
        } catch (\Throwable $e) {
            throw $e;
        }

        return $reservation;
    }

    /**
     * 予約取得.
     *
     * @param string reservationNo 予約番号
     * @param string tel 電話番号
     *
     * @return Reservation 予約
     *
     * @throws Exception
     * @throws Illuminate\Database\QueryException
     */
    public function getMypage(string $reservationNo, string $tel): ?Reservation
    {
        $reservationId = substr($reservationNo, 2);
        try {
            $query = Reservation::where('id', $reservationId)->where('tel', Cipher::encrypt($tel));
            $reservation = $query->first();
            if (is_null($reservation)) {
                \Log::debug(\Lang::get('message.reservationNotFoundEng').'[reservationNo:'.$reservationNo.']'.'[tel:'.$tel.']');
                return null;
            }
            $reservation->reservationMenus;
            $reservation->reservationStores;
            $reservation->hasReview = is_null($reservation->review) ? false : true;
        } catch (\Throwable $e) {
            \Log::error(
                sprintf('::reservationId=(%s), tel=(%s),error=%s', $reservationId, $tel, $e->getMessage())
            );
            throw $e;
        }

        return $reservation;
    }

    public function startCooking($reservationId)
    {
        $reservation = Reservation::findOrFail($reservationId);
        if ($reservation->reservation_status == config('code.reservationStatus.cancel.key')) {
            throw new \Exception('already cancel');
        }
        $reservation->reservation_status = config('code.reservationStatus.ensure.key');
        $reservation->store_reception_datetime = Carbon::now();
        $reservation->save();
    }

    /**
     * テイクアウトのreservationNo取得.
     *
     * @param int reservationId 予約ID
     *
     * @return string resservationNo
     */
    public function getTakeoutReservationNo($reservationId)
    {
        return config('code.serviceCd.gm').config('code.gmServiceCd.to').$reservationId;
    }

    /**
     * レストランのreservationNo取得.
     *
     * @param int reservationId 予約ID
     *
     * @return string resservationNo
     */
    public function getRestaurantReservationNo($reservationId)
    {
        return config('code.serviceCd.gm').config('code.gmServiceCd.rs').$reservationId;
    }

    /**
     * reservationId取得.
     *
     * @param string reservationNo
     *
     * @return int reservationId
     */
    public function getReservationId($reservationNo)
    {
        $reservationId = str_replace(config('code.serviceCd.gm'), '', $reservationNo);
        $reservationId = str_replace(config('code.gmServiceCd.to'), '', $reservationId);
        $reservationId = ltrim($reservationId, '0');

        return (int) $reservationId;
    }

    public function getDeviceAttribute()
    {
        if (!empty($this->attributes['user_agent'])) {
            if (preg_match('/(iPhone)|(Android)|(Windows Phone)|(BlackBerry)/', $this->attributes['user_agent'])) {
                return 'スマホ';
            } else {
                return 'PC';
            }
        } else {
            return '';
        }
    }

    public static function scopePastReserve($query, $email)
    {
        return $query->where('email', Cipher::encrypt($email));
    }

    public static function scopeStillNotClose($query, string $datetime)
    {
        return $query->where('pick_up_datetime', '<=', $datetime)
            ->where('app_cd', key(config('code.appCd.to')))
            ->whereNull('cancel_datetime')
            ->where('payment_method', config('const.payment.payment_method.credit'))
            ->where('payment_status', config('code.paymentStatus.auth.key'))
            ->where('is_close', 0);
    }

    /**
     * 予約一覧の出し分け.
     *
     * @param $query
     *
     * @return mixed
     */
    public function scopeList($query)
    {
        $store_id = \Auth::user()->store_id;

        if ($store_id === 0) {
            return null;
        }

        $query->whereHas('reservationStore', function ($query) use ($store_id) {
            $query->where('store_id', $store_id);
        });

        return $query;
    }
}
