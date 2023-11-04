<?php

namespace Tests\Feature\Controller\Admin;

use App\Libs\ImageUpload;
use App\Models\CancelFee;
use App\Models\CommissionRate;
use App\Models\Genre;
use App\Models\GenreGroup;
use App\Models\Image;
use App\Models\Menu;
use App\Models\OpeningHour;
use App\Models\SettlementCompany;
use App\Models\Store;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class StoreControllerTest extends TestCase
{
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

    public function testIndexWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.index');        // 指定bladeを確認
        $response->assertViewHasAll(['stores']);             // bladeに渡している変数を確認

        $this->logout();
    }

    public function testIndexWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.index');        // 指定bladeを確認
        $response->assertViewHasAll(['stores']);             // bladeに渡している変数を確認

        $this->logout();
    }

    public function testIndexWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.index');        // 指定bladeを確認
        $response->assertViewHasAll(['stores']);             // bladeに渡している変数を確認

        $this->logout();
    }

    public function testIndexWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.index');        // 指定bladeを確認
        $response->assertViewHasAll(['stores']);             // bladeに渡している変数を確認

        $this->logout();
    }

    public function testEditFormWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.edit');        // 指定bladeを確認
        $response->assertViewHasAll([
            'appCd',
            'store',
            'settlementCompanies',
            'areasLevel1',
            'areasLevel2',
            'parentArea',
            'useFax',
            'regularHoliday',
            'canCard',
            'cardTypes',
            'canDigitalMoney',
            'digitalMoneyTypes',
            'smokingTypes',
            'canCharter',
            'charterTypes',
            'hasPrivateRoom',
            'privateRoomTypes',
            'hasParking',
            'hasCoinParking',
            'budgetLowerLimit',
            'budgetLimit',
            'pickUpTimeInterval',
        ]);                                                // bladeに渡している変数を確認
        $response->assertViewHas('appCd', config('code.appCd'));
        $response->assertViewHas('store', $store);
        $response->assertViewHas('useFax', config('const.store.use_fax'));
        $response->assertViewHas('regularHoliday', config('const.store.regular_holiday'));
        $response->assertViewHas('canCard', config('const.store.can_card'));
        $response->assertViewHas('cardTypes', config('const.store.card_types'));
        $response->assertViewHas('canDigitalMoney', config('const.store.can_digital_money'));
        $response->assertViewHas('digitalMoneyTypes', config('const.store.digital_money_types'));
        $response->assertViewHas('smokingTypes', config('const.store.smoking_types'));
        $response->assertViewHas('canCharter', config('const.store.can_charter'));
        $response->assertViewHas('charterTypes', config('const.store.charter_types'));
        $response->assertViewHas('hasPrivateRoom', config('const.store.has_private_room'));
        $response->assertViewHas('privateRoomTypes', config('const.store.private_room_types'));
        $response->assertViewHas('hasParking', config('const.store.has_parking'));
        $response->assertViewHas('hasCoinParking', config('const.store.has_coin_parking'));
        $response->assertViewHas('budgetLowerLimit', config('const.store.budget_lower_limit'));
        $response->assertViewHas('budgetLimit', config('const.store.budget_limit'));
        $response->assertViewHas('pickUpTimeInterval', config('const.store.pick_up_time_interval'));

        $this->logout();
    }

    public function testEditFormWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callEditForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.edit');        // 指定bladeを確認
        $response->assertViewHasAll([
            'appCd',
            'store',
            'settlementCompanies',
            'areasLevel1',
            'areasLevel2',
            'parentArea',
            'useFax',
            'regularHoliday',
            'canCard',
            'cardTypes',
            'canDigitalMoney',
            'digitalMoneyTypes',
            'smokingTypes',
            'canCharter',
            'charterTypes',
            'hasPrivateRoom',
            'privateRoomTypes',
            'hasParking',
            'hasCoinParking',
            'budgetLowerLimit',
            'budgetLimit',
            'pickUpTimeInterval',
        ]);                                                // bladeに渡している変数を確認
        $response->assertViewHas('appCd', config('code.appCd'));
        $response->assertViewHas('store', $store);
        $response->assertViewHas('useFax', config('const.store.use_fax'));
        $response->assertViewHas('regularHoliday', config('const.store.regular_holiday'));
        $response->assertViewHas('canCard', config('const.store.can_card'));
        $response->assertViewHas('cardTypes', config('const.store.card_types'));
        $response->assertViewHas('canDigitalMoney', config('const.store.can_digital_money'));
        $response->assertViewHas('digitalMoneyTypes', config('const.store.digital_money_types'));
        $response->assertViewHas('smokingTypes', config('const.store.smoking_types'));
        $response->assertViewHas('canCharter', config('const.store.can_charter'));
        $response->assertViewHas('charterTypes', config('const.store.charter_types'));
        $response->assertViewHas('hasPrivateRoom', config('const.store.has_private_room'));
        $response->assertViewHas('privateRoomTypes', config('const.store.private_room_types'));
        $response->assertViewHas('hasParking', config('const.store.has_parking'));
        $response->assertViewHas('hasCoinParking', config('const.store.has_coin_parking'));
        $response->assertViewHas('budgetLowerLimit', config('const.store.budget_lower_limit'));
        $response->assertViewHas('budgetLimit', config('const.store.budget_limit'));
        $response->assertViewHas('pickUpTimeInterval', config('const.store.pick_up_time_interval'));

        $this->logout();
    }

    public function testEditFormWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常にアクセスできること
        {
            $response = $this->_callEditForm($store);
            $response->assertStatus(200);
            $response->assertViewIs('admin.Store.edit');        // 指定bladeを確認
            $response->assertViewHasAll([
                'appCd',
                'store',
                'settlementCompanies',
                'areasLevel1',
                'areasLevel2',
                'parentArea',
                'useFax',
                'regularHoliday',
                'canCard',
                'cardTypes',
                'canDigitalMoney',
                'digitalMoneyTypes',
                'smokingTypes',
                'canCharter',
                'charterTypes',
                'hasPrivateRoom',
                'privateRoomTypes',
                'hasParking',
                'hasCoinParking',
                'budgetLowerLimit',
                'budgetLimit',
                'pickUpTimeInterval',
            ]);                                                // bladeに渡している変数を確認
            $response->assertViewHas('appCd', config('code.appCd'));
            $response->assertViewHas('store', $store);
            $response->assertViewHas('useFax', config('const.store.use_fax'));
            $response->assertViewHas('regularHoliday', config('const.store.regular_holiday'));
            $response->assertViewHas('canCard', config('const.store.can_card'));
            $response->assertViewHas('cardTypes', config('const.store.card_types'));
            $response->assertViewHas('canDigitalMoney', config('const.store.can_digital_money'));
            $response->assertViewHas('digitalMoneyTypes', config('const.store.digital_money_types'));
            $response->assertViewHas('smokingTypes', config('const.store.smoking_types'));
            $response->assertViewHas('canCharter', config('const.store.can_charter'));
            $response->assertViewHas('charterTypes', config('const.store.charter_types'));
            $response->assertViewHas('hasPrivateRoom', config('const.store.has_private_room'));
            $response->assertViewHas('privateRoomTypes', config('const.store.private_room_types'));
            $response->assertViewHas('hasParking', config('const.store.has_parking'));
            $response->assertViewHas('hasCoinParking', config('const.store.has_coin_parking'));
            $response->assertViewHas('budgetLowerLimit', config('const.store.budget_lower_limit'));
            $response->assertViewHas('budgetLimit', config('const.store.budget_limit'));
            $response->assertViewHas('pickUpTimeInterval', config('const.store.pick_up_time_interval'));
        }

        // 担当外店舗の場合、アクセスできないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callEditForm($store2);
            $response->assertStatus(403);
        }

        $this->logout();
    }

    public function testEditFormWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callEditForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.edit');        // 指定bladeを確認
        $response->assertViewHasAll([
            'appCd',
            'store',
            'settlementCompanies',
            'areasLevel1',
            'areasLevel2',
            'parentArea',
            'useFax',
            'regularHoliday',
            'canCard',
            'cardTypes',
            'canDigitalMoney',
            'digitalMoneyTypes',
            'smokingTypes',
            'canCharter',
            'charterTypes',
            'hasPrivateRoom',
            'privateRoomTypes',
            'hasParking',
            'hasCoinParking',
            'budgetLowerLimit',
            'budgetLimit',
            'pickUpTimeInterval',
        ]);                                                // bladeに渡している変数を確認
        $response->assertViewHas('appCd', config('code.appCd'));
        $response->assertViewHas('store', $store);
        $response->assertViewHas('useFax', config('const.store.use_fax'));
        $response->assertViewHas('regularHoliday', config('const.store.regular_holiday'));
        $response->assertViewHas('canCard', config('const.store.can_card'));
        $response->assertViewHas('cardTypes', config('const.store.card_types'));
        $response->assertViewHas('canDigitalMoney', config('const.store.can_digital_money'));
        $response->assertViewHas('digitalMoneyTypes', config('const.store.digital_money_types'));
        $response->assertViewHas('smokingTypes', config('const.store.smoking_types'));
        $response->assertViewHas('canCharter', config('const.store.can_charter'));
        $response->assertViewHas('charterTypes', config('const.store.charter_types'));
        $response->assertViewHas('hasPrivateRoom', config('const.store.has_private_room'));
        $response->assertViewHas('privateRoomTypes', config('const.store.private_room_types'));
        $response->assertViewHas('hasParking', config('const.store.has_parking'));
        $response->assertViewHas('hasCoinParking', config('const.store.has_coin_parking'));
        $response->assertViewHas('budgetLowerLimit', config('const.store.budget_lower_limit'));
        $response->assertViewHas('budgetLimit', config('const.store.budget_limit'));
        $response->assertViewHas('pickUpTimeInterval', config('const.store.pick_up_time_interval'));

        $this->logout();
    }

    public function testEditWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEdit($store);
        $response->assertStatus(302);                       // リダイレクト
        $response->assertRedirect('/admin/store/');         // リダイレクト先
        $response->assertSessionHas('message', '店舗情報「テスト店舗更新」を更新しました');

        $result = Store::find($store->id);
        $this->assertSame('テスト店舗更新', $result->name);
        $this->assertSame('test-alias-name', $result->alias_name);
        $this->assertSame('RS', $result->app_cd);
        $this->assertSame('test_edit1', $result->code);
        $this->assertSame('東京都', $result->address_1);
        $this->assertSame('新宿区', $result->address_2);
        $this->assertSame('テストタワー1F', $result->address_3);
        $this->assertSame('1234567', $result->postal_code);
        $this->assertSame('0311112222', $result->tel);
        $this->assertSame('0333334444', $result->tel_order);
        $this->assertSame(100.0, $result->latitude);
        $this->assertSame(200.0, $result->longitude);
        $this->assertSame('gourmet-teststore1@adventure-inc.co.jp', $result->email_1);
        $this->assertSame('gourmet-teststore2@adventure-inc.co.jp', $result->email_2);
        $this->assertSame('gourmet-teststore3@adventure-inc.co.jp', $result->email_3);
        $this->assertSame(1000, $result->daytime_budget_lower_limit);
        $this->assertSame(3999, $result->daytime_budget_limit);
        $this->assertSame(2000, $result->night_budget_lower_limit);
        $this->assertSame(5999, $result->night_budget_limit);
        $this->assertSame('最寄りから徒歩5分', $result->access);
        $this->assertSame('公式アカウント123457890', $result->account);
        $this->assertSame(60, $result->pick_up_time_interval);
        $this->assertSame('テスト備考', $result->remarks);
        $this->assertSame('テスト説明', $result->description);
        $this->assertSame('0355556666', $result->fax);
        $this->assertSame(1, $result->use_fax);
        $this->assertSame('111111110', $result->regular_holiday);
        $this->assertSame(1, $result->price_level);
        $this->assertSame(90, $result->lower_orders_time);
        $this->assertSame(1, $result->can_card);
        $this->assertSame('VISA,JCB', $result->card_types);
        $this->assertSame(1, $result->can_digital_money);
        $this->assertSame('ID,PAYPAY', $result->digital_money_types);
        $this->assertSame(1, $result->has_private_room);
        $this->assertSame('8_PEOPLE7,10-20_PEOPLE', $result->private_room_types);
        $this->assertSame(1, $result->has_parking);
        $this->assertSame(1, $result->has_coin_parking);
        $this->assertSame(10, $result->number_of_seats);
        $this->assertSame(1, $result->can_charter);
        $this->assertSame('20-50_PEOPLE', $result->charter_types);
        $this->assertSame(0, $result->smoking);
        $this->assertSame('NO_SMOKING', $result->smoking_types);
        $this->assertSame($settlementCompany->id, $result->settlement_company_id);
        $this->assertSame(1, $result->area_id);
        $this->assertSame(0, $result->published);

        $this->logout();
    }

    public function testEditWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callEdit($store);
        $response->assertStatus(302);                       // リダイレクト
        $response->assertRedirect('/admin/store/');         // リダイレクト先
        $response->assertSessionHas('message', '店舗情報「テスト店舗更新」を更新しました');

        $result = Store::find($store->id);
        $this->assertSame('テスト店舗更新', $result->name);
        $this->assertSame('test-alias-name', $result->alias_name);
        $this->assertSame('RS', $result->app_cd);
        $this->assertSame('test_edit1', $result->code);
        $this->assertSame('東京都', $result->address_1);
        $this->assertSame('新宿区', $result->address_2);
        $this->assertSame('テストタワー1F', $result->address_3);
        $this->assertSame('1234567', $result->postal_code);
        $this->assertSame('0311112222', $result->tel);
        $this->assertSame('0333334444', $result->tel_order);
        $this->assertSame(100.0, $result->latitude);
        $this->assertSame(200.0, $result->longitude);
        $this->assertSame('gourmet-teststore1@adventure-inc.co.jp', $result->email_1);
        $this->assertSame('gourmet-teststore2@adventure-inc.co.jp', $result->email_2);
        $this->assertSame('gourmet-teststore3@adventure-inc.co.jp', $result->email_3);
        $this->assertSame(1000, $result->daytime_budget_lower_limit);
        $this->assertSame(3999, $result->daytime_budget_limit);
        $this->assertSame(2000, $result->night_budget_lower_limit);
        $this->assertSame(5999, $result->night_budget_limit);
        $this->assertSame('最寄りから徒歩5分', $result->access);
        $this->assertSame('公式アカウント123457890', $result->account);
        $this->assertSame(60, $result->pick_up_time_interval);
        $this->assertSame('テスト備考', $result->remarks);
        $this->assertSame('テスト説明', $result->description);
        $this->assertSame('0355556666', $result->fax);
        $this->assertSame(1, $result->use_fax);
        $this->assertSame('111111110', $result->regular_holiday);
        $this->assertSame(1, $result->price_level);
        $this->assertSame(90, $result->lower_orders_time);
        $this->assertSame(1, $result->can_card);
        $this->assertSame('VISA,JCB', $result->card_types);
        $this->assertSame(1, $result->can_digital_money);
        $this->assertSame('ID,PAYPAY', $result->digital_money_types);
        $this->assertSame(1, $result->has_private_room);
        $this->assertSame('8_PEOPLE7,10-20_PEOPLE', $result->private_room_types);
        $this->assertSame(1, $result->has_parking);
        $this->assertSame(1, $result->has_coin_parking);
        $this->assertSame(10, $result->number_of_seats);
        $this->assertSame(1, $result->can_charter);
        $this->assertSame('20-50_PEOPLE', $result->charter_types);
        $this->assertSame(0, $result->smoking);
        $this->assertSame('NO_SMOKING', $result->smoking_types);
        $this->assertSame($settlementCompany->id, $result->settlement_company_id);
        $this->assertSame(1, $result->area_id);
        $this->assertSame(0, $result->published);

        $this->logout();
    }

    public function testEditWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常に処理できること
        {
            $response = $this->_callEdit($store);
            $response->assertStatus(302);                       // リダイレクト
            $response->assertRedirect('/admin/store/');         // リダイレクト先
            $response->assertSessionHas('message', '店舗情報「テスト店舗更新」を更新しました');

            $result = Store::find($store->id);
            $this->assertSame('テスト店舗更新', $result->name);
            $this->assertSame('test-alias-name', $result->alias_name);
            $this->assertSame('RS', $result->app_cd);
            $this->assertSame('test-code-test', $result->code);                  // 更新されていないこと
            $this->assertSame('東京都', $result->address_1);
            $this->assertSame('新宿区', $result->address_2);
            $this->assertSame('テストタワー1F', $result->address_3);
            $this->assertSame('1234567', $result->postal_code);
            $this->assertSame('0311112222', $result->tel);
            $this->assertSame('0333334444', $result->tel_order);
            $this->assertSame(100.0, $result->latitude);
            $this->assertSame(200.0, $result->longitude);
            $this->assertSame('gourmet-teststore1@adventure-inc.co.jp', $result->email_1);
            $this->assertSame('gourmet-teststore2@adventure-inc.co.jp', $result->email_2);
            $this->assertSame('gourmet-teststore3@adventure-inc.co.jp', $result->email_3);
            $this->assertSame(1000, $result->daytime_budget_lower_limit);
            $this->assertSame(3999, $result->daytime_budget_limit);
            $this->assertSame(2000, $result->night_budget_lower_limit);
            $this->assertSame(5999, $result->night_budget_limit);
            $this->assertSame('最寄りから徒歩5分', $result->access);
            $this->assertSame('公式アカウント123457890', $result->account);
            $this->assertSame(60, $result->pick_up_time_interval);
            $this->assertSame('テスト備考', $result->remarks);
            $this->assertSame('テスト説明', $result->description);
            $this->assertSame('0355556666', $result->fax);
            $this->assertSame(1, $result->use_fax);
            $this->assertSame('111111110', $result->regular_holiday);
            $this->assertSame(1, $result->price_level);
            $this->assertSame(90, $result->lower_orders_time);
            $this->assertSame(1, $result->can_card);
            $this->assertSame('VISA,JCB', $result->card_types);
            $this->assertSame(1, $result->can_digital_money);
            $this->assertSame('ID,PAYPAY', $result->digital_money_types);
            $this->assertSame(1, $result->has_private_room);
            $this->assertSame('8_PEOPLE7,10-20_PEOPLE', $result->private_room_types);
            $this->assertSame(1, $result->has_parking);
            $this->assertSame(1, $result->has_coin_parking);
            $this->assertSame(10, $result->number_of_seats);
            $this->assertSame(1, $result->can_charter);
            $this->assertSame('20-50_PEOPLE', $result->charter_types);
            $this->assertSame(0, $result->smoking);
            $this->assertSame('NO_SMOKING', $result->smoking_types);
            $this->assertSame($settlementCompany->id, $result->settlement_company_id);
            $this->assertSame(1, $result->area_id);
            $this->assertSame(0, $result->published);
        }

        // 担当外店舗の場合、正常に処理できないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callEdit($store2);
            $response->assertStatus(302);                       // リダイレクト
            $response->assertRedirect('/admin/store/');         // リダイレクト先
            $response->assertSessionHas('custom_error', '店舗情報「テスト店舗更新」を更新できませんでした');
        }

        $this->logout();
    }

    public function testEditWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callEdit($store);
        $response->assertStatus(302);                       // リダイレクト
        $response->assertRedirect('/admin/store/');         // リダイレクト先
        $response->assertSessionHas('message', '店舗情報「テスト店舗更新」を更新しました');

        $result = Store::find($store->id);
        $this->assertSame('テスト店舗更新', $result->name);
        $this->assertSame('test-alias-name', $result->alias_name);
        $this->assertSame('RS', $result->app_cd);
        $this->assertSame('test_edit1', $result->code);
        $this->assertSame('東京都', $result->address_1);
        $this->assertSame('新宿区', $result->address_2);
        $this->assertSame('テストタワー1F', $result->address_3);
        $this->assertSame('1234567', $result->postal_code);
        $this->assertSame('0311112222', $result->tel);
        $this->assertSame('0333334444', $result->tel_order);
        $this->assertSame(100.0, $result->latitude);
        $this->assertSame(200.0, $result->longitude);
        $this->assertSame('gourmet-teststore1@adventure-inc.co.jp', $result->email_1);
        $this->assertSame('gourmet-teststore2@adventure-inc.co.jp', $result->email_2);
        $this->assertSame('gourmet-teststore3@adventure-inc.co.jp', $result->email_3);
        $this->assertSame(1000, $result->daytime_budget_lower_limit);
        $this->assertSame(3999, $result->daytime_budget_limit);
        $this->assertSame(2000, $result->night_budget_lower_limit);
        $this->assertSame(5999, $result->night_budget_limit);
        $this->assertSame('最寄りから徒歩5分', $result->access);
        $this->assertSame('公式アカウント123457890', $result->account);
        $this->assertSame(60, $result->pick_up_time_interval);
        $this->assertSame('テスト備考', $result->remarks);
        $this->assertSame('テスト説明', $result->description);
        $this->assertSame('0355556666', $result->fax);
        $this->assertSame(1, $result->use_fax);
        $this->assertSame('111111110', $result->regular_holiday);
        $this->assertSame(1, $result->price_level);
        $this->assertSame(90, $result->lower_orders_time);
        $this->assertSame(1, $result->can_card);
        $this->assertSame('VISA,JCB', $result->card_types);
        $this->assertSame(1, $result->can_digital_money);
        $this->assertSame('ID,PAYPAY', $result->digital_money_types);
        $this->assertSame(1, $result->has_private_room);
        $this->assertSame('8_PEOPLE7,10-20_PEOPLE', $result->private_room_types);
        $this->assertSame(1, $result->has_parking);
        $this->assertSame(1, $result->has_coin_parking);
        $this->assertSame(10, $result->number_of_seats);
        $this->assertSame(1, $result->can_charter);
        $this->assertSame('20-50_PEOPLE', $result->charter_types);
        $this->assertSame(0, $result->smoking);
        $this->assertSame('NO_SMOKING', $result->smoking_types);
        $this->assertSame($settlementCompany->id, $result->settlement_company_id);
        $this->assertSame(1, $result->area_id);
        $this->assertSame(0, $result->published);

        $this->logout();
    }

    public function testEditExistsImageWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditExistsImage($store, $storeImage, $menuImage, $imageName);
        $response->assertStatus(302);                       // リダイレクト
        $response->assertRedirect('/admin/store/');         // リダイレクト先
        $response->assertSessionHas('message', '店舗情報「テスト店舗更新」を更新しました');

        $result = Store::find($store->id);
        $this->assertSame('テスト店舗更新', $result->name);     // 全項目の更新チェックはtestEditWith関数で確認済みのため省略

        // 画像のパスが変わっていること
        $resultStoreImage = Image::find($storeImage->id);
        $this->assertSame(ImageUpload::environment() . 'images/test_edit1/store/' . $imageName[0], $resultStoreImage->url);
        $resultMenuImage = Image::find($menuImage->id);
        $this->assertSame(ImageUpload::environment() . 'images/test_edit1/menu/' . $imageName[1], $resultMenuImage->url);

        // テスト用にアップロードしたファイルを削除しておく
        $dirPath = 'images/test_edit1/';
        $this->_deleteImage($dirPath . 'store/', $imageName[0]);
        $this->_deleteImage($dirPath . 'menu/', $imageName[1]);

        $this->logout();
    }

    public function testEditExistsImageWithClientAdministratore()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        $response = $this->_callEditExistsImage($store, $storeImage, $menuImage, $imageName);
        $response->assertStatus(302);                       // リダイレクト
        $response->assertRedirect('/admin/store/');         // リダイレクト先
        $response->assertSessionHas('message', '店舗情報「テスト店舗更新」を更新しました');

        $result = Store::find($store->id);
        $this->assertSame('テスト店舗更新', $result->name);     // 全項目の更新チェックはtestEditWith関数で確認済みのため省略

        // 画像のパスが変わっていないことを確認する
        $resultStoreImage = Image::find($storeImage->id);
        $this->assertSame(ImageUpload::environment() . 'images/test-code-test/store/' . $imageName[0], $resultStoreImage->url);
        $resultMenuImage = Image::find($menuImage->id);
        $this->assertSame(ImageUpload::environment() . 'images/test-code-test/menu/' . $imageName[1], $resultMenuImage->url);

        // テスト用にアップロードしたファイルを削除しておく
        $dirPath = 'images/test-code-test/';
        $this->_deleteImage($dirPath . 'store/', $imageName[0]);
        $this->_deleteImage($dirPath . 'menu/', $imageName[1]);

        $this->logout();
    }

    public function testEditRegularHoliday()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        // 不定期の場合は、regular_holidayが「1111111110」になること
        {
            $response = $this->_callEdit($store, [1, null, null, null, null, null, null, null, null, 1]);
            $response->assertStatus(302);                       // リダイレクト
            $response->assertRedirect('/admin/store/');         // リダイレクト先
            $response->assertSessionHas('message', '店舗情報「テスト店舗更新」を更新しました');

            $result = Store::find($store->id);
            $this->assertSame('1111111110', $result->regular_holiday);
        }

        // 不定期ではない場合は、「1111111110」にならないこと
        {
            $response = $this->_callEdit($store, [1, null, null, null, null, null, null, null, null, null]);
            $response->assertStatus(302);                       // リダイレクト
            $response->assertRedirect('/admin/store/');         // リダイレクト先
            $response->assertSessionHas('message', '店舗情報「テスト店舗更新」を更新しました');

            $result = Store::find($store->id);
            $this->assertSame('0111111101', $result->regular_holiday);
        }

        $this->logout();
    }

    public function testAddFormWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callAddForm();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.add');         // 指定bladeを確認
        $response->assertViewHasAll([
            'appCd',
            'areasLevel1',
            'settlementCompanies',
            'useFax',
            'regularHoliday',
            'canCard',
            'cardTypes',
            'canDigitalMoney',
            'digitalMoneyTypes',
            'smokingTypes',
            'canCharter',
            'charterTypes',
            'hasPrivateRoom',
            'privateRoomTypes',
            'hasParking',
            'hasCoinParking',
            'budgetLowerLimit',
            'budgetLimit',
            'pickUpTimeInterval',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('appCd', config('code.appCd'));
        $response->assertViewHas('useFax', config('const.store.use_fax'));
        $response->assertViewHas('regularHoliday', config('const.store.regular_holiday'));
        $response->assertViewHas('canCard', config('const.store.can_card'));
        $response->assertViewHas('cardTypes', config('const.store.card_types'));
        $response->assertViewHas('canDigitalMoney', config('const.store.can_digital_money'));
        $response->assertViewHas('digitalMoneyTypes', config('const.store.digital_money_types'));
        $response->assertViewHas('smokingTypes', config('const.store.smoking_types'));
        $response->assertViewHas('canCharter', config('const.store.can_charter'));
        $response->assertViewHas('charterTypes', config('const.store.charter_types'));
        $response->assertViewHas('hasPrivateRoom', config('const.store.has_private_room'));
        $response->assertViewHas('privateRoomTypes', config('const.store.private_room_types'));
        $response->assertViewHas('hasParking', config('const.store.has_parking'));
        $response->assertViewHas('hasCoinParking', config('const.store.has_coin_parking'));
        $response->assertViewHas('budgetLowerLimit', config('const.store.budget_lower_limit'));
        $response->assertViewHas('budgetLimit', config('const.store.budget_limit'));
        $response->assertViewHas('pickUpTimeInterval', config('const.store.pick_up_time_interval'));

        $this->logout();
    }

    public function testAddFormWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callAddForm();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.add');         // 指定bladeを確認
        $response->assertViewHasAll([
            'appCd',
            'areasLevel1',
            'settlementCompanies',
            'useFax',
            'regularHoliday',
            'canCard',
            'cardTypes',
            'canDigitalMoney',
            'digitalMoneyTypes',
            'smokingTypes',
            'canCharter',
            'charterTypes',
            'hasPrivateRoom',
            'privateRoomTypes',
            'hasParking',
            'hasCoinParking',
            'budgetLowerLimit',
            'budgetLimit',
            'pickUpTimeInterval',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('appCd', config('code.appCd'));
        $response->assertViewHas('useFax', config('const.store.use_fax'));
        $response->assertViewHas('regularHoliday', config('const.store.regular_holiday'));
        $response->assertViewHas('canCard', config('const.store.can_card'));
        $response->assertViewHas('cardTypes', config('const.store.card_types'));
        $response->assertViewHas('canDigitalMoney', config('const.store.can_digital_money'));
        $response->assertViewHas('digitalMoneyTypes', config('const.store.digital_money_types'));
        $response->assertViewHas('smokingTypes', config('const.store.smoking_types'));
        $response->assertViewHas('canCharter', config('const.store.can_charter'));
        $response->assertViewHas('charterTypes', config('const.store.charter_types'));
        $response->assertViewHas('hasPrivateRoom', config('const.store.has_private_room'));
        $response->assertViewHas('privateRoomTypes', config('const.store.private_room_types'));
        $response->assertViewHas('hasParking', config('const.store.has_parking'));
        $response->assertViewHas('hasCoinParking', config('const.store.has_coin_parking'));
        $response->assertViewHas('budgetLowerLimit', config('const.store.budget_lower_limit'));
        $response->assertViewHas('budgetLimit', config('const.store.budget_limit'));
        $response->assertViewHas('pickUpTimeInterval', config('const.store.pick_up_time_interval'));

        $this->logout();
    }

    public function testAddFormWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callAddForm();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.add');         // 指定bladeを確認
        $response->assertViewHasAll([
            'appCd',
            'areasLevel1',
            'settlementCompanies',
            'useFax',
            'regularHoliday',
            'canCard',
            'cardTypes',
            'canDigitalMoney',
            'digitalMoneyTypes',
            'smokingTypes',
            'canCharter',
            'charterTypes',
            'hasPrivateRoom',
            'privateRoomTypes',
            'hasParking',
            'hasCoinParking',
            'budgetLowerLimit',
            'budgetLimit',
            'pickUpTimeInterval',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('appCd', config('code.appCd'));
        $response->assertViewHas('useFax', config('const.store.use_fax'));
        $response->assertViewHas('regularHoliday', config('const.store.regular_holiday'));
        $response->assertViewHas('canCard', config('const.store.can_card'));
        $response->assertViewHas('cardTypes', config('const.store.card_types'));
        $response->assertViewHas('canDigitalMoney', config('const.store.can_digital_money'));
        $response->assertViewHas('digitalMoneyTypes', config('const.store.digital_money_types'));
        $response->assertViewHas('smokingTypes', config('const.store.smoking_types'));
        $response->assertViewHas('canCharter', config('const.store.can_charter'));
        $response->assertViewHas('charterTypes', config('const.store.charter_types'));
        $response->assertViewHas('hasPrivateRoom', config('const.store.has_private_room'));
        $response->assertViewHas('privateRoomTypes', config('const.store.private_room_types'));
        $response->assertViewHas('hasParking', config('const.store.has_parking'));
        $response->assertViewHas('hasCoinParking', config('const.store.has_coin_parking'));
        $response->assertViewHas('budgetLowerLimit', config('const.store.budget_lower_limit'));
        $response->assertViewHas('budgetLimit', config('const.store.budget_limit'));
        $response->assertViewHas('pickUpTimeInterval', config('const.store.pick_up_time_interval'));

        $this->logout();
    }

    public function testAddWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callAdd($settlementCompany->id);
        $response->assertStatus(302);                                             // リダイレクト
        $storeLists = Store::query()->count();
        $pageNumber = ($storeLists > 0) ? ceil($storeLists / 30) : 1;
        $response->assertRedirect('/admin/store?page=' . $pageNumber);          // リダイレクト先
        $response->assertSessionHas('message', '店舗「テスト店舗追加」を作成しました');

        $result = Store::where('settlement_company_id', $settlementCompany->id)->first();
        $this->assertSame('テスト店舗追加', $result->name);
        $this->assertSame('test-alias-name', $result->alias_name);
        $this->assertSame('RS', $result->app_cd);
        $this->assertSame('test_add1', $result->code);
        $this->assertSame('東京都', $result->address_1);
        $this->assertSame('新宿区', $result->address_2);
        $this->assertSame('テストタワー1F', $result->address_3);
        $this->assertSame('1234567', $result->postal_code);
        $this->assertSame('0311112222', $result->tel);
        $this->assertSame('0333334444', $result->tel_order);
        $this->assertSame(100.0, $result->latitude);
        $this->assertSame(200.0, $result->longitude);
        $this->assertSame('gourmet-teststore1@adventure-inc.co.jp', $result->email_1);
        $this->assertSame('gourmet-teststore2@adventure-inc.co.jp', $result->email_2);
        $this->assertSame('gourmet-teststore3@adventure-inc.co.jp', $result->email_3);
        $this->assertSame(1000, $result->daytime_budget_lower_limit);
        $this->assertSame(3999, $result->daytime_budget_limit);
        $this->assertSame(2000, $result->night_budget_lower_limit);
        $this->assertSame(5999, $result->night_budget_limit);
        $this->assertSame('最寄りから徒歩5分', $result->access);
        $this->assertSame('公式アカウント123457890', $result->account);
        $this->assertSame(60, $result->pick_up_time_interval);
        $this->assertSame('テスト備考', $result->remarks);
        $this->assertSame('テスト説明', $result->description);
        $this->assertSame('0355556666', $result->fax);
        $this->assertSame(1, $result->use_fax);
        $this->assertSame('111111110', $result->regular_holiday);
        $this->assertSame(1, $result->price_level);
        $this->assertSame(90, $result->lower_orders_time);
        $this->assertSame(1, $result->can_card);
        $this->assertSame('VISA,JCB', $result->card_types);
        $this->assertSame(1, $result->can_digital_money);
        $this->assertSame('ID,PAYPAY', $result->digital_money_types);
        $this->assertSame(1, $result->has_private_room);
        $this->assertSame('8_PEOPLE7,10-20_PEOPLE', $result->private_room_types);
        $this->assertSame(1, $result->has_parking);
        $this->assertSame(1, $result->has_coin_parking);
        $this->assertSame(10, $result->number_of_seats);
        $this->assertSame(1, $result->can_charter);
        $this->assertSame('20-50_PEOPLE', $result->charter_types);
        $this->assertSame(0, $result->smoking);
        $this->assertSame('NO_SMOKING', $result->smoking_types);
        $this->assertSame(1, $result->area_id);
        $this->assertSame(0, $result->published);

        $this->logout();
    }

    public function testAddWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callAdd($settlementCompany->id);
        $response->assertStatus(302);                                             // リダイレクト
        $storeLists = Store::query()->count();
        $pageNumber = ($storeLists > 0) ? ceil($storeLists / 30) : 1;
        $response->assertRedirect('/admin/store?page=' . $pageNumber);          // リダイレクト先
        $response->assertSessionHas('message', '店舗「テスト店舗追加」を作成しました');

        $result = Store::where('settlement_company_id', $settlementCompany->id)->first();
        $this->assertSame('テスト店舗追加', $result->name);
        $this->assertSame('test-alias-name', $result->alias_name);
        $this->assertSame('RS', $result->app_cd);
        $this->assertSame('test_add1', $result->code);
        $this->assertSame('東京都', $result->address_1);
        $this->assertSame('新宿区', $result->address_2);
        $this->assertSame('テストタワー1F', $result->address_3);
        $this->assertSame('1234567', $result->postal_code);
        $this->assertSame('0311112222', $result->tel);
        $this->assertSame('0333334444', $result->tel_order);
        $this->assertSame(100.0, $result->latitude);
        $this->assertSame(200.0, $result->longitude);
        $this->assertSame('gourmet-teststore1@adventure-inc.co.jp', $result->email_1);
        $this->assertSame('gourmet-teststore2@adventure-inc.co.jp', $result->email_2);
        $this->assertSame('gourmet-teststore3@adventure-inc.co.jp', $result->email_3);
        $this->assertSame(1000, $result->daytime_budget_lower_limit);
        $this->assertSame(3999, $result->daytime_budget_limit);
        $this->assertSame(2000, $result->night_budget_lower_limit);
        $this->assertSame(5999, $result->night_budget_limit);
        $this->assertSame('最寄りから徒歩5分', $result->access);
        $this->assertSame('公式アカウント123457890', $result->account);
        $this->assertSame(60, $result->pick_up_time_interval);
        $this->assertSame('テスト備考', $result->remarks);
        $this->assertSame('テスト説明', $result->description);
        $this->assertSame('0355556666', $result->fax);
        $this->assertSame(1, $result->use_fax);
        $this->assertSame('111111110', $result->regular_holiday);
        $this->assertSame(1, $result->price_level);
        $this->assertSame(90, $result->lower_orders_time);
        $this->assertSame(1, $result->can_card);
        $this->assertSame('VISA,JCB', $result->card_types);
        $this->assertSame(1, $result->can_digital_money);
        $this->assertSame('ID,PAYPAY', $result->digital_money_types);
        $this->assertSame(1, $result->has_private_room);
        $this->assertSame('8_PEOPLE7,10-20_PEOPLE', $result->private_room_types);
        $this->assertSame(1, $result->has_parking);
        $this->assertSame(1, $result->has_coin_parking);
        $this->assertSame(10, $result->number_of_seats);
        $this->assertSame(1, $result->can_charter);
        $this->assertSame('20-50_PEOPLE', $result->charter_types);
        $this->assertSame(0, $result->smoking);
        $this->assertSame('NO_SMOKING', $result->smoking_types);
        $this->assertSame(1, $result->area_id);
        $this->assertSame(0, $result->published);

        $this->logout();
    }

    public function testAddWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callAdd($settlementCompany->id);
        $response->assertStatus(302);                                             // リダイレクト
        $storeLists = Store::query()->count();
        $pageNumber = ($storeLists > 0) ? ceil($storeLists / 30) : 1;
        $response->assertRedirect('/admin/store?page=' . $pageNumber);          // リダイレクト先
        $response->assertSessionHas('message', '店舗「テスト店舗追加」を作成しました');

        $result = Store::where('settlement_company_id', $settlementCompany->id)->first();
        $this->assertSame('テスト店舗追加', $result->name);
        $this->assertSame('test-alias-name', $result->alias_name);
        $this->assertSame('RS', $result->app_cd);
        $this->assertSame('test_add1', $result->code);
        $this->assertSame('東京都', $result->address_1);
        $this->assertSame('新宿区', $result->address_2);
        $this->assertSame('テストタワー1F', $result->address_3);
        $this->assertSame('1234567', $result->postal_code);
        $this->assertSame('0311112222', $result->tel);
        $this->assertSame('0333334444', $result->tel_order);
        $this->assertSame(100.0, $result->latitude);
        $this->assertSame(200.0, $result->longitude);
        $this->assertSame('gourmet-teststore1@adventure-inc.co.jp', $result->email_1);
        $this->assertSame('gourmet-teststore2@adventure-inc.co.jp', $result->email_2);
        $this->assertSame('gourmet-teststore3@adventure-inc.co.jp', $result->email_3);
        $this->assertSame(1000, $result->daytime_budget_lower_limit);
        $this->assertSame(3999, $result->daytime_budget_limit);
        $this->assertSame(2000, $result->night_budget_lower_limit);
        $this->assertSame(5999, $result->night_budget_limit);
        $this->assertSame('最寄りから徒歩5分', $result->access);
        $this->assertSame('公式アカウント123457890', $result->account);
        $this->assertSame(60, $result->pick_up_time_interval);
        $this->assertSame('テスト備考', $result->remarks);
        $this->assertSame('テスト説明', $result->description);
        $this->assertSame('0355556666', $result->fax);
        $this->assertSame(1, $result->use_fax);
        $this->assertSame('111111110', $result->regular_holiday);
        $this->assertSame(1, $result->price_level);
        $this->assertSame(90, $result->lower_orders_time);
        $this->assertSame(1, $result->can_card);
        $this->assertSame('VISA,JCB', $result->card_types);
        $this->assertSame(1, $result->can_digital_money);
        $this->assertSame('ID,PAYPAY', $result->digital_money_types);
        $this->assertSame(1, $result->has_private_room);
        $this->assertSame('8_PEOPLE7,10-20_PEOPLE', $result->private_room_types);
        $this->assertSame(1, $result->has_parking);
        $this->assertSame(1, $result->has_coin_parking);
        $this->assertSame(10, $result->number_of_seats);
        $this->assertSame(1, $result->can_charter);
        $this->assertSame('20-50_PEOPLE', $result->charter_types);
        $this->assertSame(0, $result->smoking);
        $this->assertSame('NO_SMOKING', $result->smoking_types);
        $this->assertSame(1, $result->area_id);
        $this->assertSame(0, $result->published);

        $this->logout();
    }

    public function testAddWithException()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callAdd([$settlementCompany->id]);  // 配列を渡し、例外エラーを起こす
        $response->assertStatus(302);                           // リダイレクト
        $response->assertRedirect('/admin/store');              // リダイレクト先
        $response->assertSessionHas('custom_error', '店舗「テスト店舗追加」を作成できませんでした');

        // 登録されていないことを確認する
        $this->assertFalse(Store::where('settlement_company_id', $settlementCompany->id)->exists());

        $this->logout();
    }

    public function testDeleteWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callDelete($store);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 削除されていることを確認する
        $this->assertFalse(Store::where('id', $store->id)->exists());

        $this->logout();
    }

    public function testDeleteWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callDelete($store);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 削除されていることを確認する
        $this->assertFalse(Store::where('id', $store->id)->exists());

        $this->logout();
    }

    public function testDeleteWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常に処理できること
        {
            $response = $this->_callDelete($store);
            $response->assertStatus(200)->assertJson(['result' => 'ok']);

            // 削除されていることを確認する
            $this->assertFalse(Store::where('id', $store->id)->exists());
        }

        // 担当外店舗の場合、正常に処理できないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callDelete($store2);
            $response->assertStatus(403);
        }

        $this->logout();
    }

    public function testDeleteWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callDelete($store);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 削除されていることを確認する
        $this->assertFalse(Store::where('id', $store->id)->exists());

        $this->logout();
    }

    public function testSetPublishedWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callSetPublish($store);
        $response->assertStatus(302);
        $response->assertRedirect('/admin/store?page=1');          // リダイレクト先
        $response->assertSessionHas('message', '店舗「テスト店舗」を公開しました。');

        // 公開されていることを確認する
        $this->assertSame(1, Store::find($store->id)->published);

        $this->logout();
    }

    public function testSetPublishedWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callSetPublish($store);
        $response->assertStatus(302);
        $response->assertRedirect('/admin/store?page=1');          // リダイレクト先
        $response->assertSessionHas('message', '店舗「テスト店舗」を公開しました。');

        // 公開されていることを確認する
        $this->assertSame(1, Store::find($store->id)->published);

        $this->logout();
    }

    public function testSetPublishedWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        $response = $this->_callSetPublish($store);
        $response->assertStatus(302);
        $response->assertRedirect('/admin/store?page=1');          // リダイレクト先
        $response->assertSessionHas('message', '店舗「テスト店舗」を公開しました。');

        // 公開されていることを確認する
        $this->assertSame(1, Store::find($store->id)->published);

        $this->logout();
    }

    public function testSetPublishedWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callSetPublish($store);
        $response->assertStatus(302);
        $response->assertRedirect('/admin/store?page=1');          // リダイレクト先
        $response->assertSessionHas('message', '店舗「テスト店舗」を公開しました。');

        // 公開されていることを確認する
        $this->assertSame(1, Store::find($store->id)->published);

        $this->logout();
    }

    public function testSetPrivateWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callSetPrivate($store);
        $response->assertStatus(302);
        $response->assertRedirect('/admin/store?page=1');          // リダイレクト先
        $response->assertSessionHas('message', '店舗「テスト店舗」を非公開にしました。');

        // 非公開されていることを確認する
        $this->assertSame(0, Store::find($store->id)->published);

        $this->logout();
    }

    public function testSetPrivateWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callSetPrivate($store);
        $response->assertStatus(302);
        $response->assertRedirect('/admin/store?page=1');          // リダイレクト先
        $response->assertSessionHas('message', '店舗「テスト店舗」を非公開にしました。');

        // 非公開されていることを確認する
        $this->assertSame(0, Store::find($store->id)->published);

        $this->logout();
    }

    public function testSetPrivateWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        $response = $this->_callSetPrivate($store);
        $response->assertStatus(302);
        $response->assertRedirect('/admin/store?page=1');          // リダイレクト先
        $response->assertSessionHas('message', '店舗「テスト店舗」を非公開にしました。');

        // 非公開されていることを確認する
        $this->assertSame(0, Store::find($store->id)->published);

        $this->logout();
    }

    public function testSetPrivateWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callSetPrivate($store);
        $response->assertStatus(302);
        $response->assertRedirect('/admin/store?page=1');          // リダイレクト先
        $response->assertSessionHas('message', '店舗「テスト店舗」を非公開にしました。');

        // 非公開されていることを確認する
        $this->assertSame(0, Store::find($store->id)->published);

        $this->logout();
    }

    public function testStoreControllerWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(403);

        // target method add
        $response = $this->_callAdd($settlementCompanyId);
        $response->assertStatus(403);

        $this->logout();
    }

    public function testStoreControllerWithClientGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithClientGeneral($store->id, $settlementCompanyId);      // クライアント一般としてログイン

        // target method index
        $response = $this->_callIndex($store);
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($store);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($store);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($settlementCompanyId);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($store);
        $response->assertStatus(404);

        // target method setPublish
        $response = $this->_callSetPublish($store);
        $response->assertStatus(404);

        // target method setPrivate
        $response = $this->_callSetPrivate($store);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testStoreControllerWithSettlementAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithSettlementAdministrator($settlementCompany->id);    // 精算管理会社としてログイン

        // target method index
        $response = $this->_callIndex($store);
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($store);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($store);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($settlementCompany->id);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($store);
        $response->assertStatus(404);

        // target method setPublish
        $response = $this->_callSetPublish($store);
        $response->assertStatus(404);

        // target method setPrivate
        $response = $this->_callSetPrivate($store);
        $response->assertStatus(404);

        $this->logout();
    }

    private function _createSettlementCompany()
    {
        $settlementCompany = new SettlementCompany();
        $settlementCompany->name = 'testテストtest精算会社';
        $settlementCompany->tel = '0698765432';
        $settlementCompany->postal_code = '1111123';
        $settlementCompany->published = 1;
        $settlementCompany->save();

        return $settlementCompany;
    }

    private function _createStore($settlementCompanyId, $appCd = 'RS')
    {
        $store = new Store();
        $store->app_cd = $appCd;
        $store->code = 'test-code-test';
        $store->name = 'テスト店舗';
        $store->regular_holiday = '110111111';
        $store->area_id = 1;
        $store->published = 0;
        $store->settlement_company_id = $settlementCompanyId;
        $store->save();

        return $store;
    }

    private function _createMenu($storeId, $appCd = 'RS')
    {
        $menu = new Menu();
        $menu->store_id = $storeId;
        $menu->app_cd = $appCd;
        $menu->name = 'テストメニュー';
        $menu->lower_orders_time = 90;
        $menu->provided_day_of_week = '11111111';
        $menu->free_drinks = 0;
        $menu->published = 0;
        $menu->save();
        return $menu;
    }

    private function _createImage($storeId, $menu, $url, $imageCd = 'RESTAURANT_LOGO')
    {
        $menuId = null;
        if (!is_null($menu)) {
            $storeId = null;
            $imageCd = 'MENU_MAIN';
            $menuId = $menu->id;
        }

        $image = new Image();
        $image->store_id = $storeId;
        $image->menu_id = $menuId;
        $image->image_cd = $imageCd;
        $image->url = $url;
        $image->weight = 100;
        $image->save();
        return $image;
    }

    // 店舗公開用データ
    private function _createStorePublished($store)
    {
        $storeId = $store->id;

        $storeImage = $this->_createImage($storeId, null, null);
        $storeImage2 = $this->_createImage($storeId, null, null);
        $storeImage3 = $this->_createImage($storeId, null, null);

        $openigHour = new OpeningHour();
        $openigHour->store_id = $storeId;
        $openigHour->save();

        $genre = new Genre();
        $genre->level = 2;
        $genre->genre_cd = 'test2';
        $genre->published = 1;
        $genre->path = '/test';
        $genre->save();

        $genreGroup = new GenreGroup();
        $genreGroup->store_id = $storeId;
        $genreGroup->genre_id = $genre->id;
        $genreGroup->is_delegate = 1;
        $genreGroup->save();

        $cancelFee = new CancelFee();
        $cancelFee->app_cd = 'RS';
        $cancelFee->store_id = $storeId;
        $cancelFee->published = 1;
        $cancelFee->save();

        $commissionRateRs = new CommissionRate();
        $commissionRateRs->app_cd = 'RS';
        $commissionRateRs->settlement_company_id = $store->settlement_company_id;
        $commissionRateRs->apply_term_from = new Carbon('2022-10-01');
        $commissionRateRs->apply_term_to = new Carbon('2999-12-31');
        $commissionRateRs->published = 1;
        $commissionRateRs->save();
    }

    private function _callIndex()
    {
        return $this->withHeaders([
            'HTTP_REFERER' =>  url('/admin/store?page=1'),
        ])->get('/admin/store/');
    }

    private function _callEditForm($store)
    {
        return $this->withHeaders([
            'HTTP_REFERER' =>  url('/admin/store?page=1'),
        ])->get("/admin/store/{$store->id}/edit");
    }

    private function _callEdit($store, $regularHoliday = [1, 1, 1, 1, 1, 1, 1, 1, 1, null])
    {
        return $this->post("/admin/store/{$store->id}/edit", [
            'app_cd' => 'RS',
            'settlement_company_id' => $store->settlement_company_id,
            'name' => 'テスト店舗更新',
            'alias_name' => 'test-alias-name',
            'code' => 'test_edit1',
            'tel' => '0311112222',
            'use_fax' => 1,
            'tel_order' => '0333334444',
            'mobile_phone' => '07011112222',
            'fax' => '0355556666',
            'postal_code' => '1234567',
            'address_1' => '東京都',
            'address_2' => '新宿区',
            'address_3' => 'テストタワー1F',
            'email_1' => 'gourmet-teststore1@adventure-inc.co.jp',
            'email_2' => 'gourmet-teststore2@adventure-inc.co.jp',
            'email_3' => 'gourmet-teststore3@adventure-inc.co.jp',
            'access' => '最寄りから徒歩5分',
            'latitude' => 100,
            'longitude' => 200,
            'regular_holiday' => $regularHoliday,
            'smoking' => 'NO_SMOKING',
            'can_card' => 1,
            'card_types' => 'VISA,JCB',
            'can_digital_money' => 1,
            'digital_money_types' => 'ID,PAYPAY',
            'has_private_room' => 1,
            'private_room_types' => '8_PEOPLE7,10-20_PEOPLE',
            'has_parking' => 1,
            'has_coin_parking' => 1,
            'number_of_seats' => 10,
            'can_charter' => 1,
            'charter_types' => '20-50_PEOPLE',
            'smoking_types' => 'NO_SMOKING',
            'daytime_budget_lower_limit' => 1000,
            'daytime_budget_limit' => 3999,
            'night_budget_lower_limit' => 2000,
            'night_budget_limit' => 5999,
            'lower_orders_time_hour' => 1,
            'lower_orders_time_minute' => 30,
            'pick_up_time_interval' => '60',
            'account' => '公式アカウント123457890',
            'remarks' => 'テスト備考',
            'description' => 'テスト説明',
            'area_id' => 1,
            'published' => 0,
            'price_level' => 1,
            'redirect_to' => '/admin/store',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditExistsImage($store, &$storeImage, &$menuImage, &$imageName)
    {
        $menu = $this->_createMenu($store->id);
        $storeImage = $this->_uploadFile($store, null, $storeFileName);    // 店舗画像追加
        $menuImage = $this->_uploadFile($store, $menu, $menuFileName);    // 店舗画像追加
        $imageName = [$storeFileName, $menuFileName];

        return $this->_callEdit($store);
    }

    private function _callAddForm()
    {
        return $this->get("/admin/store/add");
    }

    private function _callAdd($settlementCompanyId)
    {
        return $this->post("/admin/store/add", [
            'app_cd' => 'RS',
            'settlement_company_id' => $settlementCompanyId,
            'name' => 'テスト店舗追加',
            'alias_name' => 'test-alias-name',
            'code' => 'test_add1',
            'tel' => '0311112222',
            'use_fax' => 1,
            'tel_order' => '0333334444',
            'mobile_phone' => '07011112222',
            'fax' => '0355556666',
            'postal_code' => '1234567',
            'address_1' => '東京都',
            'address_2' => '新宿区',
            'address_3' => 'テストタワー1F',
            'email_1' => 'gourmet-teststore1@adventure-inc.co.jp',
            'email_2' => 'gourmet-teststore2@adventure-inc.co.jp',
            'email_3' => 'gourmet-teststore3@adventure-inc.co.jp',
            'access' => '最寄りから徒歩5分',
            'latitude' => 100,
            'longitude' => 200,
            'regular_holiday' => [1, 1, 1, 1, 1, 1, 1, 1, 1, null],
            'smoking' => 'NO_SMOKING',
            'can_card' => 1,
            'card_types' => 'VISA,JCB',
            'can_digital_money' => 1,
            'digital_money_types' => 'ID,PAYPAY',
            'has_private_room' => 1,
            'private_room_types' => '8_PEOPLE7,10-20_PEOPLE',
            'has_parking' => 1,
            'has_coin_parking' => 1,
            'number_of_seats' => 10,
            'can_charter' => 1,
            'charter_types' => '20-50_PEOPLE',
            'smoking_types' => 'NO_SMOKING',
            'daytime_budget_lower_limit' => 1000,
            'daytime_budget_limit' => 3999,
            'night_budget_lower_limit' => 2000,
            'night_budget_limit' => 5999,
            'lower_orders_time_hour' => 1,
            'lower_orders_time_minute' => 30,
            'pick_up_time_interval' => '60',
            'account' => '公式アカウント123457890',
            'remarks' => 'テスト備考',
            'description' => 'テスト説明',
            'area_id' => 1,
            'published' => 0,
            'price_level' => 1,
            'redirect_to' => '/admin/store',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callDelete($store)
    {
        return $this->post("/admin/store/{$store->id}/delete", [
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callSetPublish($store)
    {
        $this->_createStorePublished($store);
        return $this->withHeaders([
            'HTTP_REFERER' =>  url('/admin/store?page=1'),
        ])->post("/admin/store/{$store->id}/publish", [
            'published' => 1,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callSetPrivate($store)
    {
        return $this->withHeaders([
            'HTTP_REFERER' =>  url('/admin/store?page=1'),
        ])->post("/admin/store/{$store->id}/private", [
            'published' => 0,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _uploadFile($store, $menu, &$uploadFileName)
    {
        $storeCd = $store->code;
        $target = (is_null($menu)) ? 'store' : 'menu';
        $dirPath = env('GOOGLE_CLOUD_STORAGE_IMAGE_PATH_PREFIX', '') . $storeCd . '/' . $target . '/';

        // ファイルのアップロード
        $image = UploadedFile::fake()->create('test-image.jpg'); // fakeファイルを用意
        ImageUpload::store($image, $dirPath);

        // アップロードしたファイル情報をDBに格納する
        $uploadFileName = basename($image) . '.' . $image->extension();
        $url = ImageUpload::environment() . $dirPath . $uploadFileName;
        $imageModel = $this->_createImage($store->id, $menu, $url);

        return $imageModel;
    }

    private function _deleteImage($dirPath, $fileName)
    {
        \Storage::disk('gcs')->delete($dirPath . $fileName);            // アップロードしたファイルを削除
        $checkDeleteFile = \Storage::disk('gcs')->allFiles($dirPath);   // 指定フォルダ内のファイルを全て取得
        $this->assertIsArray($checkDeleteFile);                         // 戻り値は配列である
        $this->assertCount(0, $checkDeleteFile);                        // 残っていないことを確認
    }
}
