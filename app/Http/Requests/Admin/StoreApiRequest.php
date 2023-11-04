<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreApiRequest extends FormRequest
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
            'api_cd' => 'required',
            'api_store_id' => 'required|numeric',
        ];
    }

    public function attributes()
    {
        return [
            'api_cd' => '接続先',
            'api_store_id' => '接続先のID',
        ];
    }
}
