<?php

namespace App\Services;

use App\Models\Option;
use App\Models\Price;
use App\Models\Reservation;
use App\Modules\Payment\IFPayment;
use Carbon\Carbon;
use Log;

class PaymentService
{
    private $payment = null;

    public function __construct(IFPayment $payment)
    {
        $this->payment = $payment;
    }

    public function __call($name, $args)
    {
        return $this->payment->$name(...$args);
    }

    public static function sumPrice(array $params) : int
    {
        if (count($params) <= 0) {
            return 0;
        }

        $sum = 0;
        foreach ($params['application']['menus'] as $value) {
            $price = Price::available($value['menu']['id'], $params['application']['pickUpDate'])->first();
            $sum += $price->price * $value['menu']['count'];

            if (isset($value['options'])) {
                $options = Option::whereIn('id', array_column($value['options'], 'id'))->get();
                foreach ($value['options'] as $option) {
                    $sum += $options->firstWhere('id', $option['id'])->price * $value['menu']['count'];
                }
            }
        }
        return $sum;
    }

    public static function sumPriceRestarant(array $params)
    {
        if (count($params) <= 0) {
            return 0;
        }

        $sum = 0;
        foreach ($params['application']['menus'] as $value) {
            $price = Price::available($value['menu']['id'], $params['application']['visitDate'])->first();
            $sum += $price->price * $params['application']['persons'];

            if (isset($value['options'])) {
                $options = Option::whereIn('id', array_column($value['options'], 'id'))->get();
                foreach ($value['options'] as $option) {
                    $sum += $options->firstWhere('id', $option['id'])->price * $option['count'];
                }
            }
        }

        return $sum;
    }

    public static function calcPrice(Reservation $reservation, Carbon $dt, int $persons)
    {
        $newTotal = null;   //再計算後の合計金額
        $menuTotal = null;  //再計算後のメニュー合計金額
        $unitPrice = null;  //メニューの単価

        $reservationMenu = $reservation->reservationMenus[0];

        $unitPrice = $reservationMenu->unit_price;
        $menuTotal = $unitPrice * $persons;

        //オプション料金の追加
        foreach ($reservationMenu->reservationOptions as $reservationOption) {
            $newTotal += $reservationOption->price;
        }
        $newTotal += $menuTotal;

        return [(int)$unitPrice, $menuTotal, $newTotal];

    }
}
