<?php

namespace App\Http\Requests\Admin;

use App\Models\Image;
use App\Models\Menu;
use App\Models\Store;
use App\Rules\MbStringCheck;
use Illuminate\Foundation\Http\FormRequest;

class StoreEditRequest extends FormRequest
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
            'app_cd' => 'required|string',
            'settlement_company_id' => \Gate::check('client-only') ? 'nullable' : 'required|max:10',
            'name' => ['required', 'max:128', 'regex:/^[^　]+$/u'],
            'alias_name' => ['nullable', 'string', 'max:128', 'regex:/^[^　]+$/u'],
            'code' => ['required', 'regex:/^([a-z0-9\-_]+|[_])$/', 'max:64', 'unique:stores,code,'.$this->id.',id,deleted_at,NULL'],
            'tel' => 'required|regex:/^(0{1}\d{1,4}-{0,1}\d{1,4}-{0,1}\d{4})$/|max:13|min:10',
            'tel_order' => 'nullable|regex:/^(0{1}\d{1,4}-{0,1}\d{1,4}-{0,1}\d{4})$/|max:13|min:10',
            'mobile_phone' => 'nullable|regex:/^(0{1}\d{1,4}-{0,1}\d{1,4}-{0,1}\d{4})$/|max:13|min:10',
            'fax' => 'nullable|regex:/^(0{1}\d{1,4}-{0,1}\d{1,4}-{0,1}\d{4})$/|max:13|min:10',
            'postal_code' => ['required', 'regex:/^(00[1-9]|0[1-9][0-9]|[1-9][0-9]{2})[0-9]{4}$/u'],
            'address_1' => ['required', 'regex:/^[一-龠]+$/'],
            'address_2' => ['required', 'regex:/^[一-龠ぁ-んァ-ヶ]+$/'],
            'address_3' => ['required', 'string'],
            'email_1' => ['required', 'regex:/^([a-z|A-Z|0-9]){1}([a-zA-Z0-9-._])*[@]+([a-zA-Z0-9-._])+$/u'],
            'email_2' => ['nullable', 'regex:/^([a-z|A-Z|0-9]){1}([a-zA-Z0-9-._])*[@]+([a-zA-Z0-9-._])+$/u'],
            'email_3' => ['nullable', 'regex:/^([a-z|A-Z|0-9]){1}([a-zA-Z0-9-._])*[@]+([a-zA-Z0-9-._])+$/u'],
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'regular_holiday' => 'required',
            'can_card' => 'required',
            'card_types' => 'required_if:can_card, 1',
            'can_digital_money' => 'required',
            'digital_money_types' => 'required_if:can_digital_money, 1',
            'has_private_room' => 'required',
            'private_room_types' => 'required_if:has_private_room, 1',
            'has_parking' => 'required',
            'has_coin_parking' => 'required',
            'number_of_seats' => 'nullable|integer',
            'can_charter' => 'required',
            'charter_types' => 'required_if:can_charter, 1',
            'smoking_types' => 'required',
            'lower_orders_time_hour' => 'nullable|integer',
            'lower_orders_time_minute' => 'nullable|integer',
            //'station_id' => 'required',
            'remarks' => ['nullable', new MbStringCheck(config('const.store.remarks.upper'))],
            'description' => ['nullable', new MbStringCheck(config('const.store.description.upper'))],
            'area_id' => 'required|integer',
        ];
    }

    public function attributes()
    {
        return [
            'app_cd' => '利用サービス',
            'settlement_company_id' => '精算会社名',
            'name' => '店舗名',
            'alias_name' => '店舗別名',
            'code' => '店舗コード',
            'tel' => '店舗電話番号',
            'tel_order' => '予約用電話番号',
            'mobile_phone' => '携帯番号',
            'fax' => '店舗FAX番号',
            'use_fax' => 'FAX通知',
            'postal_code' => '郵便番号',
            'address_1' => '住所1（都道府県）',
            'address_2' => '住所2（市区町村）',
            'address_3' => '住所3（残り）',
            'latitude' => '緯度',
            'longitude' => '経度',
            'email_1' => '予約受付時お知らせメールアドレス1',
            'email_2' => '予約受付時お知らせメールアドレス2',
            'email_3' => '予約受付時お知らせメールアドレス3',
            'regular_holiday' => '定休日',
            'can_card' => 'カード',
            'card_types' => 'カード種類',
            'can_digital_money' => '電子マネー',
            'digital_money_types' => '電子マネー種類',
            'can_charter' => '貸切',
            'charter_types' => '貸切種類',
            'smoking_types' => '喫煙・禁煙',
            'has_private_room' => '個室',
            'private_room_types' => '個室種類',
            'has_parking' => '駐車場',
            'has_coin_parking' => '近隣にコインパーキング',
            'lower_orders_time_hour' => '最低注文時間(時間)',
            'lower_orders_time_minute' => '最低注文時間(分)',
            'remarks' => '備考',
            'description' => '説明',
            'number_of_seats' => ' 座席数',
            'area_id' => '検索エリア設定',
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
            $code = \Str::contains($this->input('code'), ['store', 'menu']);
            if (($code) || $this->input('code') === 'story') {
                $validator->errors()->add('code', '「store」、「menu」が含む店舗コードもしくは、「story」という店舗コードでの登録はできません');
            }

            // 利用サービスが入力されている＆入力された利用サービスがテイクアウト/レストラン以外の場合
            if ($this->filled(['app_cd']) && $this->input('app_cd') !== key(config('code.appCd.tors'))) {
                // 店舗に紐付いたメニューの利用サービスを重複なしで取得(TO or RS)
                $storeMenus = Menu::where('store_id', $this->id)->distinct()->pluck('app_cd');
                foreach ($storeMenus as $storeMenu) {
                    // 入力された利用サービス以外のメニューが店舗に登録されている＆入力された利用サービスがテイクアウトの場合
                    if ($this->input('app_cd') !== $storeMenu && $this->input('app_cd') === key(config('code.appCd.to'))) {
                        $validator->errors()->add('app_cd', 'すでにレストランのメニューが登録されています。利用サービスをテイクアウトのみに変更する際は、レストランメニューを削除してください。');
                    }
                    // 入力された利用サービス以外のメニューが店舗に登録されている＆入力された利用サービスがレストランの場合
                    if ($this->input('app_cd') !== $storeMenu && $this->input('app_cd') === key(config('code.appCd.rs'))) {
                        $validator->errors()->add('app_cd', 'すでにテイクアウトのメニューが登録されています。利用サービスをレストランのみに変更する際は、テイクアウトメニューを削除してください。');
                    }
                }
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

                // テイクアウト受取時間間隔は必須
                if (!$this->filled(['price_level'])) {
                    $validator->errors()->add('price_level', 'テイクアウト価格帯は利用サービスにテイクアウトを含む場合は必須です。');
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

            // 店舗名の取得
            $store = Store::select('name', 'published')->where('id', $this->id)->first();
            // 公開するにチェックが入っている場合
            if ($store->published === 1) {
                // エラーメッセージ格納用
                $errors = [];

                // 画像の取得
                $takeoutImage = Image::where('store_id', $this->id)->where('image_cd', config('code.imageCd.foodLogo'))->first();
                $restaurantImage = Image::where('store_id', $this->id)->where('image_cd', config('code.imageCd.restaurantLogo'))->first();

                // 店舗の利用サービスがテイクアウト/レストランの場合　または、店舗の利用サービスがテイクアウトの場合
                if ($this->input('app_cd') === key(config('code.appCd.tors')) || $this->input('app_cd') === key(config('code.appCd.to'))) {
                    // テイクアウト用の画像がない場合
                    if (!$takeoutImage) {
                        $errors[] = '利用サービスにて'.config('code.appCd.to.TO').'を設定している場合は、画像設定から先に'.config('const.storeImage.image_cd.FOOD_LOGO').'を設定してください。';
                    }
                }

                // 店舗の利用サービスがテイクアウト/レストランの場合　または、店舗の利用サービスがレストランの場合
                if ($this->input('app_cd') === key(config('code.appCd.tors')) || $this->input('app_cd') === key(config('code.appCd.rs'))) {
                    // レストラン用の画像がない場合
                    if (!$restaurantImage) {
                        $errors[] = '利用サービスにて'.config('code.appCd.rs.RS').'を設定している場合は、画像設定から先に'.config('const.storeImage.image_cd.RESTAURANT_LOGO').'を設定してください。';
                    }
                }

                // 店舗の利用サービスがレストラン/テイクアウト　または、テイクアウトの場合
                if ($this->input('app_cd') === key(config('code.appCd.to')) || $this->input('app_cd') === key(config('code.appCd.tors'))) {
                    if (!$this->input('price_level')) {
                        $errors[] = '店舗のテイクアウト価格帯を設定してください。';
                    }
                    if (!$this->input('pick_up_time_interval')) {
                        $errors[] = '店舗のテイクアウト受取時間間隔を設定してください。';
                    }
                    if (!$this->input('lower_orders_time_hour') && !$this->input('lower_orders_time_minute')) {
                        $errors[] = '店舗の最低注文時間を設定してください。';
                    }
                }

                // 検索エリアが設定されていない場合
                if (!$this->input('area_id')) {
                    $errors[] = '店舗の検索エリアの設定をしてください。';
                }

                // エラーがあった場合にエラーメッセージを表示する
                if ($errors) {
                    foreach ($errors as $error) {
                        $validator->errors()->add('published', '店舗「'.$store->name.'」を正しく公開するには下記の設定をするか、非公開にしてから変更してください。');
                        $validator->errors()->add('published', $error);
                    }
                }
            }
        });
    }
}
