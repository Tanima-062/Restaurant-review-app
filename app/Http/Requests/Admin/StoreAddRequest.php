<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddRequest extends FormRequest
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
        return (new StoreEditRequest())->rules();
    }

    public function attributes()
    {
        return (new StoreEditRequest())->attributes();
    }

    /**
     * カスタムバリデーション.
     *
     * @param $validator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // 予算上限が予算下限より下回った場合
            if ($this->filled(['daytime_budget_lower_limit', 'daytime_budget_limit'])) {
                if ($this->input('daytime_budget_lower_limit') >= $this->input('daytime_budget_limit')) {
                    $validator->errors()->add('daytime_budget_lower_limit', '予算上限（昼）は、予算下限（昼）より大きい額を指定してください');
                }
            }
            // 予算上限が予算下限より下回った場合
            if ($this->filled(['night_budget_lower_limit', 'night_budget_limit'])) {
                if ($this->input('night_budget_lower_limit') >= $this->input('night_budget_limit')) {
                    $validator->errors()->add('night_budget_lower_limit', '予算上限（夜）は、予算下限（夜）より大きい額を指定してください');
                }
            }

            // FAXが入力され、FAX通知が未選択の場合
            if ($this->input('fax') !== null && $this->input('use_fax') === null) {
                $validator->errors()->add('use_fax', 'FAXを入力された場合は、FAX通知も選択してください');
            }

            // FAX通知が必要あり、店舗FAX番号が未入力の場合
            if ($this->input('fax') === null && $this->input('use_fax') === '1') {
                $validator->errors()->add('fax', 'FAX通知を「必要あり」に選択された場合は、店舗FAX番号を入力してください');
            }

            // 店舗コードに「store」または、「menu」が含む場合
            $code = \Str::contains($this->input('code'), ['store', 'menu', 'story']);
            if (($code) || $this->input('code') === 'story') {
                $validator->errors()->add('code', '「store」、「menu」が含む店舗コードもしくは、「story」という店舗コードでの登録はできません');
            }

            // 交通手段は上限3行
            if ($this->filled(['access'])) {
                $count = preg_match_all("/\r\n/", $this->input('access'));
                if ($count >= 3) {
                    $validator->errors()->add('access', '交通手段は3行以内で入力して下さい。');
                }
            }

            // 公式アカウントは上限10行
            if ($this->filled(['account'])) {
                $count = preg_match_all("/\r\n/", $this->input('account'));
                if ($count >= 10) {
                    $validator->errors()->add('account', '公式アカウントは10行以内で入力して下さい。');
                }
            }

            // 利用サービスがレストランのみ以外の場合
            if ($this->input('app_cd') !== key(config('code.appCd.rs'))) {
                // テイクアウト受取時間間隔は必須
                if (!$this->filled(['pick_up_time_interval'])) {
                    $validator->errors()->add('pick_up_time_interval', 'テイクアウト受取時間間隔は利用サービスにテイクアウトを含む場合は必須です。');
                }
                if (!$this->filled(['lower_orders_time_hour']) && !$this->filled(['lower_orders_time_minute'])) {
                    $validator->errors()->add('lower_orders_time_hour', '最低注文時間は利用サービスにテイクアウトを含む場合は必須です。');
                }
            }
            // 利用サービスがテイクアウトのみ以外の場合
            if ($this->input('app_cd') !== key(config('code.appCd.to'))) {
                if (!$this->filled(['number_of_seats'])) {
                    $validator->errors()->add('number_of_seats', '座席数は利用サービスにレストランを含む場合は必須です。');
                }
            }
        });
    }
}
