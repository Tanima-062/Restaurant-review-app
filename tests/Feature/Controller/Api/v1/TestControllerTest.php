<?php

namespace Tests\Feature\Controller\Api\v1;

use Tests\TestCase;

class TestControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        $response = $this->get('/gourmet/v1/ja/test');

        $response->assertStatus(200);
    }
}
