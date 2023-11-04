<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\AreaAddRequest;
use App\Models\Area;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AreaAddRequestTest extends TestCase
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
        $request  = new AreaAddRequest();
        $this->assertTrue($request->authorize());
    }

    /**
     * バリデーションテスト
     *
     * @dataProvider dataprovider
     */
    public function testRules(array $params, bool $expected, array $messages)
    {
        $this->_createAreas();
        $request  = new AreaAddRequest($params);
        $rules = $request->rules();
        $attributes = $request->attributes();
        $validator = Validator::make($params, $rules, [], $attributes);
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testAttributes()
    {
        $request  = new AreaAddRequest();
        $result = $request->attributes();
        $this->assertCount(6, $result);
        $this->assertArrayHasKey('big_area', $result);
        $this->assertSame('エリア(大)', $result['big_area']);
        $this->assertArrayHasKey('middle_area', $result);
        $this->assertSame('エリア(中)', $result['middle_area']);
        $this->assertArrayHasKey('name', $result);
        $this->assertSame('名前', $result['name']);
        $this->assertArrayHasKey('area_cd', $result);
        $this->assertSame('エリアコード', $result['area_cd']);
        $this->assertArrayHasKey('weight', $result);
        $this->assertSame('優先度', $result['weight']);
        $this->assertArrayHasKey('sort', $result);
        $this->assertSame('ソート順', $result['sort']);
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
            'success-bigArea' => [
                // テスト条件
                [
                    'big_area' => '',
                    'middle_area' => '',
                    'name' => 'テストエリアa',
                    'area_cd' => 'testa',
                    'weight' => '',
                    'sort' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-middleArea' => [
                // テスト条件(中エリアとして登録OK)
                [
                    'big_area' => 'test1',
                    'middle_area' => '',
                    'name' => 'テストエリア1-a',
                    'area_cd' => 'test1-a',
                    'weight' => '',
                    'sort' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-smallArea' => [
                // テスト条件(小エリアとして登録OK)
                [
                    'big_area' => 'test1',
                    'middle_area' => 'test1-2',
                    'name' => 'テストエリア1-2-a',
                    'area_cd' => 'test1-2-a',
                    'weight' => '',
                    'sort' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-empty' => [
                // テスト条件
                [
                    'big_area' => '',
                    'middle_area' => '',
                    'name' => '',
                    'area_cd' => '',
                    'weight' => '',
                    'sort' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'name' => ['名前は必ず指定してください。'],
                    'area_cd' => ['エリアコードは必ず指定してください。'],
                ],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'big_area' => 123,
                    'middle_area' => 123,
                    'name' => 123,
                    'area_cd' => 123,
                    'weight' => '',
                    'sort' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'big_area' => ['エリア(大)は文字列を指定してください。'],
                    'middle_area' => ['エリア(中)は文字列を指定してください。'],
                    'name' => ['名前は文字列を指定してください。'],
                    'area_cd' => ['エリアコードは文字列を指定してください。'],
                ],
            ],
            'error-bigArea' => [
                // テスト条件
                [
                    'big_area' => '',
                    'middle_area' => '',
                    'name' => 'テストエリア1',
                    'area_cd' => 'test1',
                    'weight' => '',
                    'sort' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'area_cd' => ['エリアコードの値は既に存在しています。'],
                ],
            ],
            'error-middleArea' => [
                // テスト条件
                [
                    'big_area' => 'test1',
                    'middle_area' => '',
                    'name' => 'テストエリア1-2',
                    'area_cd' => 'test1-2',
                    'weight' => '',
                    'sort' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'area_cd' => ['エリアコードの値は既に存在しています。'],
                ],
            ],
            'error-smallArea' => [
                // テスト条件
                [
                    'big_area' => 'test1',
                    'middle_area' => 'test1-2',
                    'name' => 'テストエリア1-2-3',
                    'area_cd' => 'test1-2-3',
                    'weight' => '',
                    'sort' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'area_cd' => ['エリアコードの値は既に存在しています。'],
                ],
            ],
        ];
    }

    private function _createAreas()
    {
        $bigArea = new Area;
        $bigArea->area_cd = 'test1';
        $bigArea->name = 'テストエリア1';
        $bigArea->level = 1;
        $bigArea->path = '/';
        $bigArea->published = 1;
        $bigArea->save();

        $middleArea = new Area;
        $middleArea->area_cd = 'test1-2';
        $middleArea->name = 'テストエリア1-2';
        $middleArea->level = 2;
        $middleArea->path = '/test1';
        $middleArea->published = 1;
        $middleArea->save();

        $smallArea = new Area;
        $smallArea->area_cd = 'test1-2-3';
        $smallArea->name = 'テストエリア1-2-3';
        $smallArea->level = 3;
        $smallArea->path = '/test1/test1-2';
        $smallArea->published = 1;
        $smallArea->save();
    }
}
