<?php

namespace Tests\Feature\Controller\Api\v1;

use App\Services\AreaService;
use Tests\TestCase;

class AreaControllerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testGetArea()
    {
        $response = $this->get('/gourmet/v1/ja/area/test');
        $response->assertStatus(200);
    }

    public function testGetAreaException()
    {
        $areaService = \Mockery::mock(AreaService::class);
        $areaService->shouldReceive('getArea')->andThrow(new \Exception());
        $this->app->instance(AreaService::class, $areaService);

        $response = $this->get('/gourmet/v1/ja/area/test');
        $response->assertStatus(500);
    }

    public function testGetAreaAdmin()
    {
        $response = $this->get('/admin/v1/area?areaCd=JAPAN&lowerLevel=1');
        $response->assertStatus(200);
    }

    public function testGetAreaAdminException()
    {
        $areaService = \Mockery::mock(AreaService::class);
        $areaService->shouldReceive('searchBox')->andThrow(new \Exception());
        $this->app->instance(AreaService::class, $areaService);

        $response = $this->get('/admin/v1/area?areaCd=JAPAN&lowerLevel=1');
        $response->assertStatus(500);
    }
}
