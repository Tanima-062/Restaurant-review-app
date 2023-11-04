<?php

namespace Tests\Unit\Rules;

use App\Rules\MbStringCheck;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MbStringCheckTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testMbStringCheck()
    {
        $max = 10;

        // 10文字以下
        $validator = Validator::make(
            ['test' =>  'testtest'],
            ['test' => new MbStringCheck($max)]
        );
        $this->assertTrue($validator->passes());
        $this->assertCount(0, $validator->errors()->get('test'));   // エラーメッセージなし

        //10文字より大きい
        $validator = Validator::make(
            ['test' =>  'testtesttest'],
            ['test' => new MbStringCheck($max)]
        );
        $this->assertFalse($validator->passes());
        $this->assertCount(1, $validator->errors()->get('test'));   // エラーメッセージあり
        $this->assertSame('testは、10文字以下で入力して下さい。', $validator->errors()->get('test')[0]);

    }
}
