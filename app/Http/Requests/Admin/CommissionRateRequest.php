<?php

namespace App\Http\Requests\Admin;

use App\Rules\CommissionRatePolicy;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ApplyTerm;

class CommissionRateRequest extends FormRequest
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
        $rateConstants = config('const.commissionRate');

        return [
            'id' => ['integer'],
            'settlement_company_id' => ['required', 'integer'],
            'apply_term_from_year' => ['required', 'integer', 'between:2021,2099', new ApplyTerm($this->request->all())],
            'apply_term_from_month' => ['required', 'integer', 'between:1,12'],
            'apply_term_to_year' => ['required', 'integer', 'between:2021,2099'],
            'apply_term_to_month' => ['required', 'integer', 'between:1,12'],
            'accounting_condition' => ['required', 'string',
                'in:'.implode(',', array_keys($rateConstants['accounting_condition']))],
            'fee' => ['required', 'regex:/^([1-9]\d*|0)(\.\d)?$/', new CommissionRatePolicy($this->request->all())],
        ];
    }

    public function messages()
    {
        return [
            'fee.regex' => ':attributeは正の整数または小数(小数点以下第1位まで)を入力してください。',
        ];
    }

    public function attributes()
    {
        return [
            'apply_term_from_year' => '適用開始年',
            'apply_term_from_month' => '適用開始月',
            'apply_term_to_year' => '適用終了年',
            'apply_term_to_month' => '適用終了月',
            'accounting_condition' => '計上条件',
            'fee' => '販売手数料',
        ];
    }
}
