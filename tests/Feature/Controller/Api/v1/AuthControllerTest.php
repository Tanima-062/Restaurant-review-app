<?php

namespace Tests\Feature\Controller\Api\v1;

use App\Services\AuthService;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSession()
    {
        // 未ログイン状態
        $response = $this->get('/gourmet/v1/ja/auth/login');
        $response->assertStatus(401);

        // ログインする
        $response = $this->post('/gourmet/v1/ja/auth/login', [
            'loginId' => 'y-nakazato20201026@adventure-inc.co.jp',
            'password' => 'nakazato20201026',
        ]);
        $response->assertStatus(200);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $response['apiToken'],
        ]);

        // ログイン状態であるのでエラーにならない
        $response = $this->get('gourmet/v1/ja/auth/login');
        $response->assertStatus(200);

        // ログアウトする
        $response = $this->post('gourmet/v1/ja/auth/logout');
        $response->assertStatus(200);

        // 未ログイン状態
        $response = $this->get('/gourmet/v1/ja/auth/login');
        $response->assertStatus(401)->assertJson(['error' => 'ログインしていません。']);
    }

    public function testLoginError()
    {
        // ログイン認証失敗
        $response = $this->post('/gourmet/v1/ja/auth/login', [
            'loginId' => 'gourmet-test@adventure-inc.co.jp',
            'password' => '123456',
        ]);
        $response->assertStatus(401)->assertJson(['error' => 'ログインに失敗しました。']);
    }

    public function testLogoutError()
    {
        // ログアウト失敗
        $response = $this->post('gourmet/v1/ja/auth/logout');
        $response->assertStatus(401)->assertJson(['error' => 'ログアウトに失敗しました。']);
    }

    public function testGetMyPage()
    {
        $authService = \Mockery::mock(AuthService::class);
        $authService->shouldReceive('getMypage')->andReturn(true);
        $this->app->instance(AuthService::class, $authService);
        $response = $this->post('/gourmet/v1/ja/reservation', ['reservationNo' => '1', 'tel' => '999']);
        $response->assertStatus(200);

        $authService = \Mockery::mock(AuthService::class);
        $authService->shouldReceive('getMypage')->andReturn(false);
        $this->app->instance(AuthService::class, $authService);
        $response = $this->post('/gourmet/v1/ja/reservation', ['reservationNo' => '1', 'tel' => '999']);
        $response->assertStatus(200);
    }

    public function testGetMyPageException()
    {
        $authService = \Mockery::mock(AuthService::class);
        $authService->shouldReceive('getMypage')->andThrow(new \Exception());
        $this->app->instance(AuthService::class, $authService);

        $response = $this->post('/gourmet/v1/ja/reservation', ['reservationNo' => '1', 'tel' => '999']);
        $response->assertStatus(500);
    }
}
