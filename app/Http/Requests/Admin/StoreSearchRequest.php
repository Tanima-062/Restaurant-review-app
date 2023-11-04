<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSearchRequest extends FormRequest
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
            'id' => 'nullable|integer|min:1',
            'name' => 'nullable|max:64',
            'code' => 'nullable|max:64',
            'settlement_company_name' => 'nullable|max:64',
        ];
    }

    public function attributes()
    {
        return [
            'id' => 'ID',
            'name' => '店舗名',
            'code' => '店舗コード',
            'settlement_company_name' => '精算会社名',
        ];
    }
}
