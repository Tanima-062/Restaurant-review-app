<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MenuPriceRequest extends FormRequest
{
    /**
     * @var mixed
     */


    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'menu.*.price_cd' => 'required',
            'menu.*.price_start_date' => 'required|date',
            'menu.*.price_end_date' => 'required|date|after_or_equal:menu.*.price_start_date',
            'menu.*.price' => 'required|regex:/^[0-9]{1,8}+$/',
        ];
    }

    /**
     * Validator取得
     * return Illuminate\Contracts\Validation\Validator $validator
     */
    public function getValidator()
    {
        return $this->validator;
    }
}
