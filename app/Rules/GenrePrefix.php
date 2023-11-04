<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

class GenrePrefix implements Rule
{
    private $attributes;
    private $prefix;

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
        $data = $this->attributes;
        if (!empty($data['small_genre']) && !Str::startsWith($value, 'i-')) {
            $this->prefix = 'i-';
            return false;
        } elseif (!empty($data['middle_genre']) && empty($data['small_genre']) && !Str::startsWith($value, 's-')) {
            $this->prefix = 's-';
            return false;
        } elseif (!empty($data['big_genre']) && empty($data['middle_genre']) && !Str::startsWith($value, 'm-')) {
            $this->prefix = 'm-';
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
        return '先頭にプレフィックスとして「'.$this->prefix.'」をつけてください';
    }
}
