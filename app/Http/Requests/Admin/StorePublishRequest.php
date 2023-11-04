<?php

namespace App\Http\Requests\Admin;

use App\Models\CancelFee;
use App\Models\CommissionRate;
use App\Models\GenreGroup;
use App\Models\Image;
use App\Models\Menu;
use App\Models\OpeningHour;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StorePublishRequest extends FormRequest
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
            'published' => 'required|integer|between:1,1',
        ];
    }

    /**
     * バリデータインスタンスの設定.
     *
     * @param \Illuminate\Validation\Validator $validator
     *
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // エラーメッセージ格納用
            $errors = [];

            // 店舗情報取得
            $store = Store::find($this->id);

            // 店舗に紐付いたapp_cd(利用サービス)を取得
            $app_cd = $store->app_cd;

            // 店舗の利用サービスがテイクアウトの場合
            if ($app_cd === key(config('code.appCd.to')) || $app_cd === key(config('code.appCd.tors'))) {
                if (!$store->price_level) {
                    $errors[] = '店舗のテイクアウト価格帯を設定してください。';
                }
                if (!$store->pick_up_time_interval) {
                    $errors[] = '店舗のテイクアウト受取時間間隔を設定してください。';
                }
                if (!$store->lower_orders_time) {
                    $errors[] = '店舗の最低注文時間(分)を設定してください。';
                }
            }

            // 検索エリアが設定されていない場合
            if (!$store->area_id) {
                $errors[] = '店舗の検索エリアの設定をしてください。';
            }

            // 営業時間情報を取得
            $openingHours = OpeningHour::where('store_id', $this->id)->get();

            // 営業時間の設定がない場合
            if (!count($openingHours) >= 1) {
                $errors[] = '営業時間を1つ以上登録してください。';
            }

            // 画像の取得
            $takeoutImage = Image::where('store_id', $this->id)->where('image_cd', config('code.imageCd.foodLogo'))->first();
            $restaurantImage = Image::where('store_id', $this->id)->where('image_cd', config('code.imageCd.restaurantLogo'))->first();

            // 店舗の利用サービスがテイクアウト/レストランの場合　または、店舗の利用サービスがテイクアウトの場合
            if ($app_cd === key(config('code.appCd.tors')) || $app_cd === key(config('code.appCd.to'))) {
                // テイクアウト用の画像がない場合
                if (!$takeoutImage) {
                    $errors[] = '利用サービスにて'.config('code.appCd.to.TO').'を設定している場合は、'.config('const.storeImage.image_cd.FOOD_LOGO').'を設定してください。';
                }
            }

            // 店舗の利用サービスがテイクアウト/レストランの場合　または、店舗の利用サービスがレストランの場合
            if ($app_cd === key(config('code.appCd.tors')) || $app_cd === key(config('code.appCd.rs'))) {
                // レストラン用の画像がない場合
                if (!$restaurantImage) {
                    $errors[] = '利用サービスにて'.config('code.appCd.rs.RS').'を設定している場合は、'.config('const.storeImage.image_cd.RESTAURANT_LOGO').'を設定してください。';
                }
            }

            // 優先度が1以上の画像が最低3つあること
            $storeId = $this->id;
            $q = Image::query();
            $q->where(function ($q) use ($storeId) {
                $q->where('store_id', $storeId)->where('weight', '>=', 1);
            });
            $menuIds = Menu::where('store_id', $this->id)->pluck('id')->toArray();
            $q->orWhere(function ($q) use ($menuIds) {
                $q->whereIn('menu_id', $menuIds)->where('weight', '>=', 1);
            });
            $imgs = $q->get();
            if (count($imgs) < config('const.store.imgRequiredWeight.lower')) {
                $errors[] = '優先度1.00以上の画像は最低３つ設定してください。';
            }

            // メインジャンル取得
            $delegateGenre = GenreGroup::where('store_id', $this->id)->where('is_delegate', 1)->first();
            // メインジャンルが存在しない場合
            if (!$delegateGenre) {
                $errors[] = '店舗のメインジャンルを設定してください。';
            }

            // 店舗の利用サービスがテイクアウト/レストランの場合 または、店舗の利用サービスがレストランの場合
            if ($app_cd === key(config('code.appCd.tors')) || $app_cd === key(config('code.appCd.rs'))) {
                // レストランのキャンセル料取得
                $restaurantCancelFees = CancelFee::where('store_id', $this->id)->where('app_cd', key(config('code.appCd.rs')))->where('published', 1)->get();

                // レストランのキャンセル料設定がなかった場合
                if (!count($restaurantCancelFees) >= 1) {
                    $errors[] = '利用サービスに'.config('code.appCd.rs.RS').'を設定している場合は、'.config('code.appCd.rs.RS').'サービスのキャンセル料を設定してください。';
                }
            }

            // 店舗の販売手数料をチェックする
            if (is_null($store->settlementCompany)) {
                $errors[] = '店舗の精算会社を設定してください。';
            } else {
                $commissionRate = CommissionRate::where('settlement_company_id', $store->settlementCompany->id)->first();
                if (is_null($commissionRate)) {
                    $errors[] = '店舗の精算会社の販売手数料を設定してください。';
                } else {
                    $now = new Carbon();
                    // テイクアウトを含む場合
                    if (strpos($app_cd, key(config('code.appCd.to'))) !== false) {
                        $q = CommissionRate::where('apply_term_from', '<=', $now)
                        ->where('apply_term_to', '>', $now)
                        ->where('settlement_company_id', $store->settlementCompany->id);
                        $q->where('app_cd', key(config('code.appCd.to')));
                        $q->where('published', 1);
                        $cr = $q->get();
                        if (count($cr) === 0) {
                            $errors[] = 'テイクアウトの有効な販売手数料の設定がありません。';
                        }
                    }

                    // レストランを含む場合
                    if (strpos($app_cd, key(config('code.appCd.rs'))) !== false) {
                        $q = CommissionRate::where('apply_term_from', '<=', $now)
                        ->where('apply_term_to', '>', $now)
                        ->where('settlement_company_id', $store->settlementCompany->id);
                        $q->where('app_cd', key(config('code.appCd.rs')));
                        $q->where('published', 1);
                        $cr = $q->get();
                        if (count($cr) === 0) {
                            $errors[] = 'レストランの有効な販売手数料の設定がありません。';
                        }
                    }
                }
            }

            // エラーがあった場合にエラーメッセージを表示する
            if ($errors) {
                foreach ($errors as $error) {
                    $validator->errors()->add('published', '店舗「'.$store->name.'」を公開するには下記の設定をしてください。');
                    $validator->errors()->add('published', $error);
                }
            }
        });
    }
}
