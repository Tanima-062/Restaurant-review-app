<?php

namespace Tests\Unit\Rules;

use App\Rules\GenrePrefix;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class GenrePrefixTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testGenrePrefix()
    {
        // 小ジャンル（接頭辞がi-始まり）の場合、OK
        $attributes = [
            'small_genre' => 'i-testtest',
            'test' =>  'i-testtest'
        ];
        $validator = Validator::make(
            ['test' =>  'i-testtest'],
            ['test' => new GenrePrefix($attributes)]
        );
        $this->assertTrue($validator->passes());
        $this->assertCount(0, $validator->errors()->get('test'));   // エラーメッセージなし

        // 小ジャンル（接頭辞がi-始まりではない)場合、NG
        $attributes = [
            'small_genre' => 'i-testtest',
            'test' =>  'testtest'
        ];
        $validator = Validator::make(
            ['test' =>  'testtest'],
            ['test' => new GenrePrefix($attributes)]
        );
        $this->assertFalse($validator->passes());
        $this->assertCount(1, $validator->errors()->get('test'));   // エラーメッセージあり
        $this->assertSame('先頭にプレフィックスとして「i-」をつけてください', $validator->errors()->get('test')[0]);

        // 中ジャンル（接頭辞がs-始まり）の場合、OK
        $attributes = [
            'small_genre' => '',
            'middle_genre' => 's-testtest',
            'test' =>  's-testtest'
        ];
        $validator = Validator::make(
            ['test' =>  's-testtest'],
            ['test' => new GenrePrefix($attributes)]
        );
        $this->assertTrue($validator->passes());
        $this->assertCount(0, $validator->errors()->get('test'));   // エラーメッセージなし

        // 中ャンル（接頭辞がs-始まりではない)場合、NG
        $attributes = [
            'small_genre' => '',
            'middle_genre' => 's-testtest',
            'test' =>  'testtest'
        ];
        $validator = Validator::make(
            ['test' =>  'testtest'],
            ['test' => new GenrePrefix($attributes)]
        );
        $this->assertFalse($validator->passes());
        $this->assertCount(1, $validator->errors()->get('test'));   // エラーメッセージあり
        $this->assertSame('先頭にプレフィックスとして「s-」をつけてください', $validator->errors()->get('test')[0]);

        // 大ジャンル（接頭辞がm-始まり）の場合、OK
        $attributes = [
            'middle_genre' => '',
            'big_genre' => 'm-testtest',
            'test' =>  'm-testtest'
        ];
        $validator = Validator::make(
            ['test' =>  'm-testtest'],
            ['test' => new GenrePrefix($attributes)]
        );
        $this->assertTrue($validator->passes());
        $this->assertCount(0, $validator->errors()->get('test'));   // エラーメッセージなし

        // 大ジャンル（接頭辞がs-始まりではない)場合、NG
        $attributes = [
            'middle_genre' => '',
            'big_genre' => 'm-testtest',
            'test' =>  'testtest'
        ];
        $validator = Validator::make(
            ['test' =>  'testtest'],
            ['test' => new GenrePrefix($attributes)]
        );
        $this->assertFalse($validator->passes());
        $this->assertCount(1, $validator->errors()->get('test'));   // エラーメッセージあり
        $this->assertSame('先頭にプレフィックスとして「m-」をつけてください', $validator->errors()->get('test')[0]);
    }
}
