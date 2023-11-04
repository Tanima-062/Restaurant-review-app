<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\AccessLog;
use Illuminate\Http\Request;
use Tests\TestCase;

class AccessLogTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testHandle()
    {
        // middlewareを呼び出し
        $request = new Request();
        $middleware = new AccessLog();
        $response = $middleware->handle($request, function () {
            $this->assertTrue(true);
        });
        // エラーレスポンスが返却されないこと
        $this->assertNull($response);
    }
}
