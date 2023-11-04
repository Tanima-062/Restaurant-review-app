<?php

namespace App\Http\Requests\Admin;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class MenuAddRequest extends FormRequest
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
        return (new MenuEditRequest())->rules();
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->filled(['sales_lunch_start_time', 'sales_lunch_end_time'])) {
                if ($this->input('sales_lunch_start_time') >= $this->input('sales_lunch_end_time')) {
                    $validator->errors()->add('sales_lunch_start_time', '販売開始時間は、販売終了時間より前の時間を指定してください。');
                }
            }

            if ($this->filled(['sales_dinner_start_time', 'sales_dinner_end_time'])) {
                if (!empty($this->input('sales_dinner_start_time') >= $this->input('sales_dinner_end_time'))) {
                    $validator->errors()->add('sales_dinner_start_time', '販売開始時間は、販売終了時間より前の時間を指定してください。');
                }
            }

            // 利用サービスが選択されている場合
            if ($this->filled(['app_cd'])) {
                // 店舗情報の取得
                $store = Store::where('id', $this->input('store_name', (Auth::user())->store_id))->first();
                // 店舗の利用サービスがテイクアウト/レストランではなかった場合＆店舗の利用サービスがinputの利用サービスと違う場合
                if ($store->app_cd !== key(config('code.appCd.tors')) && $store->app_cd !== $this->input('app_cd')) {
                    $validator->errors()->add('app_cd', '店舗情報に設定してある利用サービスのメニューのみ登録可能です。');
                }
            }

            if ($this->input('app_cd') !== key(config('code.appCd.to')) && $this->filled('available_number_of_lower_limit')) {
                // 利用可能下限人数は1~99 利用可能上限人数がある場合は1~利用可能上限人数
                $max = empty($this->input('available_number_of_upper_limit')) ? config('const.menu.availableNumber.upper') : $this->input('available_number_of_upper_limit');
                if ($this->input('available_number_of_lower_limit') > $max) {
                    $msg = sprintf('利用可能下限人数は%s以下で指定してください。', $max);
                    $validator->errors()->add('available_number_of_lower_limit', $msg);
                } elseif ($this->input('available_number_of_lower_limit') < config('const.menu.availableNumber.lower')) {
                    $msg = sprintf('利用可能下限人数は%s以上で指定してください。', config('const.menu.availableNumber.lower'));
                    $validator->errors()->add('available_number_of_lower_limit', $msg);
                }
            }
            if ($this->input('app_cd') !== key(config('code.appCd.to')) && $this->filled('available_number_of_upper_limit')) {
                // 利用可能上限人数は1~99 利用可能下限人数がある場合は利用可能下限人数~99
                $min = empty($this->input('available_number_of_lower_limit')) ? config('const.menu.availableNumber.lower') : $this->input('available_number_of_lower_limit');
                if ($this->input('available_number_of_upper_limit') < $min) {
                    $msg = sprintf('利用可能上限人数は%s以上で指定してください。', $min);
                    $validator->errors()->add('available_number_of_upper_limit', $msg);
                } elseif ($this->input('available_number_of_upper_limit') > config('const.menu.availableNumber.upper')) {
                    $msg = sprintf('利用可能上限人数は%s以下で指定してください。', config('const.menu.availableNumber.upper'));
                    $validator->errors()->add('available_number_of_upper_limit', $msg);
                }
            }

            if ($this->input('app_cd') === key(config('code.appCd.rs'))) {
                // コース品数は数字・ハイフン以外で登録できない
                if ($this->input('number_of_course') !== '-' && !preg_match('/^[0-9]+$/', $this->input('number_of_course'))) {
                    $msg = sprintf('コース品数は数字またはハイフン(-)1つのみで指定してください。');
                    $validator->errors()->add('number_of_course', $msg);
                }
            }

            // 提供可能曜日は1つ以上設定必須
            $providedDayOfWeek = $this->input('provided_day_of_week');
            $isAvarable = false;
            foreach ($providedDayOfWeek as $week) {
                if ($week === '1') {
                    $isAvarable = true;
                }
            }
            if (!$isAvarable) {
                $validator->errors()->add('provided_day_of_week', '提供可能曜日は1つ以上設定してください。');
            }
        });
    }

    public function attributes()
    {
        return (new MenuEditRequest())->attributes();
    }
}
