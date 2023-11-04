<?php

namespace App\Http\Requests\Admin;

use App\Rules\MbStringCheck;
use Illuminate\Foundation\Http\FormRequest;

class MenuOptionOkonomiEditRequest extends FormRequest
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
            'menuOkonomi.0.required' => 'required',
            'menuOkonomi.0.keyword' => ['required', 'string', new MbStringCheck(config('const.menuOptions.keyword.upper'))],
            'menuOption.*.contents' => ['required', 'string', new MbStringCheck(config('const.menuOptions.contents.upper'))],
            'menuOption.*.price' => 'required|digits_between:1,8',
        ];
    }
}
