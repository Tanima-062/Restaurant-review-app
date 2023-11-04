<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StaffDetailRequest extends FormRequest
{
    use StaffDetailRequestTrait;

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
