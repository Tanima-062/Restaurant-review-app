<?php

namespace App\Libs;

class ConsumptionTax
{
    /**
     * @param string $date format : Ym
     * @return int
     */
    public static function calc(string $date) : int
    {
        $taxList = config('const.payment.tax');

        foreach ($taxList as $tax) {
            if (!empty($tax['start']) && !empty($tax['end']) && $tax['start'] <= $date && $date <= $tax['end']) {
                return $tax['rate'];
            } elseif (empty($tax['start']) && $date <= $tax['end']) {
                return $tax['rate'];
            } elseif (empty($tax['end']) && $tax['start'] <= $date) {
                return $tax['rate'];
            }
        }

        return 0;
    }
}
