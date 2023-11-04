<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreOpeningHourEditRequest extends FormRequest
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
            'store.*.opening_hour_cd' => 'required',
            'store.*.start_at' => ['required', 'regex:/\A([01][0-9]|2[0-3]):[0-5][0-9]\Z/'],
            'store.*.end_at' => ['required', 'regex:/\A([01][0-9]|2[0-3]):[0-5][0-9]\Z/'],
            'store.*.last_order_time' => 'required',
        ];
    }

    public function attributes()
    {
        return [
            'store.*.opening_hour_cd' => '営業時間コード',
            'store.*.start_at' => '営業開始時間',
            'store.*.end_at' => '営業終了時間',
            'store.*.last_order_time' => 'ラストオーダー時間',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->filled(['store.*.start_at', 'store.*.end_at'])) {
                if ($this->input('store.*.start_at') >= $this->input('store.*.end_at')) {
                    $validator->errors()->add('sales_lunch_start_time', '営業開始時間は、営業終了時間より前の時間を指定してください');
                }
            }

            // ラストオーダーは営業開始時間と終了時間の間で設定
            // (正常に動いていなかったので修正)
            // if ($this->input('last_order_time')) {
            //     $lastOrderTime = new Carbon($this->input('last_order_time'));
            //     $start = new Carbon($this->input('start_at'));
            //     $end = new Carbon($this->input('end_at'));

            //     if (!$lastOrderTime->between($start, $end)) {
            //         $validator->errors()->add('last_order_time', 'ラストオーダーは営業開始時間と終了時間の間で設定してください。');
            //     }
            // }
            $data = $this->input();
            foreach ($data['store'] as $key => $v) {
                // 対象項目にエラーがない場合
                if (
                    !($validator->errors()->has("store.{$key}.last_order_time")) &&
                    !($validator->errors()->has("store.{$key}.start_at")) &&
                    !($validator->errors()->has("store.{$key}.end_at"))
                ) {
                    if ($v['last_order_time']) {
                        $lastOrderTime = new Carbon($v['last_order_time']);
                        $start = new Carbon($v['start_at']);
                        $end = new Carbon($v['end_at']);

                        if (!$lastOrderTime->between($start, $end)) {
                            $validator->errors()->add("store.{$key}.last_order_time", 'ラストオーダーは営業開始時間と終了時間の間で設定してください。');
                        }
                    }
                }
            }
        });
    }
}
