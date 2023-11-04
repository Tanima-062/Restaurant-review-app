<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StaffRequest extends FormRequest
{
    use StaffDetailRequestTrait, StaffPasswordRequestTrait {
        StaffDetailRequestTrait::rules as rulesStaffDetailRequest;
        StaffDetailRequestTrait::attributes as attributesStaffDetailRequest;
        StaffPasswordRequestTrait::rules as rulesStaffPasswordRequest;
        StaffPasswordRequestTrait::attributes as attributesStaffPasswordRequest;
    }

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
        // TODO: validationルールを細かく
        $rulesDetails = $this->rulesStaffDetailRequest();
        $rulesPasswords = $this->rulesStaffPasswordRequest();
        $rules = [
            'username' => 'required|max:64|unique:staff,username|'.
                'regex:/^[a-zA-Z0-9-_]+$/',
            'staff_authority_id' => 'required|integer|exists:staff_authorities,id',
            'settlement_company_id' => 'nullable|integer',
            'store_id' => 'nullable|integer',
        ];

        return array_merge($rulesDetails, $rulesPasswords, $rules);
    }

    public function attributes()
    {
        $attributesDetails = $this->attributesStaffDetailRequest();
        $attributesPasswords = $this->attributesStaffPasswordRequest();
        $attributes = [
            'username' => 'ログインID',
            'staff_authority_id' => '権限',
        ];

        return array_merge($attributesDetails, $attributesPasswords, $attributes);
    }

    public function messages()
    {
        return [
            'username.regex' => 'ログインIDは半角英数字で入力してください'
        ];
    }

    /**
     * カスタムバリデーション.
     *
     * @param $validator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $authority = config('const.staff.authority');

            if ((int) $this->input('staff_authority_id') === $authority['CLIENT_ADMINISTRATOR'] || (int) $this->input('staff_authority_id') === $authority['CLIENT_GENERAL']) {
                // 精算会社は社内ユーザ操作時のみ入力できる
                if(Auth::user()->staff_authority_id <= config('const.staff.authority.IN_HOUSE_GENERAL')){
                    if (empty($this->input('settlement_company_id'))) {
                        $validator->errors()->add('settlement_company_id', '精算会社はクライアント管理者もしくはクライアント一般の場合は必須です。');
                    }
                }
                if (empty($this->input('store_id'))) {
                    $validator->errors()->add('store_id', '店舗はクライアント管理者もしくはクライアント一般の場合は必須です。');
                }
            }
        });
    }
}
