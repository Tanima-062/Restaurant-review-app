<?php

namespace Tests\Feature\Controller\Api\v1;

use App\Models\Area;
use App\Models\Store;
use App\Services\AuthService;
use App\Services\StoreService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StoreControllerTest extends TestCase
{
    private $store;
    private $date;
    private $time;

    protected function setUp(): void
    {
        parent::setUp();

        DB::beginTransaction();
        $store = new Store();
        $store->published = 1;
        $store->save();
        $this->store = $store;

        $this->date = Carbon::now()->format('Y-m-d');
        $this->time = Carbon::now()->format('H:i');
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testGet()
    {
        // 店舗情報取得
        $response = $this->get('/gourmet/v1/ja/store/'.$this->store->id);
        $response->assertStatus(200);

        // 店舗の公開フラグをOFFに変更
        Store::find($this->store->id)->update(['published' => 0]);

        // 店舗情報取得に失敗すること
        $response = $this->get('/gourmet/v1/ja/store/'.$this->store->id);
        $response->assertStatus(404);
    }

    public function testGetException()
    {
        $storeService = \Mockery::mock(StoreService::class);
        $storeService->shouldReceive('get')->andThrow(new \Exception('unit test exception message'));
        $this->app->instance(StoreService::class, $storeService);

        // 店舗情報取得
        $response = $this->get('/gourmet/v1/ja/store/'.$this->store->id);
        $response->assertStatus(500);
    }

    public function testGetStoreReview()
    {
        $response = $this->get('/gourmet/v1/ja/store/'.$this->store->id.'/review');
        $response->assertStatus(200);
    }

    public function testGetStoreReviewException()
    {
        $storeService = \Mockery::mock(StoreService::class);
        $storeService->shouldReceive('getStoreReview')->andThrow(new \Exception());
        $this->app->instance(StoreService::class, $storeService);

        $response = $this->get('/gourmet/v1/ja/store/'.$this->store->id.'/review');
        $response->assertStatus(500);
    }

    public function testGetStoreImage()
    {
        $response = $this->get('/gourmet/v1/ja/store/'.$this->store->id.'/image');
        $response->assertStatus(200);
    }

    public function testGetStoreImageException()
    {
        $storeService = \Mockery::mock(StoreService::class);
        $storeService->shouldReceive('getStoreImage')->andThrow(new \Exception());
        $this->app->instance(StoreService::class, $storeService);

        $response = $this->get('/gourmet/v1/ja/store/'.$this->store->id.'/image');
        $response->assertStatus(500);
    }

    public function testGetStoreRestaurantMenu()
    {
        $response = $this->get('/gourmet/v1/ja/store/'.$this->store->id.'/restaurantMenu?visitDate='.$this->date.'&visitTime='.$this->time.'&visitPeople=2&dateUndecided=false');
        $response->assertStatus(200);
    }

    public function testGetStoreRestaurantMenuException()
    {
        $storeService = \Mockery::mock(StoreService::class);
        $storeService->shouldReceive('getStoreRestaurantMenu')->andThrow(new \Exception('unit test exception message'));
        $this->app->instance(StoreService::class, $storeService);

        $response = $this->get('/gourmet/v1/ja/store/'.$this->store->id.'/restaurantMenu?visitDate='.$this->date.'&visitTime='.$this->time.'&visitPeople=2&dateUndecided=false');
        $response->assertStatus(500);
    }

    public function testGetStoreTakeoutMenu()
    {
        $response = $this->get('/gourmet/v1/ja/store/'.$this->store->id.'/takeoutMenu?pickUpDate='.$this->date.'&pickUpTime='.$this->time);
        $response->assertStatus(200);
    }

    public function testGetStoreTakeoutMenuException()
    {
        $storeService = \Mockery::mock(StoreService::class);
        $storeService->shouldReceive('getStoreTakeoutMenu')->andThrow(new \Exception());
        $this->app->instance(StoreService::class, $storeService);

        $response = $this->get('/gourmet/v1/ja/store/'.$this->store->id.'/takeoutMenu?pickUpDate='.$this->date.'&pickUpTime='.$this->time);
        $response->assertStatus(500);
    }

    public function testRegister()
    {
        $authService = \Mockery::mock(AuthService::class);
        $authService->shouldReceive('registerReview')->andReturn(true);
        $this->app->instance(AuthService::class, $authService);

        $response = $this->post('/gourmet/v1/ja/reservation/review', [
            'reservationNo' => '1',
            'menuId' => 1,
            'evaluationCd' => 'COOKING',
            'body' => 'GOOD',
            'isRealName' => '1',
        ]);
        $response->assertStatus(200);
    }

    public function testRegisterException()
    {
        $authService = \Mockery::mock(AuthService::class);
        $authService->shouldReceive('registerReview')->andReturn(false);
        $this->app->instance(AuthService::class, $authService);

        $response = $this->post('/gourmet/v1/ja/reservation/review', [
            'reservationNo' => '1',
            'menuId' => 1,
            'evaluationCd' => 'COOKING',
            'body' => 'GOOD',
            'isRealName' => '1',
        ]);
        $response->assertStatus(500);
    }

    public function testGetBreadcrumb()
    {
        $response = $this->get('/gourmet/v1/ja/store/1/breadcrumb?isStore=true&isSearch=false');
        $response->assertStatus(200);
    }

    public function testGetBreadcrumbException()
    {
        $storeService = \Mockery::mock(StoreService::class);
        $storeService->shouldReceive('getBreadcrumb')->andThrow(new \Exception());
        $this->app->instance(StoreService::class, $storeService);

        $response = $this->get('/gourmet/v1/ja/store/1/breadcrumb?isStore=true&isSearch=false');
        $response->assertStatus(500);
    }

    public function testGetCancelPolicy()
    {
        $response = $this->get('/gourmet/v1/ja/store/'.$this->store->id.'/cancelPolicy?appCd=rs');
        $response->assertStatus(200);
        $response = $this->get('/gourmet/v1/ja/store/'.$this->store->id.'/cancelPolicy?appCd=to');
        $response->assertStatus(200);
    }

    public function testGetCancelPolicyException()
    {
        $storeService = \Mockery::mock(StoreService::class);
        $storeService->shouldReceive('getCancelPolicy')->andThrow(new \Exception());
        $this->app->instance(StoreService::class, $storeService);

        $response = $this->get('/gourmet/v1/ja/store/'.$this->store->id.'/cancelPolicy');
        $response->assertStatus(500);
    }

    public function testStoreSearch()
    {
        $response = $this->get('/gourmet/v1/ja/store/search?appCd=rs');
        $response->assertStatus(200);
        $response = $this->get('/gourmet/v1/ja/store/search?appCd=to');
        $response->assertStatus(200);
    }

    public function testStoreSearchException()
    {
        $storeService = \Mockery::mock(StoreService::class);
        $storeService->shouldReceive('storeSearch')->andThrow(new \Exception());
        $this->app->instance(StoreService::class, $storeService);

        $response = $this->get('/gourmet/v1/ja/store/search');
        $response->assertStatus(500);
    }

    public function testGetStoreBuffet()
    {
        // 店舗にエリア情報を追加
        $area = new Area();
        $area->path = '/';
        $area->save();
        Store::find($this->store->id)->update(['area_id' => $area->id]);

        $response = $this->get('/gourmet/v1/ja/store/'.$this->store->id.'/buffet?genreId=1373');
        $response->assertStatus(200);
    }

    public function testGetStoreBuffetException()
    {
        $storeService = \Mockery::mock(StoreService::class);
        $storeService->shouldReceive('getStoreBuffet')->andThrow(new \Exception());
        $this->app->instance(StoreService::class, $storeService);

        $response = $this->get('/gourmet/v1/ja/store/'.$this->store->id.'/buffet?genreId=1');
        $response->assertStatus(500);
    }
}
