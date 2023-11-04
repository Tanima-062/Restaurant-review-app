<?php

namespace App\Rules;

use App\Models\CommissionRate;
use Illuminate\Contracts\Validation\Rule;
use Log;

class CommissionRatePolicy implements Rule
{
    private $attributes;

    /**
     * CommissionRatePolicy constructor.
     * @param $attributes
     */
    public function __construct($attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $data = $this->attributes;
        // 指定した期間が含まれるレコードを抽出
        $rates = CommissionRate::policyCheckFilter($data)->get();

        if ($rates->count() > 0) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'ポリシーが重複しています。';
    }
}
