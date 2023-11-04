<?php

namespace Tests\Feature\Controller\Api\v1;

use App\Models\CancelFee;
use App\Models\CmThApplication;
use App\Models\CmThApplicationDetail;
use App\Models\Menu;
use App\Models\Notice;
use App\Models\OpeningHour;
use App\Models\Price;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationStore;
use App\Models\Store;
use App\Models\Vacancy;
use App\Services\RestaurantService;
use App\Services\RestaurantReservationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RestaurantControllerTest extends TestCase
{
    private $menu;
    private $store;
    private $date;
    private $time;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();

        $store = new Store();
        $store->regular_holiday = '11111111';
        $store->email_1 = 'gourmet-teststore1@adventure-inc.co.jp';
        $store->published = 1;
        $store->save();
        $this->store = $store;

        $openingHour = new OpeningHour();
        $openingHour->store_id = $store->id;
        $openingHour->week = '11111111';
        $openingHour->start_at = '07:00:00';
        $openingHour->end_at = '17:00:00';
        $openingHour->last_order_time = '17:00:00';
        $openingHour->save();

        $menu = new Menu();
        $menu->store_id = $this->store->id;
        $menu->provided_day_of_week = '11111111';
        $menu->sales_lunch_start_time = '07:00:00';
        $menu->sales_lunch_end_time = '17:00:00';
        $menu->published = 1;
        $menu->save();
        $this->menu = $menu;

        $price = new Price();
        $price->menu_id = $this->menu->id;
        $price->start_date = '2022-10-01';
        $price->end_date = '2099-12-31';
        $price->price = 1000;
        $price->save();

        $this->date = Carbon::now()->format('Y-m-d');
        $this->time = '10:00';
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testNotice()
    {
        $response = $this->get('/gourmet/v1/ja/restaurant/notice');
        $response->assertStatus(200);
    }

    public function testNoticeException()
    {
        $notice = \Mockery::mock(Notice::class);
        $notice->shouldReceive('getArea')->andThrow(new \Exception());
        $this->app->instance(Notice::class, $notice);

        $response = $this->get('/gourmet/v1/ja/restaurant/notice');
        $response->assertStatus(500);
    }

    public function testDetailMenu()
    {
        // 在庫なし
        $response = $this->get('/gourmet/v1/ja/restaurant/menu/' . $this->menu->id . '?visitDate=' . $this->date . '&visitTime=' . $this->time . '&visitPeople=2');
        $response->assertStatus(200)->assertJson(['result' => ['status' => false, 'message' => '空席がありません。']]);

        // 在庫登録
        $vacancy = new Vacancy();
        $vacancy->store_id = $this->store->id;
        $vacancy->headcount = 2;
        $vacancy->stock = 5;
        $vacancy->date = $this->date;
        $vacancy->time = $this->time . ':00';
        $vacancy->is_stop_sale = 0;
        $vacancy->save();

        // 在庫あり
        $response = $this->get('/gourmet/v1/ja/restaurant/menu/' . $this->menu->id . '?visitDate=' . $this->date . '&visitTime=' . $this->time . '&visitPeople=2');
        $response->assertStatus(200)->assertJson(['result' => ['status' => true, 'message' => 'ok']]);
    }

    public function testDetailMenuException()
    {
        $restaurantService = \Mockery::mock(RestaurantService::class);
        $restaurantService->shouldReceive('detailMenu')->andThrow(new \Exception());
        $this->app->instance(RestaurantService::class, $restaurantService);

        $response = $this->get('/gourmet/v1/ja/restaurant/menu/' . $this->menu->id . '?visitDate=' . $this->date . '&visitTime=' . $this->time . '&visitPeople=2');
        $response->assertStatus(500);
    }

    public function testSave()
    {
        $restaurantReservationService = \Mockery::mock(RestaurantReservationService::class);
        $restaurantReservationService->shouldReceive('save')->andReturn(['sessionToken' => 'testSessionToken', 'status' => true, 'paymentUrl' => 'test']);
        $this->app->instance(RestaurantReservationService::class, $restaurantReservationService);

        $response = $this->post('/gourmet/v1/ja/restaurant/reservation/save', [
            'customer' => [
                'firstName' => 'テスト名',
                'lastName' => 'テスト姓',
                'email' => 'gourmet-test1@adventure-inc.co.jp',
                'tel' => '0311112222',
                'request' => '卵アレルギーです。',
            ],
            'application' => [
                'persons' => 2,
                'menus' => [[
                    'menu' => [
                        'id' => $this->menu->id,
                        'count' => 1,
                    ],
                ]],
                'visitDate' => '2099-10-01',
                'visitTime' => '09:00',
            ],
        ]);
        $response->assertStatus(200);
    }

    public function testComplete()
    {
        $restaurantReservationService = \Mockery::mock(RestaurantReservationService::class);
        $restaurantReservationService->shouldReceive('complete')->andReturn(true);
        $this->app->instance(RestaurantReservationService::class, $restaurantReservationService);

        $response = $this->post('/gourmet/v1/ja/restaurant/reservation/complete', [
            'customer' => [
                'firstName' => 'テスト名',
                'lastName' => 'テスト姓',
                'email' => 'gourmet-test1@adventure-inc.co.jp',
                'tel' => '0311112222',
                'request' => '卵アレルギーです。',
            ],
            'application' => [
                'persons' => 2,
                'menus' => [[
                    'menu' => [
                        'id' => $this->menu->id,
                        'count' => 1,
                    ],
                ]],
                'visitDate' => '2099-10-01',
                'visitTime' => '09:00',
            ],
            'sessionToken' => 'testSessionToken',
        ]);
        $response->assertStatus(200);
    }

    public function testGetStory()
    {
        $response = $this->get('/gourmet/v1/ja/restaurant/story');
        $response->assertStatus(200);
    }

    public function testGetStoryException()
    {
        $restaurantService = \Mockery::mock(RestaurantService::class);
        $restaurantService->shouldReceive('getStory')->andThrow(new \Exception());
        $this->app->instance(RestaurantService::class, $restaurantService);

        $response = $this->get('/gourmet/v1/ja/restaurant/story');
        $response->assertStatus(500);
    }

    public function testSearchBox()
    {
        $response = $this->get('/gourmet/v1/ja/restaurant/searchBox');
        $response->assertStatus(200);
    }

    public function testSearchBoxException()
    {
        $restaurantService = \Mockery::mock(RestaurantService::class);
        $restaurantService->shouldReceive('searchBox')->andThrow(new \Exception());
        $this->app->instance(RestaurantService::class, $restaurantService);

        $response = $this->get('/gourmet/v1/ja/restaurant/searchBox');
        $response->assertStatus(500);
    }

    public function testGetRecommendation()
    {
        $response = $this->get('/gourmet/v1/ja/restaurant/recommend');
        $response->assertStatus(200);
    }

    public function testGetRecommendationException()
    {
        $restaurantService = \Mockery::mock(RestaurantService::class);
        $restaurantService->shouldReceive('getRecommendation')->andThrow(new \Exception());
        $this->app->instance(RestaurantService::class, $restaurantService);

        $response = $this->get('/gourmet/v1/ja/restaurant/recommend');
        $response->assertStatus(500);
    }

    public function testChange()
    {
        // テストデータ用意
        $reservation = $this->_crerateReservation();

        $vacancy = new Vacancy();
        $vacancy->store_id = $this->store->id;
        $vacancy->headcount = 2;
        $vacancy->stock = 5;
        $vacancy->date = '2099-10-01';
        $vacancy->time = '09:00:00';
        $vacancy->is_stop_sale = 0;
        $vacancy->save();

        $vacancy = new Vacancy();
        $vacancy->store_id = $this->store->id;
        $vacancy->headcount = 2;
        $vacancy->stock = 3;
        $vacancy->date = '2099-10-01';
        $vacancy->time = '10:00:00';
        $vacancy->is_stop_sale = 0;
        $vacancy->save();

        $CmThApplication = new CmThApplication();
        $CmThApplication->lang_id = 1;
        $CmThApplication->save();

        $cmThApplicationDetail = new CmThApplicationDetail();
        $cmThApplicationDetail->service_cd = 'gm';
        $cmThApplicationDetail->cm_application_id = $CmThApplication->cm_application_id;
        $cmThApplicationDetail->application_id = $reservation->id;
        $cmThApplicationDetail->save();

        $response = $this->post(
            '/gourmet/v1/ja/restaurant/reservation/change',
            [
                'visitDate' => '2099-10-01',
                'visitTime' => '09:00',
                'persons' => 2,
                'reservationId' => $reservation->id,
                'request' => '卵アレルギーです',
            ]
        );
        $response->assertStatus(200);
    }

    public function testCalcCancelFee()
    {
        // テストデータ用意
        $reservation = $this->_crerateReservation();
        $cancelFee = new CancelFee();
        $cancelFee->store_id = $this->store->id;
        $cancelFee->app_cd = 'RS';
        $cancelFee->apply_term_from = '2022-01-01';
        $cancelFee->apply_term_to = '2999-12-31';
        $cancelFee->visit = 'BEFORE';
        $cancelFee->published = 1;
        $cancelFee->save();

        $response = $this->get('/gourmet/v1/ja/restaurant/reservation/calcCancelFee?reservationId=' . $reservation->id);
        $response->assertStatus(200);
    }

    public function testCalcCancelFeeException()
    {
        $restaurantReservationService = \Mockery::mock(RestaurantReservationService::class);
        $restaurantReservationService->shouldReceive('calcCancelFee')->andThrow(new \Exception());
        $this->app->instance(RestaurantReservationService::class, $restaurantReservationService);

        $response = $this->get('/gourmet/v1/ja/restaurant/reservation/calcCancelFee?reservationId=123');
        $response->assertStatus(500);
    }

    public function testCalcPriceMenu()
    {
        // テストデータ用意
        $reservation = $this->_crerateReservation();

        // 引数は予約IDと予約日時
        $response = $this->get('/gourmet/v1/ja/restaurant/reservation/calcPriceMenu?reservationId=' . $reservation->id . '&visitDate=2099-10-01&visitTime=10:00&persons=3');
        $response->assertStatus(200);

        // 引数は予約IDのみ
        $response = $this->get('/gourmet/v1/ja/restaurant/reservation/calcPriceMenu?reservationId=' . $reservation->id);
        $response->assertStatus(200);
    }

    public function testCalcPriceMenuException()
    {
        $restaurantReservationService = \Mockery::mock(RestaurantReservationService::class);
        $restaurantReservationService->shouldReceive('calcPriceMenu')->andThrow(new \Exception());
        $this->app->instance(RestaurantReservationService::class, $restaurantReservationService);

        $response = $this->get('/gourmet/v1/ja/restaurant/reservation/calcPriceMenu?reservationId=123');
        $response->assertStatus(500);
    }

    public function testCancel()
    {
        // キャンセル失敗
        {
            $reservation = $this->_crerateReservation(0);
            $response = $this->post('/gourmet/v1/ja/restaurant/reservation/cancel', [
                'reservationId' => $reservation->id,
            ]);
            $response->assertStatus(200)->assertJson([
                'status' => true,
                'message' => '予約のキャンセルに失敗しました。',
            ]);
        }

        // キャンセル成功
        {
            $reservation = $this->_crerateReservation(0);
            $CmThApplication = new CmThApplication();
            $CmThApplication->lang_id = 1;
            $CmThApplication->save();

            $cmThApplicationDetail = new CmThApplicationDetail();
            $cmThApplicationDetail->service_cd = 'gm';
            $cmThApplicationDetail->cm_application_id = $CmThApplication->cm_application_id;
            $cmThApplicationDetail->application_id = $reservation->id;
            $cmThApplicationDetail->save();

            $response = $this->post('/gourmet/v1/ja/restaurant/reservation/cancel', [
                'reservationId' => $reservation->id,
            ]);
            $response->assertStatus(200)->assertJson([
                'status' => true,
                'message' => '予約のキャンセルを受け付けました。',
            ]);
        }
    }

    public function testMenuVacancy()
    {
        // テストデータ用意
        $reservation = $this->_crerateReservation();
        $response = $this->get('/gourmet/v1/ja/restaurant/menuVacancy?reservationId=' . $reservation->id . '&visitDate=2099-10-01&menuId=' . $this->menu->id);
        $response->assertStatus(200);
    }

    public function testMenuVacancyException()
    {
        $restaurantService = \Mockery::mock(RestaurantService::class);
        $restaurantService->shouldReceive('menuVacancy')->andThrow(new \Exception());
        $this->app->instance(RestaurantService::class, $restaurantService);
        $reservation = $this->_crerateReservation();
        $response = $this->get('/gourmet/v1/ja/restaurant/menuVacancy?reservationId=' . $reservation->id . '&visitDate=2099-10-01&menuId=' . $this->menu->id);
        $response->assertStatus(500);
    }

    public function testDirectPayment()
    {
        $reservation = $this->_crerateReservation(0);

        $restaurantReservationService = \Mockery::mock(RestaurantReservationService::class);
        $restaurantReservationService->shouldReceive('directPayment')->andReturn(['message' => '決済用URLの発行に成功しました。', 'status' => true, 'paymentUrl' => 'test']);
        $this->app->instance(RestaurantReservationService::class, $restaurantReservationService);

        $response = $this->post('/gourmet/v1/ja/reservation/directPayment', [
            'reservationId' => $reservation->id,
        ]);
        $response->assertStatus(200);
    }

    private function _crerateReservation($menuUnitPrice = 1000)
    {
        $reservation = new Reservation();
        $reservation->app_cd = 'RS';
        $reservation->reservation_status = 'RESERVE';               // 申込
        $reservation->payment_status = 'UNPAID';                    // 未入金
        $reservation->pick_up_datetime = '2099-10-01 10:00:00';
        $reservation->persons = 2;
        $reservation->total = $menuUnitPrice * 2;
        $reservation->save();

        $reservationStore = new ReservationStore();
        $reservationStore->store_id = $this->store->id;
        $reservationStore->reservation_id = $reservation->id;
        $reservationStore->save();

        $reservationMenu = new ReservationMenu();
        $reservationMenu->reservation_id = $reservation->id;
        $reservationMenu->menu_id = $this->menu->id;
        $reservationMenu->unit_price = $menuUnitPrice;
        $reservationMenu->price = $menuUnitPrice * 2;
        $reservationMenu->count = 2;
        $reservationMenu->save();

        return $reservation;
    }
}
