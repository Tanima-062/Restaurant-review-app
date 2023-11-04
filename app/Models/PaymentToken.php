<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentToken extends Model
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function reservation()
    {
        return $this->hasOne('App\Models\Reservation', 'id', 'reservation_id');
    }

    /*
     * トークンからorderCodeを取得する
     *
     * @param  string token
     *
     * @return string orderCode
     */
    public function getOrderCodeFromToken(string $token)
    {
        $paymentToken = PaymentToken::where('token', $token)->first();

        if (is_null($paymentToken)) {
            return null;
        }
        $callBackValues = json_decode($paymentToken->call_back_values, true);

        $orderCode = isset($callBackValues['orderCode']) ? $callBackValues['orderCode'] : '';

        return $orderCode;
    }
}
