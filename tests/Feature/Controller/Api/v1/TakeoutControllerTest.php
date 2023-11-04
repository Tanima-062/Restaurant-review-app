<?php

namespace Tests\Feature\Controller\Api\v1;

use App\Models\Menu;
use App\Models\Notice;
use App\Models\Reservation;
use App\Services\ReservationService;
use App\Services\TakeoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class TakeoutControllerTest extends TestCase
{
    private $params = [];
    private $headers = [];
    private $body = '{}';
    private $recommendData;
    private $recommendDataBackup;
    private $reservation;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();

        $this->body = '{
            "customer":{
               "firstName":"吾郎",
               "lastName":"山田",
               "email":"yamada@org",
               "tel":"012012340000",
               "request":"アレルギーあります"
            },
            "application":{
               "menus":[
                  {
                     "menu":{
                        "id":1,
                        "count":4
                     },
                     "options":[
                        {
                           "id":1,
                           "keywordId":0,
                           "contentsId":0
                        }
                     ]
                  }
               ],
               "pickUpDate":"2020-12-14",
               "pickUpTime":"12:00:00"
            },
            "payment":{
               "returnUrl":"string"
            }
         }';

        $this->recommendData = '{
            "WASHOKU":[
               {
                  "id":1,
                  "name":"\u30bf\u30d4\u30aa\u30ab\u30d1\u30f3",
                  "description":"\u30e1\u30cb\u30e5\u30fc\u8aac\u660e\u30e1\u30cb\u30e5\u30fc\u8aac\u660e\u30e1\u30cb\u30e5\u30fc\u8aac\u660e\u30e1\u30cb\u30e5\u30fc\u8aac\u660e\u30e1\u30cb\u30e5\u30fc\u8aac\u660e",
                  "sales_lunch_start_time":"10:00:00",
                  "sales_lunch_end_time":"15:00:00",
                  "sales_dinner_start_time":"14:00:00",
                  "sales_dinner_end_time":"16:00:00",
                  "app_cd":"RS",
                  "available_number_of_lower_limit":null,
                  "available_number_of_upper_limit":null,
                  "number_of_orders_same_time":null,
                  "number_of_course":null,
                  "free_drinks":1,
                  "provided_time":"120",
                  "plan":null,
                  "notes":null,
                  "provided_day_of_week":"00010110",
                  "lower_orders_time":null,
                  "remarks":null,
                  "store_id":1
               },
               {
                  "id":4,
                  "name":"\u30e1\u30cb\u30e5\u30fc\u540d",
                  "description":"\u30e1\u30cb\u30e5\u30fc\u8aac\u660e",
                  "sales_lunch_start_time":"17:22:49",
                  "sales_lunch_end_time":"13:44:54",
                  "sales_dinner_start_time":"18:00:00",
                  "sales_dinner_end_time":null,
                  "app_cd":"RESTAURANT",
                  "available_number_of_lower_limit":1,
                  "available_number_of_upper_limit":5,
                  "number_of_orders_same_time":null,
                  "number_of_course":null,
                  "free_drinks":null,
                  "provided_time":null,
                  "plan":null,
                  "notes":null,
                  "provided_day_of_week":"11111110",
                  "lower_orders_time":null,
                  "remarks":null,
                  "store_id":2
               }
            ],
            "CHUKA":[
               {
                  "id":3,
                  "name":"\u30e9\u30c3\u30b7\u30fc",
                  "description":"dddd",
                  "sales_lunch_start_time":"10:00:00",
                  "sales_lunch_end_time":"13:00:00",
                  "sales_dinner_start_time":null,
                  "sales_dinner_end_time":null,
                  "app_cd":"\u30ec\u30b9\u30c8\u30e9\u30f3",
                  "available_number_of_lower_limit":null,
                  "available_number_of_upper_limit":null,
                  "number_of_orders_same_time":null,
                  "number_of_course":"8",
                  "free_drinks":0,
                  "provided_time":"90",
                  "plan":null,
                  "notes":null,
                  "provided_day_of_week":"11111110",
                  "lower_orders_time":null,
                  "remarks":null,
                  "store_id":2
               },
               {
                  "id":5,
                  "name":"\u30e1\u30cb\u30e5\u30fc\u540d",
                  "description":"\u30e1\u30cb\u30e5\u30fc\u8aac\u660e",
                  "sales_lunch_start_time":"09:44:17",
                  "sales_lunch_end_time":"23:36:50",
                  "sales_dinner_start_time":"18:00:00",
                  "sales_dinner_end_time":"20:00:00",
                  "app_cd":"gm",
                  "available_number_of_lower_limit":1,
                  "available_number_of_upper_limit":6,
                  "number_of_orders_same_time":null,
                  "number_of_course":null,
                  "free_drinks":null,
                  "provided_time":null,
                  "plan":null,
                  "notes":null,
                  "provided_day_of_week":"11111110",
                  "lower_orders_time":null,
                  "remarks":null,
                  "store_id":1
               }
            ]
         }';
        $this->recommendDataBackup = Redis::get(config('takeout.batch.cacheRecommend.cache.nameRecommendApi'));
        Redis::set(config('takeout.batch.cacheRecommend.cache.nameRecommendApi'), $this->recommendData);

        $reservation = new Reservation();
        $reservation->app_cd = 'TO';
        $reservation->tel = '012012340000';
        $reservation->save();
        $this->reservation = $reservation;
    }

    public function tearDown(): void
    {
        Redis::set(config('takeout.batch.cacheRecommend.cache.nameRecommendApi'), $this->recommendDataBackup);
        DB::rollBack();
        parent::tearDown();
    }

    public function testSearch()
    {
        $menu = \Mockery::mock(Menu::class);
        $menu->shouldReceive('search')->andReturn([
            'count' => 0,
            'pageMax' => 0,
            'list' => collect([]),
        ]);
        $this->app->instance(Menu::class, $menu);

        $response = $this->get('/gourmet/v1/ja/takeout/search');
        $response->assertStatus(200);
    }

    public function testSearchException()
    {
        $menu = \Mockery::mock(Menu::class);
        $menu->shouldReceive('search')->andThrow(new \Exception());
        $this->app->instance(Menu::class, $menu);

        $response = $this->get('/gourmet/v1/ja/takeout/search');
        $response->assertStatus(500);
    }

    public function testGetRecommendation()
    {
        $response = $this->get('/gourmet/v1/ja/takeout/recommend');
        $response->assertStatus(200);
    }

    public function testGetRecommendationException()
    {
        $menu = \Mockery::mock(Menu::class);
        $menu->shouldReceive('getRecommendation')->andThrow(new \Exception());
        $this->app->instance(Menu::class, $menu);

        $response = $this->get('/gourmet/v1/ja/takeout/recommend');
        $response->assertStatus(500);
    }

    public function testSave()
    {
        $reservationService = \Mockery::mock(ReservationService::class);
        $reservationService->shouldReceive('save')->andReturn(true);
        $this->app->instance(ReservationService::class, $reservationService);

        $params = json_decode($this->body, true);
        $response = $this->post('/gourmet/v1/ja/takeout/reservation/save', $params);
        $response->assertStatus(200);
    }

    public function testSaveFailure()
    {
        $reservationService = \Mockery::mock(ReservationService::class);
        $reservationService->shouldReceive('save')->andReturn(false);
        $this->app->instance(ReservationService::class, $reservationService);

        $params = json_decode($this->body, true);
        $response = $this->post('/gourmet/v1/ja/takeout/reservation/save', $params);
        // 失敗でも200を返す仕様
        $response->assertStatus(200);
    }

    public function testComplete()
    {
        $reservationService = \Mockery::mock(ReservationService::class);
        $reservationService->shouldReceive('complete')->andReturn(true);
        $this->app->instance(ReservationService::class, $reservationService);

        $response = $this->post('/gourmet/v1/ja/takeout/reservation/complete', ['sessionToken' => 'jajaja', 'cd3secResFlg' => '1']);
        $response->assertStatus(200);
    }

    public function testCompleteFailure()
    {
        $reservationService = \Mockery::mock(TakeoutController::class);
        $reservationService->shouldReceive('complete')->andReturn(false);
        $this->app->instance(TakeoutController::class, $reservationService);

        $response = $this->post('/gourmet/v1/ja/takeout/reservation/complete', ['sessionToken' => 'jajaja', 'cd3secResFlg' => '1']);
        $response->assertStatus(500);
    }

    public function testNotice()
    {
        Notice::query()->delete();

        $notice = new Notice();
        $notice->app_cd = key(config('code.appCd.to'));
        $notice->title = 'メンテナンスのお知らせ';
        $notice->message = '2020年1月1日 02:00〜04:00までメンテナンスを行います。';
        $notice->datetime_from = '2020-01-01 02:00:00';
        $notice->datetime_to = '9999-01-01 04:00:00';
        $notice->published_at = '2020-12-27 00:00:00';
        $notice->ui_website_flg = 1;
        $notice->published = 1;
        $notice->save();

        $response = $this->get('/gourmet/v1/ja/takeout/notice');
        $response->assertStatus(200);

        $responseContent = json_decode($response->getContent());
        $this->assertObjectHasAttribute('notices', $responseContent);
        $this->assertCount(1, $responseContent->notices);
        $this->assertObjectHasAttribute('id', $responseContent->notices[0]);

        $this->assertObjectHasAttribute('title', $responseContent->notices[0]);
        $this->assertSame('メンテナンスのお知らせ', $responseContent->notices[0]->title);

        $this->assertObjectHasAttribute('message', $responseContent->notices[0]);
        $this->assertSame('2020年1月1日 02:00〜04:00までメンテナンスを行います。', $responseContent->notices[0]->message);

        $this->assertObjectHasAttribute('datetimeFrom', $responseContent->notices[0]);
        $this->assertSame('2020-01-01 02:00:00', $responseContent->notices[0]->datetimeFrom);

        $this->assertObjectHasAttribute('datetimeTo', $responseContent->notices[0]);
        $this->assertSame('9999-01-01 04:00:00', $responseContent->notices[0]->datetimeTo);

        $this->assertObjectHasAttribute('publishedAt', $responseContent->notices[0]);
        $this->assertSame('2020-12-27 00:00:00', $responseContent->notices[0]->publishedAt);

        $this->assertObjectHasAttribute('updatedAt', $responseContent->notices[0]);
    }

    public function testNoticeFailure()
    {
        $notice = \Mockery::mock(Notice::class);
        $notice->shouldReceive('getNotice')->andThrow(new \Exception());
        $this->app->instance(Notice::class, $notice);

        $response = $this->get('/gourmet/v1/ja/takeout/notice');
        $response->assertStatus(500);
    }

    public function testGetTakeoutGenre()
    {
        $takeoutService = \Mockery::mock(TakeoutService::class);
        $takeoutService->shouldReceive('getTakeoutGenre')->andReturn([]);
        $this->app->instance(TakeoutService::class, $takeoutService);

        $response = $this->get('/gourmet/v1/ja/takeout/genre/m-sushi_sakanaryouri');
        $response->assertStatus(200);
    }

    public function testGetTakeoutGenreFailure()
    {
        $takeoutService = \Mockery::mock(TakeoutService::class);
        $takeoutService->shouldReceive('getTakeoutGenre')->andThrow(new \Exception());
        $this->app->instance(TakeoutService::class, $takeoutService);

        $response = $this->get('/gourmet/v1/ja/takeout/genre/m-sushi_sakanaryouri');
        $response->assertStatus(500);
    }

    public function testGetDetail()
    {
        $takeoutService = \Mockery::mock(TakeoutService::class);
        $takeoutService->shouldReceive('detailMenu')->andReturn([]);
        $this->app->instance(TakeoutService::class, $takeoutService);

        $response = $this->get('/gourmet/v1/ja/takeout/menu/1');
        $response->assertStatus(200);
    }

    public function testGetDetailFailure()
    {
        $takeoutService = \Mockery::mock(TakeoutService::class);
        $takeoutService->shouldReceive('detailMenu')->andThrow(new \Exception());
        $this->app->instance(TakeoutService::class, $takeoutService);

        $response = $this->get('/gourmet/v1/ja/takeout/menu/1');
        $response->assertStatus(500);
    }

    public function testGetStory()
    {
        $takeoutService = \Mockery::mock(TakeoutService::class);
        $takeoutService->shouldReceive('getStory')->andReturn([]);
        $this->app->instance(TakeoutService::class, $takeoutService);

        $response = $this->get('/gourmet/v1/ja/takeout/story');
        $response->assertStatus(200);
    }

    public function testGetStoryFailure()
    {
        $takeoutService = \Mockery::mock(TakeoutService::class);
        $takeoutService->shouldReceive('getStory')->andThrow(new \Exception());
        $this->app->instance(TakeoutService::class, $takeoutService);

        $response = $this->get('/gourmet/v1/ja/takeout/story');
        $response->assertStatus(500);
    }

    public function testReservationClose()
    {
        $takeoutService = \Mockery::mock(TakeoutService::class);
        $takeoutService->shouldReceive('close')->andReturn();
        $this->app->instance(TakeoutService::class, $takeoutService);

        $response = $this->post('/gourmet/v1/ja/takeout/reservation/close', ['reservationNo' => $this->reservation->app_cd . $this->reservation->id, 'tel' => '012012340000']);
        $response->assertStatus(200);

        $responseContent = json_decode($response->getContent());
        $this->assertSame(true, $responseContent->status);
    }

    public function testReservationCloseFailure()
    {
        $takeoutService = \Mockery::mock(TakeoutService::class);
        $takeoutService->shouldReceive('close')->andThrow(new \Exception());
        $this->app->instance(TakeoutService::class, $takeoutService);

        $response = $this->post('/gourmet/v1/ja/takeout/reservation/close', ['reservationNo' => $this->reservation->app_cd . $this->reservation->id, 'tel' => '012012340000']);
        $response->assertStatus(200);

        $responseContent = json_decode($response->getContent());
        $this->assertSame(false, $responseContent->status);
    }

    public function testSearchBox()
    {
        $response = $this->get('/gourmet/v1/ja/takeout/searchBox');
        $response->assertStatus(200);
    }

    public function testSearchBoxException()
    {
        $takeoutService = \Mockery::mock(TakeoutService::class);
        $takeoutService->shouldReceive('searchBox')->andThrow(new \Exception());
        $this->app->instance(TakeoutService::class, $takeoutService);

        $response = $this->get('/gourmet/v1/ja/takeout/searchBox');
        $response->assertStatus(500);
    }
}
