<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MenuImageEditRequest extends FormRequest
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
            'menu.*.image_cd' => 'required',
            'menu.*.image_path' => 'nullable|mimes:png,jpg,jpeg|max:8192',
            'menu.*.weight' => 'nullable|numeric|between:0,10',
        ];
    }
}
