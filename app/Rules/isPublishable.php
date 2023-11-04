<?php

namespace App\Rules;

use App\Models\CommissionRate;
use App\Models\Image;
use App\Models\Menu;
use App\Models\Price;
use App\Models\SettlementCompany;
use App\Models\Store;
use Illuminate\Contracts\Validation\Rule;

class isPublishable implements Rule
{
    private $id;
    private $errMsg = '';

    const NO_GENRE = 'メニューにジャンルが1つも登録されていません';
    const NO_STORE = '店舗が登録されていません';
    const NO_OPENING_HOURS = '店舗に営業時間が1つも登録されていません';
    const NO_STORE_IMAGE = '店舗に画像が1つも登録されていません';
    const NO_SETTLEMENT = '精算会社が登録されていません';
    const NO_COMMISSION = '精算会社に手数料が1つも登録されていません';
    const NO_MENU_IMAGE = 'メニューに画像が1つも登録されていません';
    const NO_MENU_PRICE = 'メニューに料金が1つも登録されていません';

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // 非公開にするときはチェックしない
        if ((int)$value == 0) {
            return true;
        }

        // 公開時は色々チェック

        $menu = Menu::find($this->id);

        if ($menu->genres()->count() <= 0) {
            $this->errMsg = self::NO_GENRE;
            return false;
        }

        $store = Store::find($menu->store_id);
        if (is_null($store)) {
            $this->errMsg = self::NO_STORE;
            return false;
        }

        if ($store->openingHours->count() <= 0) {
            $this->errMsg = self::NO_OPENING_HOURS;
            return false;
        }

        if ($store->images()->count() <= 0) {
            $this->errMsg = self::NO_STORE_IMAGE;
            return false;
        }

        $settlementCompany = SettlementCompany::find($store->settlement_company_id);
        if (is_null($settlementCompany)) {
            $this->errMsg = self::NO_SETTLEMENT;
            return false;
        }

        $count = CommissionRate::where('settlement_company_id', $settlementCompany->id)->count();
        if ($count <= 0) {
            $this->errMsg = self::NO_COMMISSION;
            return false;
        }

        $count = Image::where('menu_id', $menu->id)->where('image_cd', config('code.imageCd.menuMain'))->count();
        if ($count <= 0) {
            $this->errMsg = self::NO_MENU_IMAGE;
            return false;
        }

        $count = Price::where('menu_id', $menu->id)->count();
        if ($count <= 0) {
            $this->errMsg = self::NO_MENU_PRICE;
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->errMsg;
    }
}
