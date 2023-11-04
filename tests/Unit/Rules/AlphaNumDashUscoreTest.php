<?php

namespace Tests\Unit\Rules;

use App\Rules\AlphaNumDashUscore;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AlphaNumDashUscoreTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testAlphaNumDashUscore()
    {
        $alphaNumDashUscore = new AlphaNumDashUscore();

        // 半角数字のみの場合、OK
        $validator = Validator::make(
            ['test' => '1234567890'],
            ['test' =>  $alphaNumDashUscore]
        );
        $this->assertTrue($validator->passes());
        $this->assertCount(0, $validator->errors()->get('test'));   // エラーメッセージなし

        // 半角英字のみの場合、OK
        $validator = Validator::make(
            ['test' => 'abcdefghijklmnopqrstuvwxyz'],
            ['test' =>  $alphaNumDashUscore]
        );
        $this->assertTrue($validator->passes());
        $this->assertCount(0, $validator->errors()->get('test'));   // エラーメッセージなし
        $validator = Validator::make(
            ['test' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'],
            ['test' =>  $alphaNumDashUscore]
        );
        $this->assertTrue($validator->passes());
        $this->assertCount(0, $validator->errors()->get('test'));   // エラーメッセージなし

        // 半角ハイフン(-)半角下線(_)の場合、OK
        $validator = Validator::make(
            ['test' => '-_'],
            ['test' =>  $alphaNumDashUscore]
        );
        $this->assertTrue($validator->passes());
        $this->assertCount(0, $validator->errors()->get('test'));   // エラーメッセージなし

        // 半角英数以外が含まれるの場合、NG
        $validator = Validator::make(
            ['test' => '1234あああAbc'],
            ['test' =>  $alphaNumDashUscore]
        );
        $this->assertFalse($validator->passes());
        $this->assertCount(1, $validator->errors()->get('test'));   // エラーメッセージあり
        $this->assertSame('testは、半角英数字と半角ハイフン(-)及び半角下線(_)がご利用できます。', $validator->errors()->get('test')[0]);
    }
}
