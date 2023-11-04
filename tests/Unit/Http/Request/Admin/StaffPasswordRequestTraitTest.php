<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StaffPasswordRequestTrait;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

// traitをテストするため、テスト用クラスを作成
class StaffPasswordRequestTraitClass
{
    use StaffPasswordRequestTrait;
}

class StaffPasswordRequestTraitTest extends TestCase
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
        $request  = new StaffPasswordRequestTraitClass();
        $rules = $request->rules();
        $attributes = $request->attributes();
        $errorMessages = $request->messages();
        $validator = Validator::make($params, $rules, $errorMessages, $attributes);
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testAttributes()
    {
        $request  = new StaffPasswordRequestTraitClass();
        $result = $request->attributes();
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('val-password2', $result);
        $this->assertSame('パスワード', $result['val-password2']);
        $this->assertArrayHasKey('val-confirm-password2', $result);
        $this->assertSame('パスワード確認', $result['val-confirm-password2']);
    }

    public function testMessages()
    {
        $request  = new StaffPasswordRequestTraitClass();
        $result = $request->messages();
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('val-password2.regex', $result);
        $this->assertSame('新しいパスワードに半角英字の大文字が含まれていない、もしくは、記号が含まれています。', $result['val-password2.regex']);
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
                    'val-password2' => '',
                    'val-confirm-password2' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'val-password2' => ['パスワードは必ず指定してください。'],
                    'val-confirm-password2' => ['パスワード確認は必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'val-password2' => 'Testpassword1',
                    'val-confirm-password2' => 'Testpassword1',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notRegex' => [
                // テスト条件
                [
                    'val-password2' => 'Testpa',
                    'val-confirm-password2' => 'Testpa',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'val-password2' => ['新しいパスワードに半角英字の大文字が含まれていない、もしくは、記号が含まれています。'],
                ],
            ],
            'error-notRegex2' => [
                // テスト条件
                [
                    'val-password2' => 'ああああああ',
                    'val-confirm-password2' => 'ああああああ',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'val-password2' => ['新しいパスワードに半角英字の大文字が含まれていない、もしくは、記号が含まれています。'],
                ],
            ],
            'error-notRegex3' => [
                // テスト条件
                [
                    'val-password2' => '*Testpa',
                    'val-confirm-password2' => '*Testpa',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'val-password2' => ['新しいパスワードに半角英字の大文字が含まれていない、もしくは、記号が含まれています。'],
                ],
            ],
            'error-notSame' => [
                // テスト条件
                [
                    'val-password2' => 'Testpa111',
                    'val-confirm-password2' => 'Testpa112',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'val-confirm-password2' => ['パスワード確認とパスワードには同じ値を指定してください。'],
                ],
            ],
            'error-belowMinimum' => [
                // テスト条件
                [
                    'val-password2' => 'Tes1p',             // 5桁、min:6
                    'val-confirm-password2' => 'Tes1p',     // 5桁、min:6
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'val-password2' => ['パスワードは、6文字以上で指定してください。'],
                    'val-confirm-password2' => ['パスワード確認は、6文字以上で指定してください。'],
                ],
            ],
            'success-minimum' => [
                // テスト条件
                [
                    'val-password2' => 'Tes1pa',            // min:6
                    'val-confirm-password2' => 'Tes1pa',    // min:6
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-maximum' => [
                // テスト条件
                [
                    'val-password2' => str_repeat('Test1', 6),          // max:30
                    'val-confirm-password2' => str_repeat('Test1', 6),  // max:30
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-overMaximum' => [
                // テスト条件
                [
                    'val-password2' => str_repeat('Test1', 6) . '1',          // 31桁、max:30
                    'val-confirm-password2' => str_repeat('Test1', 6) . '1',  // 31桁、max:30
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'val-password2' => ['パスワードは、30文字以下で指定してください。'],
                    'val-confirm-password2' => ['パスワード確認は、30文字以下で指定してください。'],
                ],
            ],
        ];
    }
}
