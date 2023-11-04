<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StaffSearchRequest extends FormRequest
{

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
            'username' => 'nullable|max:64',
            // 'client_id' => 'nullable|integer|min:1|exists:clients,id',   // clients table not exist
            'client_id' => 'nullable|integer|min:1',
            'staff_authority_id' => 'nullable|integer|min:1|exists:staff_authorities,id',
        ];
    }

    public function attributes()
    {
        return [
            'id' => '担当者ID',
            'name' => 'お名前',
            'username' => 'ログインID',
            'client_id' => '運行会社',
            'staff_authority_id' => 'スタッフ権限',
        ];
    }
}
