<?php

namespace Tests\Unit\Rules;

use App\Models\CommissionRate;
use App\Models\Genre;
use App\Models\GenreGroup;
use App\Models\Image;
use App\Models\Menu;
use App\Models\OpeningHour;
use App\Models\Price;
use App\Models\SettlementCompany;
use App\Models\Store;
use App\Rules\isPublishable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IsPublishableTest extends TestCase
{
    private $testMenuId;
    private $testStoreId;
    private $testSettlementCompanyId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testIsPublishable()
    {
        $this->_createMenu();
        $menuId = $this->testMenuId;

        // 非公開の場合、OK
        $isPublished = 0;
        $validator = Validator::make(
            ['test' => $isPublished],
            ['test' => new isPublishable($menuId)]
        );
        $this->assertTrue($validator->passes());
        $this->assertCount(0, $validator->errors()->get('test'));   // エラーメッセージなし

        // 公開の場合
        {
            $isPublished = 1;

            // ジャンル未登録でNG
            $validator = Validator::make(
                ['test' => $isPublished],
                ['test' => new isPublishable($menuId)]
            );
            $this->assertFalse($validator->passes());
            $this->assertCount(1, $validator->errors()->get('test'));   // エラーメッセージあり
            $this->assertSame('メニューにジャンルが1つも登録されていません', $validator->errors()->get('test')[0]);

            // ジャンル登録
            $this->_createGenre();

            // 店舗未登録でNG
            $validator = Validator::make(
                ['test' => $isPublished],
                ['test' => new isPublishable($menuId)]
            );
            $this->assertFalse($validator->passes());
            $this->assertCount(1, $validator->errors()->get('test'));   // エラーメッセージあり
            $this->assertSame('店舗が登録されていません', $validator->errors()->get('test')[0]);

            // 店舗登録
            $this->_createStore();

            // 店舗営業時間未登録でNG
            $validator = Validator::make(
                ['test' => $isPublished],
                ['test' => new isPublishable($menuId)]
            );
            $this->assertFalse($validator->passes());
            $this->assertCount(1, $validator->errors()->get('test'));   // エラーメッセージあり
            $this->assertSame('店舗に営業時間が1つも登録されていません', $validator->errors()->get('test')[0]);

            // 店舗営業時間登録
            $this->_createOpeningHour();

            // 店舗画像未登録でNG
            $validator = Validator::make(
                ['test' => $isPublished],
                ['test' => new isPublishable($menuId)]
            );
            $this->assertFalse($validator->passes());
            $this->assertCount(1, $validator->errors()->get('test'));   // エラーメッセージあり
            $this->assertSame('店舗に画像が1つも登録されていません', $validator->errors()->get('test')[0]);

            // 店舗画像登録
            $this->_createImage('store_id', $this->testStoreId, 'RESTAURANT_LOGO');

            // 精算会社未登録でNG
            $validator = Validator::make(
                ['test' => $isPublished],
                ['test' => new isPublishable($menuId)]
            );
            $this->assertFalse($validator->passes());
            $this->assertCount(1, $validator->errors()->get('test'));   // エラーメッセージあり
            $this->assertSame('精算会社が登録されていません', $validator->errors()->get('test')[0]);

            // 精算会社登録
            $this->_createSettlementCompany();

            // 精算会社ポリシー未登録
            $validator = Validator::make(
                ['test' => $isPublished],
                ['test' => new isPublishable($menuId)]
            );
            $this->assertFalse($validator->passes());
            $this->assertCount(1, $validator->errors()->get('test'));   // エラーメッセージあり
            $this->assertSame('精算会社に手数料が1つも登録されていません', $validator->errors()->get('test')[0]);

            // 精算会社ポリシー登録
            $this->_createCommissionRate();

            // メニュー画像未登録でNG
            $validator = Validator::make(
                ['test' => $isPublished],
                ['test' => new isPublishable($menuId)]
            );
            $this->assertFalse($validator->passes());
            $this->assertCount(1, $validator->errors()->get('test'));   // エラーメッセージあり
            $this->assertSame('メニューに画像が1つも登録されていません', $validator->errors()->get('test')[0]);

            // メニュー画像登録
            $this->_createImage('menu_id', $menuId, 'MENU_MAIN');

            // メニュー画像未登録でNG
            $validator = Validator::make(
                ['test' => $isPublished],
                ['test' => new isPublishable($menuId)]
            );
            $this->assertFalse($validator->passes());
            $this->assertCount(1, $validator->errors()->get('test'));   // エラーメッセージあり
            $this->assertSame('メニューに料金が1つも登録されていません', $validator->errors()->get('test')[0]);

            // メニュー料金登録でNG
            $this->_createPrice();

            // エラーがなく、公開OK
            $validator = Validator::make(
                ['test' => $isPublished],
                ['test' => new isPublishable($menuId)]
            );
            $this->assertTrue($validator->passes());
            $this->assertCount(0, $validator->errors()->get('test'));   // エラーメッセージなし
        }
    }

    private function _createMenu()
    {
        $menu = new Menu();
        $menu->save();
        $this->testMenuId = $menu->id;
    }

    private function _createGenre()
    {
        $genreLevel2 = new Genre();
        $genreLevel2->level = 2;
        $genreLevel2->genre_cd = 'test2';
        $genreLevel2->published = 1;
        $genreLevel2->path = '/test';
        $genreLevel2->save();

        $genreGroup = new GenreGroup();
        $genreGroup->menu_id = $this->testMenuId;
        $genreGroup->genre_id = $genreLevel2->id;
        $genreGroup->is_delegate = 0;
        $genreGroup->save();
    }

    private function _createStore()
    {
        $store = new Store();
        $store->save();
        $this->testStoreId =  $store->id;

        Menu::find($this->testMenuId)->update(['store_id' => $this->testStoreId]);
    }

    private function _createOpeningHour()
    {
        $openingHour = new OpeningHour();
        $openingHour->store_id = $this->testStoreId;
        $openingHour->save();
    }

    private function _createImage($target, $value, $imageCd)
    {
        $image = new Image();
        $image->$target = $value;
        $image->image_cd = $imageCd;
        $image->weight = 100;
        $image->save();
    }

    private function _createSettlementCompany()
    {
        $settlementCompany = new SettlementCompany();
        $settlementCompany->name = 'testテストtest精算会社';
        $settlementCompany->tel = '0698765432';
        $settlementCompany->postal_code = '1111123';
        $settlementCompany->save();
        $this->testSettlementCompanyId = $settlementCompany->id;

        Store::find($this->testStoreId)->update(['settlement_company_id' => $this->testSettlementCompanyId]);
    }

    private function _createCommissionRate()
    {
        $commissionRate = new CommissionRate();
        $commissionRate->settlement_company_id = $this->testSettlementCompanyId;
        $commissionRate->app_cd = 'RS';
        $commissionRate->apply_term_from = '2022-01-01 00:00:00';
        $commissionRate->apply_term_to = '2025-12-31 23:59:59';
        $commissionRate->fee = '10.0';
        $commissionRate->accounting_condition = 'FIXED_RATE';
        $commissionRate->only_seat = 1;
        $commissionRate->published = 1;
        $commissionRate->save();
    }

    private function _createPrice()
    {
        $price = new Price();
        $price->menu_id = $this->testMenuId;
        $price->price = 1000;
        $price->save();
    }
}
