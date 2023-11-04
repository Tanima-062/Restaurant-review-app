<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreImageEditRequest extends FormRequest
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
            'storeImage.*.image_cd' => 'required',
            'storeImage.*.image_path' => 'nullable|mimes:png,jpg,jpeg|max:8192',
            'storeImage.*.weight' => 'nullable|numeric|between:0,10',
        ];
    }
}
