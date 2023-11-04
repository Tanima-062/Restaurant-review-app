<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

trait StoreEditRequestTrait
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|max:30',
        ];
    }

    public function attributes()
    {
        return [
            'name' => '店舗名',
        ];
    }

    public function messages()
    {

    }
}
