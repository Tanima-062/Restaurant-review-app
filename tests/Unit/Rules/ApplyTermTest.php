<?php

namespace Tests\Unit\Rules;

use App\Rules\ApplyTerm;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ApplyTermTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testApplyTerm()
    {
        // formがtoより未来の場合、OK
        $params = [
            'apply_term_from_year' => 2022,
            'apply_term_from_month' => 1,
            'apply_term_to_year' => 2023,
            'apply_term_to_month' => 12,
        ];
        $validator = Validator::make(
            ['test' => 1],
            ['test' => new ApplyTerm($params)]
        );
        $this->assertTrue($validator->passes());
        $this->assertCount(0, $validator->errors()->get('test'));   // エラーメッセージなし

        // formとtoより同じの場合、OK
        $params = [
            'apply_term_from_year' => 2023,
            'apply_term_from_month' => 1,
            'apply_term_to_year' => 2023,
            'apply_term_to_month' => 1,
        ];
        $validator = Validator::make(
            ['test' => 1],
            ['test' => new ApplyTerm($params)]
        );
        $this->assertTrue($validator->passes());
        $this->assertCount(0, $validator->errors()->get('test'));   // エラーメッセージなし

        // formとtoより過去の場合、NG
        $params = [
            'apply_term_from_year' => 2023,
            'apply_term_from_month' => 1,
            'apply_term_to_year' => 2022,
            'apply_term_to_month' => 12,
        ];
        $validator = Validator::make(
            ['test' => 1],
            ['test' => new ApplyTerm($params)]
        );
        $this->assertFalse($validator->passes());
        $this->assertCount(1, $validator->errors()->get('test'));   // エラーメッセージあり
        $this->assertSame('適用期間の終了年月は開始年月以降を設定してください。', $validator->errors()->get('test')[0]);

        // パラメータが１つでも空の場合、OK
        {
            // apply_term_from_year
            $params = [
                'apply_term_from_year' => null,
                'apply_term_from_month' => 1,
                'apply_term_to_year' => 2022,
                'apply_term_to_month' => 12,
            ];
            $validator = Validator::make(
                ['test' => 1],
                ['test' => new ApplyTerm($params)]
            );
            $this->assertTrue($validator->passes());
            $this->assertCount(0, $validator->errors()->get('test'));   // エラーメッセージなし

            // apply_term_from_month
            $params = [
                'apply_term_from_year' => 2022,
                'apply_term_from_month' => null,
                'apply_term_to_year' => 2022,
                'apply_term_to_month' => 12,
            ];
            $validator = Validator::make(
                ['test' => 1],
                ['test' => new ApplyTerm($params)]
            );
            $this->assertTrue($validator->passes());
            $this->assertCount(0, $validator->errors()->get('test'));   // エラーメッセージなし

            // apply_term_to_year
            $params = [
                'apply_term_from_year' => 2022,
                'apply_term_from_month' => 1,
                'apply_term_to_year' => null,
                'apply_term_to_month' => 12,
            ];
            $validator = Validator::make(
                ['test' => 1],
                ['test' => new ApplyTerm($params)]
            );
            $this->assertTrue($validator->passes());
            $this->assertCount(0, $validator->errors()->get('test'));   // エラーメッセージなし

            // apply_term_to_month
            $params = [
                'apply_term_from_year' => 2022,
                'apply_term_from_month' => 1,
                'apply_term_to_year' => 2022,
                'apply_term_to_month' => null,
            ];
            $validator = Validator::make(
                ['test' => 1],
                ['test' => new ApplyTerm($params)]
            );
            $this->assertTrue($validator->passes());
            $this->assertCount(0, $validator->errors()->get('test'));   // エラーメッセージなし
        }
    }
}
