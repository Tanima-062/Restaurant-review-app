<?php

namespace App\Http\Requests\Admin;

// use Composer\DependencyResolver\Request;
use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;

class StoreCancelFeeRequest extends FormRequest
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
    public function rules(Request $request)
    {
        // 来店後が選択されていた場合
        if ($request->visit === config('code.cancelPolicy.visit.after')) {
            $visitValid = 'nullable';
        } else {
            $visitValid = 'required|numeric';
        }
        return [
            'app_cd' => 'required',
            'apply_term_from' => 'required|date_format:Y/m/d',
            'apply_term_to' => 'required|date_format:Y/m/d',
            'visit' => 'required',
            'cancel_limit' => $visitValid,
            'cancel_limit_unit' => 'required',
            'cancel_fee' => 'required|numeric',
            'cancel_fee_unit' => 'required',
            'fraction_unit' => 'required|numeric',
            'fraction_round' => 'required',
            'cancel_fee_max' => 'nullable|numeric',
            'cancel_fee_min' => 'nullable|numeric',
        ];
    }

    public function attributes()
    {
        return [
            'apply_term_from' => '適用開始日',
            'apply_term_to' => '適用終了日',
            'visit' => '来店前/来店後',
            'cancel_limit' => '期限',
            'cancel_limit_unit' => '期限単位',
            'cancel_fee' => 'キャンセル料',
            'cancel_fee_unit' => '計上単位',
            'fraction_unit' => '端数処理',
            'fraction_round' => '端数処理(round)',
            'cancel_fee_max' => '最高額',
            'cancel_fee_min' => '最低額',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->filled(['apply_term_from', 'apply_term_to'])) {
                if ($this->input('apply_term_from') >= $this->input('apply_term_to')) {
                    $validator->errors()->add('apply_term_from', '適用開始日は、適用終了日より前の時間を指定してください。');
                }
            }

            if ($this->filled(['cancel_fee_max', 'cancel_fee_min'])) {
                if ($this->input('cancel_fee_min') >= $this->input('cancel_fee_max')) {
                    $validator->errors()->add('cancel_fee_max', '最高額は最低額より高い金額にしてください。');
                }
            }

            if ($this->input('cancel_fee_unit') == config('code.cancelPolicy.cancel_fee_unit.fixedRate')) {
                if ($this->input('cancel_fee') > 100) {
                    $validator->errors()->add('cancel_fee', '定率の場合は100％以上設定できません。');
                }
            }
        });
    }
}
