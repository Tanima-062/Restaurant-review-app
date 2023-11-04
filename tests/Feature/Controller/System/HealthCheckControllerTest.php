<?php

namespace Tests\Feature\Controller\System;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class HealthCheckControllerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testIndex()
    {
        $response = $this->get('/health');
        $response->assertStatus(200);                   // アクセス確認
        $response->assertSee('Health check OK');
    }
}
