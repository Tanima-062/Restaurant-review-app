<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StoreEditRequestTrait;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

// traitをテストするため、テスト用クラスを作成
class StoreEditRequestTraitClass
{
    use StoreEditRequestTrait;
}

class StoreEditRequestTraitTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * バリデーションテスト
     *
     * @dataProvider dataprovider
     */
    public function testRules(array $params, bool $expected, array $messages)
    {
        // テスト実施
        $request  = new StoreEditRequestTraitClass();
        $rules = $request->rules();
        $attributes = $request->attributes();
        $validator = Validator::make($params, $rules, [], $attributes);
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testAttributes()
    {
        $request  = new StoreEditRequestTraitClass();
        $result = $request->attributes();
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertSame('店舗名', $result['name']);
    }

    public function testMessages()
    {
        $request  = new StoreEditRequestTraitClass();
        $result = $request->messages();
        $this->assertNull($result);
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
            'success-empty' => [
                // テスト条件
                [
                    'name' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'name' => ['店舗名は必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'name' => 'テスト店舗名',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-maximum' => [
                // テスト条件
                [
                    'name' => Str::random(30),          // max:30
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-overMaximum' => [
                // テスト条件
                [
                    'name' => Str::random(31),          // 31桁、max:30
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'name' => ['店舗名は、30文字以下で指定してください。'],
                ],
            ],
        ];
    }
}
