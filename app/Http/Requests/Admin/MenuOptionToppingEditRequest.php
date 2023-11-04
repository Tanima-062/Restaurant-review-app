<?php

namespace App\Http\Requests\Admin;

use App\Rules\MbStringCheck;
use Illuminate\Foundation\Http\FormRequest;

class MenuOptionToppingEditRequest extends FormRequest
{
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
            'menuOptionTopping.*.contents' => ['required', 'string', new MbStringCheck(config('const.menuOptions.contents.upper'))],
            'menuOptionTopping.*.price' => 'required|digits_between:1,8',
        ];
    }

    public function attributes()
    {
        return [
            'menuOptionTopping.*.contents' => 'トッピング内容',
            'menuOptionTopping.*.price' => 'トッピング金額（税込）',
        ];
    }
}
