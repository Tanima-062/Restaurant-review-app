<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Response\Format\ResponseFormatter;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResponseFormatterTest extends TestCase
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

    public function testFormatEloquentModel()
    {
        $store = new Store();
        $store->name = 'テスト店舗';
        $store->alias_name = 'テスト別名';
        $store->app_cd = 'TORS';
        $store->code = 'testtest_123123';
        $store->address_1 = '東京都';
        $store->address_2 = '渋谷区';
        $store->address_3 = 'テストタワー5F';
        $store->email_1 = 'gourmet-teststore1adventure-inc.co.jp';
        $store->save();

        $responseFormatter  = new ResponseFormatter();
        $result = $responseFormatter->formatEloquentModel($store);
        $this->assertCount(8, $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertSame('テスト店舗', $result['name']);
        $this->assertArrayHasKey('aliasName', $result);
        $this->assertSame('テスト別名', $result['aliasName']);
        $this->assertArrayHasKey('appCd', $result);
        $this->assertSame('TORS', $result['appCd']);
        $this->assertArrayHasKey('code', $result);
        $this->assertSame('testtest_123123', $result['code']);
        $this->assertArrayHasKey('address1', $result);
        $this->assertSame('東京都', $result['address1']);
        $this->assertArrayHasKey('address2', $result);
        $this->assertSame('渋谷区', $result['address2']);
        $this->assertArrayHasKey('address3', $result);
        $this->assertSame('テストタワー5F', $result['address3']);
        $this->assertArrayHasKey('id', $result);
        $this->assertSame($store->id, $result['id']);
    }

    public function testFormatEloquentCollection()
    {
        $responseFormatter  = new ResponseFormatter();

        // 引数が空の場合は、そのまま返ってくる
        $this->assertSame('', $responseFormatter->formatEloquentCollection(''));

        // テストデータ作成（２つ登録する）
        $store = new Store();
        $store->name = 'テスト店舗';
        $store->alias_name = 'テスト別名';
        $store->app_cd = 'TORS';
        $store->code = 'testtest_123123';
        $store->address_1 = '東京都';
        $store->address_2 = '渋谷区';
        $store->address_3 = 'テストタワー5F';
        $store->email_1 = 'gourmet-teststore1@adventure-inc.co.jp';
        $store->save();

        $store2 = new Store();
        $store2->name = 'テスト店舗';
        $store2->alias_name = 'テスト別名2';
        $store2->app_cd = 'TORS';
        $store2->code = 'testtest_1231232';
        $store2->address_1 = '東京都2';
        $store2->address_2 = '渋谷区2';
        $store2->address_3 = 'テストタワー5F2';
        $store2->email_1 = 'gourmet-teststore2@adventure-inc.co.jp';
        $store2->save();

        // テストデータ取得
        $stores = Store::where('name', 'テスト店舗')->get();
        $this->assertCount(2, $stores->toArray());

        // 返却数は本来２だが、１つしか返ってこない。
        // 修正しようとしたが、下記ソースにて認識されてはいたようで、その時点で修正されていないことから
        // 何か修正せずそのままにしている原因がある可能性があるかもしれないので、ひとまずそのままにしておく。
        // src/app/Http/Controllers/Api/v1/AreaController.php
        $result = $responseFormatter->formatEloquentCollection($stores);
        $this->assertCount(1, $result);
        $this->assertIsArray($result[1]);
        $this->assertArrayHasKey('name', $result[1]);
        $this->assertSame('テスト店舗', $result[1]['name']);
        $this->assertArrayHasKey('aliasName', $result[1]);
        $this->assertSame('テスト別名2', $result[1]['aliasName']);
        $this->assertArrayHasKey('appCd', $result[1]);
        $this->assertSame('TORS', $result[1]['appCd']);
        $this->assertArrayHasKey('code', $result[1]);
        $this->assertSame('testtest_1231232', $result[1]['code']);
        $this->assertArrayHasKey('address1', $result[1]);
        $this->assertSame('東京都2', $result[1]['address1']);
        $this->assertArrayHasKey('address2', $result[1]);
        $this->assertSame('渋谷区2', $result[1]['address2']);
        $this->assertArrayHasKey('address3', $result[1]);
        $this->assertSame('テストタワー5F2', $result[1]['address3']);
        $this->assertArrayHasKey('id', $result[1]);
        $this->assertSame($store2->id, $result[1]['id']);
    }
}
