<?php

namespace App\Http\Requests\Admin;

use App\Rules\MbStringCheck;
use Illuminate\Foundation\Http\FormRequest;

class MenuOptionOkonomiAddRequest extends FormRequest
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
            'required' => 'required',
            'keyword' => ['required', 'string', new MbStringCheck(config('const.menuOptions.keyword.upper'))],
            'contents' => ['required', 'string', new MbStringCheck(config('const.menuOptions.contents.upper'))],
            'price' => 'required|digits_between:1,8',
        ];
    }
}
