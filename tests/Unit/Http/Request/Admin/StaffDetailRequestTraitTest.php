<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StaffDetailRequestTrait;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

// traitをテストするため、テスト用クラスを作成
class TestStaffDetailRequestTraitClass
{
    use StaffDetailRequestTrait;
}

class StaffDetailRequestTraitTest extends TestCase
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
        $request  = new TestStaffDetailRequestTraitClass();
        $rules = $request->rules();
        $attributes = $request->attributes();
        $validator = Validator::make($params, $rules, [], $attributes);
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testAttributes()
    {
        $request  = new TestStaffDetailRequestTraitClass();
        $result = $request->attributes();
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertSame('お名前', $result['name']);
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
                    'name' => ['お名前は必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'name' => 'テストお名前',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-minimum' => [
                // テスト条件
                [
                    'name' => 'テ',                 // min:1
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-maximum' => [
                // テスト条件
                [
                    'name' => Str::random(64),      // max:64
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-overMaximum' => [
                // テスト条件
                [
                    'name' => Str::random(65),      // 65桁、max:64
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'name' => ['お名前は、64文字以下で指定してください。'],
                ],
            ],
        ];
    }
}
