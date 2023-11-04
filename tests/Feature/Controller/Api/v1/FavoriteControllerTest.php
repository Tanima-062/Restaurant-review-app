<?php

namespace Tests\Feature\Controller\Api\v1;

use App\Models\Favorite;
use App\Models\Menu;
use App\Models\Price;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FavoriteControllerTest extends TestCase
{
    private $menu;

    protected function setUp(): void
    {
        parent::setUp();

        $response = $this->get('/gourmet/v1/ja/auth/login');
        if ($response->getStatusCode() === 401 || empty($response['apiToken'])) {
            $response = $this->post('/gourmet/v1/ja/auth/login', [
                'loginId' => 'y-nakazato20201026@adventure-inc.co.jp',
                'password' => 'nakazato20201026',
            ]);
        }

        $this->withHeaders([
            'Authorization' => 'Bearer '.$response['apiToken'],
        ]);

        DB::beginTransaction();
        $menu = new Menu();
        $menu->sales_lunch_start_time = '10:00';
        $menu->sales_lunch_end_time = '13:00';
        $menu->save();
        $this->menu = $menu;
        $price = new Price();
        $price->start_date = '2020-12-01';
        $price->end_date = '2999-12-31';
        $price->menu_id = $this->menu->id;
        $price->save();
    }

    public function tearDown(): void
    {
        $response = $this->post('/gourmet/v1/ja/auth/logout');
        DB::rollBack();
        parent::tearDown();
    }

    public function testRegister()
    {
        // お気に入り登録
        $response = $this->post('/gourmet/v1/ja/favorite/register', ['id' => $this->menu->id, 'appCd' => 'to']);
        $response->assertStatus(200);
    }

    public function testRegisterException()
    {
        $favorite = \Mockery::mock(Favorite::class);
        $favorite->shouldReceive('registerFavorite')->andReturn(false);
        $this->app->instance(Favorite::class, $favorite);

        // お気に入り登録
        $response = $this->post('/gourmet/v1/ja/favorite/register', ['id' => $this->menu->id]);
        $response->assertStatus(500);
    }

    public function testDelete()
    {
        // お気に入り削除
        $response = $this->post('/gourmet/v1/ja/favorite/delete', ['id' => $this->menu->id, 'appCd' => 'to']);
        $response->assertStatus(200);
    }

    public function testDeleteException()
    {
        $favorite = \Mockery::mock(Favorite::class);
        $favorite->shouldReceive('deleteFavorite')->andReturn(false);
        $this->app->instance(Favorite::class, $favorite);

        // お気に入り削除
        $response = $this->post('/gourmet/v1/ja/favorite/delete', ['id' => $this->menu->id]);
        $response->assertStatus(500);
    }

    public function testGet()
    {
        // お気に入り取得
        $response = $this->get('/gourmet/v1/ja/favorite?pickUpTime=12:00&pickUpDate=2020-12-01&appCd=to&menuIds='.$this->menu->id);
        $response->assertStatus(200);
    }

    public function testGetException()
    {
        $favorite = \Mockery::mock(Favorite::class);
        $favorite->shouldReceive('getFavoriteIds')->andReturn(false);
        $this->app->instance(Favorite::class, $favorite);

        // お気に入り削除
        $response = $this->get('/gourmet/v1/ja/favorite?pickUpTime=12:00&pickUpDate=2020-12-01');
        $response->assertStatus(500);
    }
}
