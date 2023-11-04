<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ApplyTerm implements Rule
{
    private $attributes;

    /**
     * Create a new rule instance.
     * @param $attributes
     * @return void
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
        $params = $this->attributes;
        if (isset($params['apply_term_from_year']) && isset($params['apply_term_from_month']) &&
            isset($params['apply_term_to_year']) && isset($params['apply_term_to_month'])) {
            $from = sprintf('%04d%02d', $params['apply_term_from_year'], $params['apply_term_from_month']);
            $to = sprintf('%04d%02d', $params['apply_term_to_year'], $params['apply_term_to_month']);
            return ((int)$from <= (int)$to);
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
        return '適用期間の終了年月は開始年月以降を設定してください。';
    }
}
