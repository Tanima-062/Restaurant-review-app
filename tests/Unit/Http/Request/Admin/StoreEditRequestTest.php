<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StoreEditRequest;
use App\Models\Image;
use App\Models\Menu;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreEditRequestTest extends TestCase
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

    public function testAuthorize()
    {
        $request  = new StoreEditRequest();
        $this->assertTrue($request->authorize());
    }

    /**
     * バリデーションテスト
     *
     * @dataProvider dataprovider
     */
    public function testRules(array $params, bool $expected, array $messages, ?string $addMethod)
    {
        $store = $this->_createData();
        $params['id'] = $store->id;

        // 追加呼出関数の指定がある場合は、呼び出す
        if (!empty($addMethod)) {
            if ($addMethod == '_createMenuRs') {
                $this->_createMenu($store->id, 'RS');
            } elseif ($addMethod == '_createMenuTo') {
                $this->_createMenu($store->id, 'TO');
            } else {
                $this->{$addMethod}($store);
            }
        }

        // テスト実施
        $request  = new StoreEditRequest($params);
        $rules = $request->rules();
        $attributes = $request->attributes();
        $validator = Validator::make($params, $rules, [], $attributes);
        $request->withValidator($validator);                                // withValidator関数呼び出し
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testAttributes()
    {
        $request  = new StoreEditRequest();
        $result = $request->attributes();
        $this->assertCount(37, $result);
        $this->assertArrayHasKey('app_cd', $result);
        $this->assertSame('利用サービス', $result['app_cd']);
        $this->assertArrayHasKey('settlement_company_id', $result);
        $this->assertSame('精算会社名', $result['settlement_company_id']);
        $this->assertArrayHasKey('name', $result);
        $this->assertSame('店舗名', $result['name']);
        $this->assertArrayHasKey('alias_name', $result);
        $this->assertSame('店舗別名', $result['alias_name']);
        $this->assertArrayHasKey('code', $result);
        $this->assertSame('店舗コード', $result['code']);
        $this->assertArrayHasKey('tel', $result);
        $this->assertSame('店舗電話番号', $result['tel']);
        $this->assertArrayHasKey('tel_order', $result);
        $this->assertSame('予約用電話番号', $result['tel_order']);
        $this->assertArrayHasKey('mobile_phone', $result);
        $this->assertSame('携帯電話番号', $result['mobile_phone']);
        $this->assertArrayHasKey('fax', $result);
        $this->assertSame('店舗FAX番号', $result['fax']);
        $this->assertArrayHasKey('use_fax', $result);
        $this->assertSame('FAX通知', $result['use_fax']);
        $this->assertArrayHasKey('postal_code', $result);
        $this->assertSame('郵便番号', $result['postal_code']);
        $this->assertArrayHasKey('address_1', $result);
        $this->assertSame('住所1（都道府県）', $result['address_1']);
        $this->assertArrayHasKey('address_2', $result);
        $this->assertSame('住所2（市区町村）', $result['address_2']);
        $this->assertArrayHasKey('address_3', $result);
        $this->assertSame('住所3（残り）', $result['address_3']);
        $this->assertArrayHasKey('latitude', $result);
        $this->assertSame('緯度', $result['latitude']);
        $this->assertArrayHasKey('longitude', $result);
        $this->assertSame('経度', $result['longitude']);
        $this->assertArrayHasKey('email_1', $result);
        $this->assertSame('予約受付時お知らせメールアドレス1', $result['email_1']);
        $this->assertArrayHasKey('email_2', $result);
        $this->assertSame('予約受付時お知らせメールアドレス2', $result['email_2']);
        $this->assertArrayHasKey('email_3', $result);
        $this->assertSame('予約受付時お知らせメールアドレス3', $result['email_3']);
        $this->assertArrayHasKey('regular_holiday', $result);
        $this->assertSame('定休日', $result['regular_holiday']);
        $this->assertArrayHasKey('can_card', $result);
        $this->assertSame('カード', $result['can_card']);
        $this->assertArrayHasKey('card_types', $result);
        $this->assertSame('カード種類', $result['card_types']);
        $this->assertArrayHasKey('can_digital_money', $result);
        $this->assertSame('電子マネー', $result['can_digital_money']);
        $this->assertArrayHasKey('digital_money_types', $result);
        $this->assertSame('電子マネー種類', $result['digital_money_types']);
        $this->assertArrayHasKey('can_charter', $result);
        $this->assertSame('貸切', $result['can_charter']);
        $this->assertArrayHasKey('charter_types', $result);
        $this->assertSame('貸切種類', $result['charter_types']);
        $this->assertArrayHasKey('smoking_types', $result);
        $this->assertSame('喫煙・禁煙', $result['smoking_types']);
        $this->assertArrayHasKey('has_private_room', $result);
        $this->assertSame('個室', $result['has_private_room']);
        $this->assertArrayHasKey('private_room_types', $result);
        $this->assertSame('個室種類', $result['private_room_types']);
        $this->assertArrayHasKey('has_parking', $result);
        $this->assertSame('駐車場', $result['has_parking']);
        $this->assertArrayHasKey('has_coin_parking', $result);
        $this->assertSame('近隣にコインパーキング', $result['has_coin_parking']);
        $this->assertArrayHasKey('lower_orders_time_hour', $result);
        $this->assertSame('最低注文時間(時間)', $result['lower_orders_time_hour']);
        $this->assertArrayHasKey('lower_orders_time_minute', $result);
        $this->assertSame('最低注文時間(分)', $result['lower_orders_time_minute']);
        $this->assertArrayHasKey('remarks', $result);
        $this->assertSame('備考', $result['remarks']);
        $this->assertArrayHasKey('description', $result);
        $this->assertSame('説明', $result['description']);
        $this->assertArrayHasKey('number_of_seats', $result);
        $this->assertSame(' 座席数', $result['number_of_seats']);
        $this->assertArrayHasKey('area_id', $result);
        $this->assertSame('検索エリア設定', $result['area_id']);
    }

    /**
     * データプロバイダ
     *
     * @return データプロバイダ
     *
     * @dataProvider dataprovider
     */
    public function dataprovider(): array
    {
        // testRules関数で順番にテストされる
        return [
            'error-empty' => [
                // テスト条件
                [
                    'app_cd' => null,
                    'settlement_company_id' => null,
                    'name' => null,
                    'alias_name' => null,
                    'code' => null,
                    'tel' => null,
                    'tel_order' => null,
                    'mobile_phone' => null,
                    'fax' => null,
                    'use_fax' => null,
                    'postal_code' => null,
                    'address_1' => null,
                    'address_2' => null,
                    'address_3' => null,
                    'email_1' => null,
                    'email_2' => null,
                    'email_3' => null,
                    'latitude' => null,
                    'longitude' => null,
                    'regular_holiday' => null,
                    'can_card' => null,
                    'card_types' => null,
                    'can_digital_money' => null,
                    'digital_money_types' => null,
                    'has_private_room' => null,
                    'private_room_types' => null,
                    'has_parking' => null,
                    'has_coin_parking' => null,
                    'number_of_seats' => null,
                    'can_charter' => null,
                    'charter_types' => null,
                    'smoking_types' => null,
                    'lower_orders_time_hour' => null,
                    'lower_orders_time_minute' => null,
                    'remarks' => null,
                    'description' => null,
                    'area_id' => null,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'app_cd' => ['利用サービスは必ず指定してください。'],
                    'settlement_company_id' => ['精算会社名は必ず指定してください。'],
                    'name' => ['店舗名は必ず指定してください。'],
                    'code' => ['店舗コードは必ず指定してください。'],
                    'tel' => ['店舗電話番号は必ず指定してください。'],
                    'postal_code' => ['郵便番号は必ず指定してください。'],
                    'address_1' => ['住所1（都道府県）は必ず指定してください。'],
                    'address_2' => ['住所2（市区町村）は必ず指定してください。'],
                    'address_3' => ['住所3（残り）は必ず指定してください。'],
                    'email_1' => ['予約受付時お知らせメールアドレス1は必ず指定してください。'],
                    'latitude' => ['緯度は必ず指定してください。'],
                    'longitude' => ['経度は必ず指定してください。'],
                    'regular_holiday' => ['定休日は必ず指定してください。'],
                    'can_card' => ['カードは必ず指定してください。'],
                    'can_digital_money' => ['電子マネーは必ず指定してください。'],
                    'has_private_room' => ['個室は必ず指定してください。'],
                    'has_parking' => ['駐車場は必ず指定してください。'],
                    'has_coin_parking' => ['近隣にコインパーキングは必ず指定してください。'],
                    'can_charter' => ['貸切は必ず指定してください。'],
                    'smoking_types' => ['喫煙・禁煙は必ず指定してください。'],
                    'area_id' => ['検索エリア設定は必ず指定してください。'],
                    'pick_up_time_interval' => ['テイクアウト受取時間間隔は利用サービスにテイクアウトを含む場合は必須です。'],
                    'price_level' => ['テイクアウト価格帯は利用サービスにテイクアウトを含む場合は必須です。'],
                    'lower_orders_time_hour' => ['最低注文時間は利用サービスにテイクアウトを含む場合は必須です。'],
                    'number_of_seats' => ['座席数は利用サービスにレストランを含む場合は必須です。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-empty-appCd' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => null,
                    'name' => null,
                    'alias_name' => null,
                    'code' => null,
                    'tel' => null,
                    'tel_order' => null,
                    'mobile_phone' => null,
                    'fax' => null,
                    'use_fax' => null,
                    'postal_code' => null,
                    'address_1' => null,
                    'address_2' => null,
                    'address_3' => null,
                    'email_1' => null,
                    'email_2' => null,
                    'email_3' => null,
                    'latitude' => null,
                    'longitude' => null,
                    'regular_holiday' => null,
                    'can_card' => null,
                    'card_types' => null,
                    'can_digital_money' => null,
                    'digital_money_types' => null,
                    'has_private_room' => null,
                    'private_room_types' => null,
                    'has_parking' => null,
                    'has_coin_parking' => null,
                    'number_of_seats' => null,
                    'can_charter' => null,
                    'charter_types' => null,
                    'smoking_types' => null,
                    'lower_orders_time_hour' => null,
                    'lower_orders_time_minute' => null,
                    'remarks' => null,
                    'description' => null,
                    'area_id' => null,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'settlement_company_id' => ['精算会社名は必ず指定してください。'],
                    'name' => ['店舗名は必ず指定してください。'],
                    'code' => ['店舗コードは必ず指定してください。'],
                    'tel' => ['店舗電話番号は必ず指定してください。'],
                    'postal_code' => ['郵便番号は必ず指定してください。'],
                    'address_1' => ['住所1（都道府県）は必ず指定してください。'],
                    'address_2' => ['住所2（市区町村）は必ず指定してください。'],
                    'address_3' => ['住所3（残り）は必ず指定してください。'],
                    'email_1' => ['予約受付時お知らせメールアドレス1は必ず指定してください。'],
                    'latitude' => ['緯度は必ず指定してください。'],
                    'longitude' => ['経度は必ず指定してください。'],
                    'regular_holiday' => ['定休日は必ず指定してください。'],
                    'can_card' => ['カードは必ず指定してください。'],
                    'can_digital_money' => ['電子マネーは必ず指定してください。'],
                    'has_private_room' => ['個室は必ず指定してください。'],
                    'has_parking' => ['駐車場は必ず指定してください。'],
                    'has_coin_parking' => ['近隣にコインパーキングは必ず指定してください。'],
                    'can_charter' => ['貸切は必ず指定してください。'],
                    'smoking_types' => ['喫煙・禁煙は必ず指定してください。'],
                    'area_id' => ['検索エリア設定は必ず指定してください。'],
                    'number_of_seats' => ['座席数は利用サービスにレストランを含む場合は必須です。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-empty-appCd2' => [
                // テスト条件
                [
                    'app_cd' => 'TO',
                    'settlement_company_id' => null,
                    'name' => null,
                    'alias_name' => null,
                    'code' => null,
                    'tel' => null,
                    'tel_order' => null,
                    'mobile_phone' => null,
                    'fax' => null,
                    'use_fax' => null,
                    'postal_code' => null,
                    'address_1' => null,
                    'address_2' => null,
                    'address_3' => null,
                    'email_1' => null,
                    'email_2' => null,
                    'email_3' => null,
                    'latitude' => null,
                    'longitude' => null,
                    'regular_holiday' => null,
                    'can_card' => null,
                    'card_types' => null,
                    'can_digital_money' => null,
                    'digital_money_types' => null,
                    'has_private_room' => null,
                    'private_room_types' => null,
                    'has_parking' => null,
                    'has_coin_parking' => null,
                    'number_of_seats' => null,
                    'can_charter' => null,
                    'charter_types' => null,
                    'smoking_types' => null,
                    'lower_orders_time_hour' => null,
                    'lower_orders_time_minute' => null,
                    'remarks' => null,
                    'description' => null,
                    'area_id' => null,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'settlement_company_id' => ['精算会社名は必ず指定してください。'],
                    'name' => ['店舗名は必ず指定してください。'],
                    'code' => ['店舗コードは必ず指定してください。'],
                    'tel' => ['店舗電話番号は必ず指定してください。'],
                    'postal_code' => ['郵便番号は必ず指定してください。'],
                    'address_1' => ['住所1（都道府県）は必ず指定してください。'],
                    'address_2' => ['住所2（市区町村）は必ず指定してください。'],
                    'address_3' => ['住所3（残り）は必ず指定してください。'],
                    'email_1' => ['予約受付時お知らせメールアドレス1は必ず指定してください。'],
                    'latitude' => ['緯度は必ず指定してください。'],
                    'longitude' => ['経度は必ず指定してください。'],
                    'regular_holiday' => ['定休日は必ず指定してください。'],
                    'can_card' => ['カードは必ず指定してください。'],
                    'can_digital_money' => ['電子マネーは必ず指定してください。'],
                    'has_private_room' => ['個室は必ず指定してください。'],
                    'has_parking' => ['駐車場は必ず指定してください。'],
                    'has_coin_parking' => ['近隣にコインパーキングは必ず指定してください。'],
                    'can_charter' => ['貸切は必ず指定してください。'],
                    'smoking_types' => ['喫煙・禁煙は必ず指定してください。'],
                    'area_id' => ['検索エリア設定は必ず指定してください。'],
                    'pick_up_time_interval' => ['テイクアウト受取時間間隔は利用サービスにテイクアウトを含む場合は必須です。'],
                    'price_level' => ['テイクアウト価格帯は利用サービスにテイクアウトを含む場合は必須です。'],
                    'lower_orders_time_hour' => ['最低注文時間は利用サービスにテイクアウトを含む場合は必須です。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'error-notStirng' => [
                // テスト条件
                [
                    'app_cd' => 111,
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 1,
                    'code' => 'test_add1',
                    'tel' => '0311112222',
                    'use_fax' => 1,
                    'tel_order' => '0333334444',
                    'mobile_phone' => '07011112222',
                    'fax' => '0355556666',
                    'postal_code' => '1234567',
                    'address_1' => '東京都',
                    'address_2' => '新宿区',
                    'address_3' => 1,
                    'email_1' => 'gourmet-teststore1@adventure-inc.co.jp',
                    'email_2' => 'gourmet-teststore2@adventure-inc.co.jp',
                    'email_3' => 'gourmet-teststore3@adventure-inc.co.jp',
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'app_cd' => ['利用サービスは文字列を指定してください。'],
                    'alias_name' => ['店舗別名は文字列を指定してください。'],
                    'address_3' => ['住所3（残り）は文字列を指定してください。'],
                    'pick_up_time_interval' => ['テイクアウト受取時間間隔は利用サービスにテイクアウトを含む場合は必須です。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-belowMinimum' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
                    'code' => 'test_add1',
                    'tel' => '012345678',                 // 9桁、min:10
                    'use_fax' => 1,
                    'tel_order' => '012345678',           // 9桁、min:10
                    'mobile_phone' => '012345678',        // 9桁、min:10
                    'fax' => '012345678',                 // 9桁、min:10
                    'postal_code' => '1234567',
                    'address_1' => '東京都',
                    'address_2' => '新宿区',
                    'address_3' => 'テストタワー1F',
                    'email_1' => 'gourmet-teststore1@adventure-inc.co.jp',
                    'email_2' => 'gourmet-teststore2@adventure-inc.co.jp',
                    'email_3' => 'gourmet-teststore3@adventure-inc.co.jp',
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'tel' => ['店舗電話番号は、10文字以上で指定してください。'],
                    'tel_order' => ['予約用電話番号は、10文字以上で指定してください。'],
                    'mobile_phone' => ['携帯電話番号は、10文字以上で指定してください。'],
                    'fax' => ['店舗FAX番号は、10文字以上で指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'success-minimum' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
                    'code' => 'test_add1',
                    'tel' => '0612345678',                 // min:10
                    'use_fax' => 1,
                    'tel_order' => '0612345678',           // min:10
                    'mobile_phone' => '0612345678',        // min:10
                    'fax' => '0612345678',                 // min:10
                    'postal_code' => '1234567',
                    'address_1' => '東京都',
                    'address_2' => '新宿区',
                    'address_3' => 'テストタワー1F',
                    'email_1' => 'gourmet-teststore1@adventure-inc.co.jp',
                    'email_2' => 'gourmet-teststore2@adventure-inc.co.jp',
                    'email_3' => 'gourmet-teststore3@adventure-inc.co.jp',
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'success-maximum' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => Str::random(128),             // max:128
                    'alias_name' => Str::random(128),       // max:128
                    'code' => str_repeat('abz45678', 8),    // max:64
                    'tel' => '0120-345-6789',               // max:13
                    'use_fax' => 1,
                    'tel_order' => '0120-345-6789',         // max:13
                    'mobile_phone' => '0120-345-6789',      // max:13
                    'fax' => '0120-345-6789',               // max:13
                    'postal_code' => '1234567',
                    'address_1' => '東京都',
                    'address_2' => '新宿区',
                    'address_3' => 'テストタワー1F',
                    'email_1' => 'gourmet-teststore1@adventure-inc.co.jp',
                    'email_2' => 'gourmet-teststore2@adventure-inc.co.jp',
                    'email_3' => 'gourmet-teststore3@adventure-inc.co.jp',
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => Str::random(200),          // max:200
                    'description' => Str::random(80),       // max:80
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'error-overMaximum' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => Str::random(129),             // 129桁、max:128
                    'alias_name' => Str::random(129),       // 129桁、max:128
                    'code' => Str::random(65),              // 65桁、max:64
                    'tel' => '0120-345-67890',              // 14桁、max:13
                    'use_fax' => 1,
                    'tel_order' => '0120-345-67890',        // 14桁、max:13
                    'mobile_phone' => '0120-345-67890',     // 14桁、max:13
                    'fax' => '0120-345-67890',              // 14桁、max:13
                    'postal_code' => '1234567',
                    'address_1' => '東京都',
                    'address_2' => '新宿区',
                    'address_3' => 'テストタワー1F',
                    'email_1' => 'gourmet-teststore1@adventure-inc.co.jp',
                    'email_2' => 'gourmet-teststore2@adventure-inc.co.jp',
                    'email_3' => 'gourmet-teststore3@adventure-inc.co.jp',
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => Str::random(201),          // 201桁、max:200
                    'description' => Str::random(81),       // 81桁、max:80
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'name' => ['店舗名は、128文字以下で指定してください。'],
                    'alias_name' => ['店舗別名は、128文字以下で指定してください。'],
                    'code' => [
                        '店舗コードに正しい形式を指定してください。',
                        '店舗コードは、64文字以下で指定してください。',
                    ],
                    'tel' => [
                        '店舗電話番号に正しい形式を指定してください。',
                        '店舗電話番号は、13文字以下で指定してください。'
                    ],
                    'tel_order' => [
                        '予約用電話番号に正しい形式を指定してください。',
                        '予約用電話番号は、13文字以下で指定してください。'
                    ],
                    'mobile_phone' => [
                        '携帯電話番号に正しい形式を指定してください。',
                        '携帯電話番号は、13文字以下で指定してください。'
                    ],
                    'fax' => [
                        '店舗FAX番号に正しい形式を指定してください。',
                        '店舗FAX番号は、13文字以下で指定してください。'
                    ],
                    'remarks' => ['備考は、200文字以下で入力して下さい。'],
                    'description' => ['説明は、80文字以下で入力して下さい。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notRegex-aliasName,code' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト　追加店舗 ',          // 全角スペースが含まれる
                    'alias_name' => 'テスト　追加店舗',     // 全角スペースが含まれる
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'name' => ['店舗名に正しい形式を指定してください。'],
                    'alias_name' => ['店舗別名に正しい形式を指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notRegex-notTel' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗 ',
                    'alias_name' => 'テスト追加店舗',
                    'code' => 'test_add1',
                    'tel' => '０３１１１１２２２２',                // 全角数字
                    'use_fax' => 1,
                    'tel_order' => '０３１１１１２２２２',          // 全角数字
                    'mobile_phone' => '０３１１１１２２２２',       // 全角数字
                    'fax' => '０３１１１１２２２２',                // 全角数字
                    'postal_code' => '1234567',
                    'address_1' => '東京都',
                    'address_2' => '新宿区',
                    'address_3' => 'テストタワー1F',
                    'email_1' => 'gourmet-teststore1@adventure-inc.co.jp',
                    'email_2' => 'gourmet-teststore2@adventure-inc.co.jp',
                    'email_3' => 'gourmet-teststore3@adventure-inc.co.jp',
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'tel' => ['店舗電話番号に正しい形式を指定してください。'],
                    'tel_order' => ['予約用電話番号に正しい形式を指定してください。'],
                    'mobile_phone' => ['携帯電話番号に正しい形式を指定してください。'],
                    'fax' => ['店舗FAX番号に正しい形式を指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notRegex-notTel2' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗 ',
                    'alias_name' => 'テスト追加店舗',
                    'code' => 'test_add1',
                    'tel' => 'abcdefg1234',                 // 半角英数字
                    'use_fax' => 1,
                    'tel_order' => 'abcdefg1234',           // 半角英数字
                    'mobile_phone' => 'abcdefg1234',        // 半角英数字
                    'fax' => 'abcdefg123',                  // 半角英数字
                    'postal_code' => '1234567',
                    'address_1' => '東京都',
                    'address_2' => '新宿区',
                    'address_3' => 'テストタワー1F',
                    'email_1' => 'gourmet-teststore1@adventure-inc.co.jp',
                    'email_2' => 'gourmet-teststore2@adventure-inc.co.jp',
                    'email_3' => 'gourmet-teststore3@adventure-inc.co.jp',
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'tel' => ['店舗電話番号に正しい形式を指定してください。'],
                    'tel_order' => ['予約用電話番号に正しい形式を指定してください。'],
                    'mobile_phone' => ['携帯電話番号に正しい形式を指定してください。'],
                    'fax' => ['店舗FAX番号に正しい形式を指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notRegex-notTel3' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗 ',
                    'alias_name' => 'テスト追加店舗',
                    'code' => 'test_add1',
                    'tel' => 'あいうえおアイウエオ',            // 全角文字
                    'use_fax' => 1,
                    'tel_order' => 'あいうえおアイウエオ',      // 全角文字
                    'mobile_phone' => 'あいうえおアイウエオ',   // 全角文字
                    'fax' => 'あいうえおアイウエオ',            // 全角文字
                    'postal_code' => '1234567',
                    'address_1' => '東京都',
                    'address_2' => '新宿区',
                    'address_3' => 'テストタワー1F',
                    'email_1' => 'gourmet-teststore1@adventure-inc.co.jp',
                    'email_2' => 'gourmet-teststore2@adventure-inc.co.jp',
                    'email_3' => 'gourmet-teststore3@adventure-inc.co.jp',
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'tel' => ['店舗電話番号に正しい形式を指定してください。'],
                    'tel_order' => ['予約用電話番号に正しい形式を指定してください。'],
                    'mobile_phone' => ['携帯電話番号に正しい形式を指定してください。'],
                    'fax' => ['店舗FAX番号に正しい形式を指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'success-Regex-Tel' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗 ',
                    'alias_name' => 'テスト追加店舗',
                    'code' => 'test_add1',
                    'tel' => '0120-123-4567',            // ハイフンあり
                    'use_fax' => 1,
                    'tel_order' => '0120-123-4567',      // ハイフンあり
                    'mobile_phone' => '0120-123-4567',   // ハイフンあり
                    'fax' => '0120-123-4567',            // ハイフンあり
                    'postal_code' => '1234567',
                    'address_1' => '東京都',
                    'address_2' => '新宿区',
                    'address_3' => 'テストタワー1F',
                    'email_1' => 'gourmet-teststore1@adventure-inc.co.jp',
                    'email_2' => 'gourmet-teststore2@adventure-inc.co.jp',
                    'email_3' => 'gourmet-teststore3@adventure-inc.co.jp',
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'error-notRegex-postCode' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
                    'code' => 'test_add1',
                    'tel' => '0311112222',
                    'use_fax' => 1,
                    'tel_order' => '0333334444',
                    'mobile_phone' => '07011112222',
                    'fax' => '0355556666',
                    'postal_code' => '１２３４５６７',      // 全角数字
                    'address_1' => '東京都',
                    'address_2' => '新宿区',
                    'address_3' => 'テストタワー1F',
                    'email_1' => 'gourmet-teststore1@adventure-inc.co.jp',
                    'email_2' => 'gourmet-teststore2@adventure-inc.co.jp',
                    'email_3' => 'gourmet-teststore3@adventure-inc.co.jp',
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'postal_code' => ['郵便番号に正しい形式を指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notRegex-postCode2' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
                    'code' => 'test_add1',
                    'tel' => '0311112222',
                    'use_fax' => 1,
                    'tel_order' => '0333334444',
                    'mobile_phone' => '07011112222',
                    'fax' => '0355556666',
                    'postal_code' => '123-4567',      // ハイフンあり
                    'address_1' => '東京都',
                    'address_2' => '新宿区',
                    'address_3' => 'テストタワー1F',
                    'email_1' => 'gourmet-teststore1@adventure-inc.co.jp',
                    'email_2' => 'gourmet-teststore2@adventure-inc.co.jp',
                    'email_3' => 'gourmet-teststore3@adventure-inc.co.jp',
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'postal_code' => ['郵便番号に正しい形式を指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notRegex-postCode3' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
                    'code' => 'test_add1',
                    'tel' => '0311112222',
                    'use_fax' => 1,
                    'tel_order' => '0333334444',
                    'mobile_phone' => '07011112222',
                    'fax' => '0355556666',
                    'postal_code' => 'あいうえおかき',      // 全角文字
                    'address_1' => '東京都',
                    'address_2' => '新宿区',
                    'address_3' => 'テストタワー1F',
                    'email_1' => 'gourmet-teststore1@adventure-inc.co.jp',
                    'email_2' => 'gourmet-teststore2@adventure-inc.co.jp',
                    'email_3' => 'gourmet-teststore3@adventure-inc.co.jp',
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'postal_code' => ['郵便番号に正しい形式を指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notRegex-address' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
                    'code' => 'test_add1',
                    'tel' => '0311112222',
                    'use_fax' => 1,
                    'tel_order' => '0333334444',
                    'mobile_phone' => '07011112222',
                    'fax' => '0355556666',
                    'postal_code' => '1234567',
                    'address_1' => '１２３４５',              // 全角数字
                    'address_2' => '１２３４５',              // 全角数字
                    'address_3' => 'テストタワー1F',
                    'email_1' => 'gourmet-teststore1@adventure-inc.co.jp',
                    'email_2' => 'gourmet-teststore2@adventure-inc.co.jp',
                    'email_3' => 'gourmet-teststore3@adventure-inc.co.jp',
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'address_1' => ['住所1（都道府県）に正しい形式を指定してください。'],
                    'address_2' => ['住所2（市区町村）に正しい形式を指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notRegex-email' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'email_1' => 'gourmet-teststore1adventure-inc.co.jp',
                    'email_2' => 'gourmet-teststore2adventure-inc.co.jp',
                    'email_3' => 'gourmet-teststore3adventure-inc.co.jp',
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'email_1' => ['予約受付時お知らせメールアドレス1に正しい形式を指定してください。'],
                    'email_2' => ['予約受付時お知らせメールアドレス2に正しい形式を指定してください。'],
                    'email_3' => ['予約受付時お知らせメールアドレス3に正しい形式を指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notRegex-email2' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'email_1' => 'gourmet-teststore1＠adventure-inc.co.jp', // 全角文字混じり（@が全角)
                    'email_2' => 'gourmet-teststore2＠adventure-inc.co.jp', // 全角文字混じり（@が全角)
                    'email_3' => 'gourmet-teststore3＠adventure-inc.co.jp', // 全角文字混じり（@が全角)
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'email_1' => ['予約受付時お知らせメールアドレス1に正しい形式を指定してください。'],
                    'email_2' => ['予約受付時お知らせメールアドレス2に正しい形式を指定してください。'],
                    'email_3' => ['予約受付時お知らせメールアドレス3に正しい形式を指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notRegex-email3' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'email_1' => '.gourmet-teststore1@adventure-inc.co.jp', // .（ドット）始まり
                    'email_2' => '.gourmet-teststore2@adventure-inc.co.jp', // .（ドット）始まり
                    'email_3' => '.gourmet-teststore3@adventure-inc.co.jp', // .（ドット）始まり
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'email_1' => ['予約受付時お知らせメールアドレス1に正しい形式を指定してください。'],
                    'email_2' => ['予約受付時お知らせメールアドレス2に正しい形式を指定してください。'],
                    'email_3' => ['予約受付時お知らせメールアドレス3に正しい形式を指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notNumeric-Integer' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => '１２３４５',                     // 全角数字
                    'longitude' => '１２３４５',                    // 全角数字
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => '１２３４５',              // 全角数字
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => '１２３４５',       // 全角数字
                    'lower_orders_time_minute' => '１２３４５',     // 全角数字
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => '１２３４５',                      // 全角数字
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'latitude' => ['緯度には、数字を指定してください。'],
                    'longitude' => ['経度には、数字を指定してください。'],
                    'number_of_seats' => [' 座席数は整数で指定してください。'],
                    'lower_orders_time_hour' => ['最低注文時間(時間)は整数で指定してください。'],
                    'lower_orders_time_minute' => ['最低注文時間(分)は整数で指定してください。'],
                    'area_id' => ['検索エリア設定は整数で指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notNumeric-Integer2' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => 'あああああ',                     // 全角文字
                    'longitude' => 'いいいいい',                    // 全角文字
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 'ううううう',              // 全角文字
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 'えええええ',       // 全角文字
                    'lower_orders_time_minute' => 'おおおおお',     // 全角文字
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 'かかかかか',                      // 全角文字
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'latitude' => ['緯度には、数字を指定してください。'],
                    'longitude' => ['経度には、数字を指定してください。'],
                    'number_of_seats' => [' 座席数は整数で指定してください。'],
                    'lower_orders_time_hour' => ['最低注文時間(時間)は整数で指定してください。'],
                    'lower_orders_time_minute' => ['最低注文時間(分)は整数で指定してください。'],
                    'area_id' => ['検索エリア設定は整数で指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notNumeric-Integer3' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => 'aaaaa',                     // 半角文字
                    'longitude' => 'bbbbb',                    // 半角文字
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 'ccccc',              // 半角文字
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 'ddddd',       // 半角文字
                    'lower_orders_time_minute' => 'eeeee',     // 半角文字
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 'fffff',                      // 半角文字
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'latitude' => ['緯度には、数字を指定してください。'],
                    'longitude' => ['経度には、数字を指定してください。'],
                    'number_of_seats' => [' 座席数は整数で指定してください。'],
                    'lower_orders_time_hour' => ['最低注文時間(時間)は整数で指定してください。'],
                    'lower_orders_time_minute' => ['最低注文時間(分)は整数で指定してください。'],
                    'area_id' => ['検索エリア設定は整数で指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'success-numeric-integer' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => 10.1,                     // numeric
                    'longitude' => 11.1,                    // numeric
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,                 // integer
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 24,         // integer
                    'lower_orders_time_minute' => 30,       // integer
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 10,                        // integer
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'error-notRequiredIf' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => null,               // required_if:can_card,1
                    'can_digital_money' => 1,
                    'digital_money_types' => null,      // required_if:can_digital_money,1
                    'has_private_room' => 1,
                    'private_room_types' => null,       // required_if:has_private_room,1
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => null,            // required_if:can_charter,1
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'card_types' => ['カードがありの場合は、カード種類も指定してください。'],
                    'digital_money_types' => ['電子マネーがありの場合は、電子マネー種類も指定してください。'],
                    'private_room_types' => ['個室がありの場合は、個室種類も指定してください。'],
                    'charter_types' => ['貸切がありの場合は、貸切種類も指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'success-requiredIf' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => '0',
                    'card_types' => null,               // required_if:can_card,1
                    'can_digital_money' => '0',
                    'digital_money_types' => null,      // required_if:can_digital_money,1
                    'has_private_room' => '0',
                    'private_room_types' => null,       // required_if:has_private_room,1
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => '0',
                    'charter_types' => null,            // required_if:can_charter,1
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'error-notUniqueCode' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
                    'code' => 'testtesttest456',              // 重複あり
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'code' => ['店舗コードの値は既に存在しています。'],
                ],
                // 追加呼び出し関数
                '_createStore',
            ],
            'success-uniqueCode' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
                    'code' => 'testtesttest789',              // 重複あるが、削除データのため、問題なし
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数f
                '_createStore2',
            ],
            'error-withValidator-belowBugedMinimum' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                    'daytime_budget_lower_limit' => 1500,       // 予算下限値
                    'daytime_budget_limit' => 1000,             // 予算上限値
                    'night_budget_lower_limit' => 1500,         // 予算下限値
                    'night_budget_limit' => 1000,               // 予算上限値
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'daytime_budget_lower_limit' => ['予算上限（昼）は、予算下限（昼）より大きい額を指定してください'],
                    'night_budget_lower_limit' => ['予算上限（夜）は、予算下限（夜）より大きい額を指定してください'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-withValidator-belowBugedMinimum2' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                    'daytime_budget_lower_limit' => 1000,       // 予算下限値
                    'daytime_budget_limit' => 1000,             // 予算上限値
                    'night_budget_lower_limit' => 1500,         // 予算下限値
                    'night_budget_limit' => 1500,               // 予算上限値
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'daytime_budget_lower_limit' => ['予算上限（昼）は、予算下限（昼）より大きい額を指定してください'],
                    'night_budget_lower_limit' => ['予算上限（夜）は、予算下限（夜）より大きい額を指定してください'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'success-withValidator-bugedMinimum' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                    'daytime_budget_lower_limit' => 1500,       // 予算下限値
                    'daytime_budget_limit' => 1501,             // 予算上限値
                    'night_budget_lower_limit' => 1500,         // 予算下限値
                    'night_budget_limit' => 1501,               // 予算上限値
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'error-withValidator-notUseFax' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
                    'code' => 'test_add1',
                    'tel' => '0311112222',
                    'use_fax' => null,                  // fax通知がnull
                    'tel_order' => '0333334444',
                    'mobile_phone' => '07011112222',
                    'fax' => '0355556666',              // fax番号あり
                    'postal_code' => '1234567',
                    'address_1' => '東京都',
                    'address_2' => '新宿区',
                    'address_3' => 'テストタワー1F',
                    'email_1' => 'gourmet-teststore1@adventure-inc.co.jp',
                    'email_2' => 'gourmet-teststore2@adventure-inc.co.jp',
                    'email_3' => 'gourmet-teststore3@adventure-inc.co.jp',
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'use_fax' => ['FAXを入力された場合は、FAX通知も選択してください'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-withValidator-notFax' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
                    'code' => 'test_add1',
                    'tel' => '0311112222',
                    'use_fax' => '1',                   // fax通知あり
                    'tel_order' => '0333334444',
                    'mobile_phone' => '07011112222',
                    'fax' => null,                      // fax番号がnull
                    'postal_code' => '1234567',
                    'address_1' => '東京都',
                    'address_2' => '新宿区',
                    'address_3' => 'テストタワー1F',
                    'email_1' => 'gourmet-teststore1@adventure-inc.co.jp',
                    'email_2' => 'gourmet-teststore2@adventure-inc.co.jp',
                    'email_3' => 'gourmet-teststore3@adventure-inc.co.jp',
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'fax' => ['FAX通知を「必要あり」に選択された場合は、店舗FAX番号を入力してください'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-withValidator-code' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
                    'code' => 'teststore123',
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'code' => ['「store」、「menu」が含む店舗コードもしくは、「story」という店舗コードでの登録はできません'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-withValidator-code2' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
                    'code' => 'testmenu123',
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'code' => ['「store」、「menu」が含む店舗コードもしくは、「story」という店舗コードでの登録はできません'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-withValidator-code3' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
                    'code' => 'story',
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'code' => ['「store」、「menu」が含む店舗コードもしくは、「story」という店舗コードでの登録はできません'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-withValidator-appCd' => [
                // テスト条件
                [
                    'app_cd' => 'TO',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'app_cd' => ['すでにレストランのメニューが登録されています。利用サービスをテイクアウトのみに変更する際は、レストランメニューを削除してください。'],
                    'pick_up_time_interval' => ['テイクアウト受取時間間隔は利用サービスにテイクアウトを含む場合は必須です。'],
                ],
                // 追加呼び出し関数
                '_createMenuRs',
            ],
            'error-withValidator-appCd2' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'app_cd' => ['すでにテイクアウトのメニューが登録されています。利用サービスをレストランのみに変更する際は、テイクアウトメニューを削除してください。'],
                ],
                // 追加呼び出し関数
                '_createMenuTo',
            ],
            'error-withValidator-numberOfLines' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 0,
                    'price_level' => 1,
                    'access' => "テスト\r\nテスト\r\nテスト\r\nテスト",                                                                 // 4行,max:3行
                    'account' => "テスト\r\nテスト\r\nテスト\r\nテスト\r\nテスト\r\nテスト\r\nテスト\r\nテスト\r\nテスト\r\nテスト\r\nテスト", // 11行,max:10行
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'access' => ['交通手段は3行以内で入力して下さい。'],
                    'account' => ['公式アカウントは10行以内で入力して下さい。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-withValidator-publishedRs' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 1,               // 店舗公開設定
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        '店舗「テスト店舗123」を正しく公開するには下記の設定をするか、非公開にしてから変更してください。',
                        '利用サービスにてレストランを設定している場合は、画像設定から先にレストランロゴを設定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_chengeStorePublished',        // 店舗情報を公開に変更
            ],
            'success-withValidator-publishedRs' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 1,               // 店舗公開設定
                    'price_level' => 1,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                '_createStoreImageRs',              // 店舗情報を公開に変更、画像情報を追加
            ],
            'error-withValidator-publishedTo' => [
                // テスト条件
                [
                    'app_cd' => 'TO',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    // 'lower_orders_time_hour' => 1,               // テイクアウト設定に必要な設定を未設定にする
                    // 'lower_orders_time_minute' => 30,            // テイクアウト設定に必要な設定を未設定にする
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 1,                               // 店舗公開設定
                    // 'price_level' => 1,                          // テイクアウト設定に必要な設定を未設定にする
                    // 'pick_up_time_interval' => 60,               // テイクアウト設定に必要な設定を未設定にする
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'pick_up_time_interval' => ['テイクアウト受取時間間隔は利用サービスにテイクアウトを含む場合は必須です。'],
                    'price_level' => ['テイクアウト価格帯は利用サービスにテイクアウトを含む場合は必須です。'],
                    'lower_orders_time_hour' => ['最低注文時間は利用サービスにテイクアウトを含む場合は必須です。'],
                    'published' => [
                        '店舗「テスト店舗123」を正しく公開するには下記の設定をするか、非公開にしてから変更してください。',
                        '利用サービスにてテイクアウトを設定している場合は、画像設定から先にフードロゴを設定してください。',
                        '店舗のテイクアウト価格帯を設定してください。',
                        '店舗のテイクアウト受取時間間隔を設定してください。',
                        '店舗の最低注文時間を設定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_chengeStorePublished',                            // 店舗情報を公開に変更
            ],
            'error-withValidator-publishedTo-setImage' => [
                // テスト条件
                [
                    'app_cd' => 'TO',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    // 'lower_orders_time_hour' => 1,               // テイクアウト設定に必要な設定を未設定にする
                    // 'lower_orders_time_minute' => 30,            // テイクアウト設定に必要な設定を未設定にする
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 1,                               // 店舗公開設定
                    // 'price_level' => 1,                          // テイクアウト設定に必要な設定を未設定にする
                    // 'pick_up_time_interval' => 60,               // テイクアウト設定に必要な設定を未設定にする
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'pick_up_time_interval' => ['テイクアウト受取時間間隔は利用サービスにテイクアウトを含む場合は必須です。'],
                    'price_level' => ['テイクアウト価格帯は利用サービスにテイクアウトを含む場合は必須です。'],
                    'lower_orders_time_hour' => ['最低注文時間は利用サービスにテイクアウトを含む場合は必須です。'],
                    'published' => [
                        '店舗「テスト店舗123」を正しく公開するには下記の設定をするか、非公開にしてから変更してください。',
                        '店舗のテイクアウト価格帯を設定してください。',
                        '店舗のテイクアウト受取時間間隔を設定してください。',
                        '店舗の最低注文時間を設定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_createStoreImageTo',                              // 店舗情報を公開に変更、画像情報を追加
            ],
            'error-withValidator-publishedTo-setPriceLevel' => [
                // テスト条件
                [
                    'app_cd' => 'TO',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    // 'lower_orders_time_hour' => 1,               // テイクアウト設定に必要な設定を未設定にする
                    // 'lower_orders_time_minute' => 30,            // テイクアウト設定に必要な設定を未設定にする
                    'area_id' => 1,
                    'published' => 1,                               // 店舗公開設定
                    'price_level' => 1,                             // price_levelを設定
                    // 'pick_up_time_interval' => 60,               // テイクアウト設定に必要な設定を未設定にする
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'pick_up_time_interval' => ['テイクアウト受取時間間隔は利用サービスにテイクアウトを含む場合は必須です。'],
                    'lower_orders_time_hour' => ['最低注文時間は利用サービスにテイクアウトを含む場合は必須です。'],
                    'published' => [
                        '店舗「テスト店舗123」を正しく公開するには下記の設定をするか、非公開にしてから変更してください。',
                        '利用サービスにてテイクアウトを設定している場合は、画像設定から先にフードロゴを設定してください。',
                        '店舗のテイクアウト受取時間間隔を設定してください。',
                        '店舗の最低注文時間を設定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_chengeStorePublished',                            // 店舗情報を公開に変更
            ],
            'error-withValidator-publishedTo-setPickUpTimeInterval' => [
                // テスト条件
                [
                    'app_cd' => 'TO',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    // 'lower_orders_time_hour' => 1,               // テイクアウト設定に必要な設定を未設定にする
                    // 'lower_orders_time_minute' => 30,            // テイクアウト設定に必要な設定を未設定にする
                    'area_id' => 1,
                    'published' => 1,                               // 店舗公開設定
                    // 'price_level' => 1,                          // テイクアウト設定に必要な設定を未設定にする
                    'pick_up_time_interval' => 60,                  // pick_up_time_intervalを設定
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'price_level' => ['テイクアウト価格帯は利用サービスにテイクアウトを含む場合は必須です。'],
                    'lower_orders_time_hour' => ['最低注文時間は利用サービスにテイクアウトを含む場合は必須です。'],
                    'published' => [
                        '店舗「テスト店舗123」を正しく公開するには下記の設定をするか、非公開にしてから変更してください。',
                        '利用サービスにてテイクアウトを設定している場合は、画像設定から先にフードロゴを設定してください。',
                        '店舗のテイクアウト価格帯を設定してください。',
                        '店舗の最低注文時間を設定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_chengeStorePublished',                            // 店舗情報を公開に変更
            ],
            'error-withValidator-publishedTo-setLowerOrdersTimeHour' => [
                // テスト条件
                [
                    'app_cd' => 'TO',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,                  // 最低注文時間を設定
                    'lower_orders_time_minute' => 30,               // 最低注文時間を設定
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 1,                               // 店舗公開設定
                    // 'price_level' => 1,                          // テイクアウト設定に必要な設定を未設定にする
                    // 'pick_up_time_interval' => 60,               // テイクアウト設定に必要な設定を未設定にする
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'pick_up_time_interval' => ['テイクアウト受取時間間隔は利用サービスにテイクアウトを含む場合は必須です。'],
                    'price_level' => ['テイクアウト価格帯は利用サービスにテイクアウトを含む場合は必須です。'],
                    'published' => [
                        '店舗「テスト店舗123」を正しく公開するには下記の設定をするか、非公開にしてから変更してください。',
                        '利用サービスにてテイクアウトを設定している場合は、画像設定から先にフードロゴを設定してください。',
                        '店舗のテイクアウト価格帯を設定してください。',
                        '店舗のテイクアウト受取時間間隔を設定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_chengeStorePublished',                            // 店舗情報を公開に変更
            ],
            'success-withValidator-publishedTo' => [
                // テスト条件
                [
                    'app_cd' => 'TO',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,                  // 最低注文時間を設定
                    'lower_orders_time_minute' => 30,               // 最低注文時間を設定
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => 1,
                    'published' => 1,                               // 店舗公開設定
                    'price_level' => 1,                             // price_levelを設定
                    'pick_up_time_interval' => 60,                  // pick_up_time_intervalを設定
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                '_createStoreImageTo',                              // 店舗情報を公開に変更、画像情報を追加
            ],
            'error-withValidator-publiched-areaId' => [
                // テスト条件
                [
                    'app_cd' => 'RS',
                    'settlement_company_id' => 1,
                    'name' => 'テスト追加店舗',
                    'alias_name' => 'テスト追加店舗',
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
                    'latitude' => 100,
                    'longitude' => 200,
                    'regular_holiday' => '11111111',
                    'can_card' => 1,
                    'card_types' => 'JCB',
                    'can_digital_money' => 1,
                    'digital_money_types' => 'ID',
                    'has_private_room' => 1,
                    'private_room_types' => '10-20_PEOPLE',
                    'has_parking' => 1,
                    'has_coin_parking' => 1,
                    'number_of_seats' => 10,
                    'can_charter' => 1,
                    'charter_types' => '20-50_PEOPLE',
                    'smoking_types' => 'ALL_OK',
                    'lower_orders_time_hour' => 1,
                    'lower_orders_time_minute' => 30,
                    'remarks' => 'テスト備考',
                    'description' => 'テスト説明',
                    'area_id' => null,                              // エリアIDをnullに設定
                    'published' => 0,
                    'price_level' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'area_id' => ['検索エリア設定は必ず指定してください。'],
                    'published' => [
                        '店舗「テスト店舗123」を正しく公開するには下記の設定をするか、非公開にしてから変更してください。',
                        '店舗の検索エリアの設定をしてください。',
                    ],
                ],
                // 追加呼び出し関数
                '_createStoreImageRs',                              // 店舗情報を公開に変更、画像情報を追加
            ],
        ];
    }

    private function _createData()
    {
        $store = new Store();
        $store->app_cd = 'RS';
        $store->code = 'testtesttest123';
        $store->name = 'テスト店舗123';
        $store->published = 0;
        $store->save();

        return $store;
    }

    private function _createStore()
    {
        $store = new Store();
        $store->app_cd = 'RS';
        $store->code = 'testtesttest456';
        $store->name = 'テスト店舗456';
        $store->published = 0;
        $store->deleted_at = null;
        $store->save();
    }

    private function _createStore2()
    {
        $store = new Store();
        $store->app_cd = 'RS';
        $store->code = 'testtesttest789';
        $store->name = 'テスト店舗789';
        $store->published = 0;
        $store->deleted_at = '2022-10-01 10:00:00';
        $store->save();
    }

    private function _createMenu($storeId, $appCd)
    {
        $menu = new Menu();
        $menu->app_cd = $appCd;
        $menu->store_id = $storeId;
        $menu->save();
    }

    private function _chengeStorePublished($store)
    {
        Store::find($store->id)->update(['published' => 1]);
    }

    private function _createStoreImageRs($store)
    {
        $this->_chengeStorePublished($store);

        $image = new Image();
        $image->store_id = $store->id;
        $image->image_cd = 'RESTAURANT_LOGO';
        $image->weight = 100;
        $image->save();
    }

    private function _createStoreImageTo($store)
    {
        $this->_chengeStorePublished($store);

        $image = new Image();
        $image->store_id = $store->id;
        $image->image_cd = 'FOOD_LOGO';
        $image->weight = 100;
        $image->save();
    }
}
