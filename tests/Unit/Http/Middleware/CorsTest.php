<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\Cors;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class CorsTest extends TestCase
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
        $response = new Response();
        $middleware = new Cors();
        $result = $middleware->handle($request, function () use ($response) {
            $this->assertTrue(true);
            return $response;
        });
        // ヘッダー情報が正しいか確認
        $this->assertSame('GET, POST, PUT, DELETE, OPTIONS', $result->headers->get('Access-Control-Allow-Methods'));
        $this->assertSame('true', $result->headers->get('Access-Control-Allow-Credentials'));
        $this->assertSame('Content-Type', $result->headers->get('Access-Control-Allow-Headers'));
    }
}
