<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class AlphaNumDashUscore implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        return preg_match('/^[a-zA-Z0-9-_]+$/', $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return ":attributeは、半角英数字と半角ハイフン(-)及び半角下線(_)がご利用できます。";
    }
}
