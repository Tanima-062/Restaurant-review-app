<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class SettlementCompanyRequest extends FormRequest
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
        $const = config('const.settlement');

        return [
            'name' => ['required', "regex:/^[一-龠|ァ-ヶー|ぁ-ん|ｱ-ﾝﾞﾟ|a-z|A-Z|0-9| &＆’，‐．・']+$/u"],
            'postal_code' => ['nullable', 'regex:/^(00[1-9]|0[1-9][0-9]|[1-9][0-9]{2})[0-9]{4}$/u'],
            'tel' => ['required', 'regex:/^([0-9]*)$/','max:11', 'min:10'],
            'address' => ['required', 'string', 'max:256'],
            'payment_cycle' => ['required', Rule::in(Arr::pluck($const['payment_cycle'], 'value'))],
            'result_base_amount' => ['required', Rule::in(Arr::pluck($const['result_base_amount'], 'value'))],
            'tax_calculation' => ['required', Rule::in(Arr::pluck($const['tax_calculation'], 'value'))],
            'bank_name' => ['required', 'string', 'max:128'],
            'branch_name' => ['required', 'string', 'max:128'],
            'branch_number' => ['required', 'regex:/^[0-9]{3}|-/u'],
            'account_type' => ['required', Rule::in(Arr::pluck($const['account_type'], 'value'))],
            'account_number' => ['required', 'regex:/^[0-9-]*$/', 'max:15'],
            'account_name_kana' => ['required', 'regex:/^(?!.*(ァ|ィ|ゥ|ェ|ォ|ッ|ャ|ュ|ョ|ヮ|ヵ|ヶ|ヲ))[（）．－Ａ-Ｚ０-９ァ-ヾ　]+$|^-$/u', 'max:128'],
            'billing_email_1' => ['required', 'regex:/^([a-z|A-Z|0-9]){1}([a-zA-Z0-9-._])*[@]+([a-zA-Z0-9-._])+$/u'],
            'billing_email_2' => ['nullable', 'regex:/^([a-z|A-Z|0-9]){1}([a-zA-Z0-9-._])*[@]+([a-zA-Z0-9-._])+$/u'],
        ];
    }

    public function attributes()
    {
        return [
            'name' => '会社名',
            'tel' => '電話番号',
            'postal_code' => '郵便番号',
            'address' => '住所',
            'bank_name' => '銀行名',
            'branch_name' => '支店名',
            'branch_number' => '支店番号',
            'account_number' => '口座番号',
            'account_name_kana' => '口座名義カナ',
            'billing_email_1' => '通知書送付先メールアドレス1',
            'billing_email_2' => '通知書送付先メールアドレス2',
        ];
    }
}
