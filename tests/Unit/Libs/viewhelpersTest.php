<?php

namespace Tests\Unit\Libs;

use Tests\TestCase;

class viewhelpersTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testBadgeColor()
    {
        $this->assertSame('primary', badge_color(10, 10));
        $this->assertSame('success', badge_color(11, 10));
        $this->assertSame('info', badge_color(12, 10));
        $this->assertSame('warning', badge_color(13, 10));
        $this->assertSame('danger', badge_color(14, 10));
        $this->assertSame('secondary', badge_color(15, 10));
    }

    public function testSidebarMenuActive()
    {
        $this->assertSame('active', sidebar_menu_active(''));
        $this->assertSame('active', sidebar_menu_active('/'));
        $this->assertSame('', sidebar_menu_active('test/test'));
    }

    public function testReservationNoToId()
    {
        $this->assertSame('1234', reservation_no_to_id('TO1234'));
        $this->assertSame('5678', reservation_no_to_id('RS5678'));
    }

    public function testHostUrl()
    {
        if (\App::environment('local') || \App::environment('develop')) {
            $this->assertSame('https://jp.skyticket.jp/gourmet/', host_url());
        } else if (\App::environment('staging') || \App::environment('production')) {
            $this->assertSame('https://skyticket.jp/gourmet/', host_url());
        } else {
            $this->assertEmpty(host_url());   // ここは通過しないはず
        }
    }

    public function testQsUrl()
    {
        // path/qs/secure全て指定
        $result = qs_url('test/test2', ['a' => 1, 'b' => 10, 'c' => 'aiueo'], 5);
        $this->assertSame(env('APP_URL') . '/test/test2/5?a=1&b=10&c=aiueo', $result);

        // path/qs/secure全て未指定
        $result = qs_url();
        $this->assertSame(env('APP_URL'), $result);

        // pathのみ指定
        $result = qs_url('test123/abc');
        $this->assertSame(env('APP_URL') . '/test123/abc', $result);

        // qsのみ指定
        $result = qs_url(null, ['a' => 1, 'b' => 10, 'c' => 'aiueo']);
        $this->assertSame(env('APP_URL') . '?a=1&b=10&c=aiueo', $result);

        // secureのみ指定
        $result = qs_url(null, [], 5);
        $this->assertSame(env('APP_URL') . '/5', $result);
    }
}
