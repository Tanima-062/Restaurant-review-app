<?php

namespace Tests\Feature\Controller\Api\v1;

use App\Models\CmThApplication;
use App\Models\CmThApplicationDetail;
use App\Models\Reservation;
use App\Models\ReservationStore;
use App\Models\Staff;
use App\Modules\StaffLogin;
use App\Services\DishUpService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DishUpControllerTest extends TestCase
{
    private $reservation;
    private $staff;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $reservation = new Reservation();
        $reservation->reservation_status = config('code.reservationStatus.reserve.key');
        $reservation->pick_up_datetime = '2020-01-01';
        $reservation->save();
        $this->reservation = $reservation;

        $reservationStore = new ReservationStore();
        $reservationStore->reservation_id = $this->reservation->id;
        $reservationStore->store_id = 1;
        $reservationStore->save();

        $staff = new Staff();
        $staff->name = 'y-nakazatoo';
        $staff->username = 'y-nakazato';
        $staff->password = '$2y$10$eekYCOy4b6h175LDTTla.uvWg.9F1.eZr9fCopO/bvaY6DIChB.VW';
        $staff->staff_authority_id = 1;
        $staff->published = 1;
        $staff->remember_token = 'G6IF5m4ZA8LXyTAfNPgucBYdDNqfuoi9YGkHnJmAGzNGK1GHR6Vx1Xghx1s1';
        $staff->save();
        $this->staff = $staff;

        $user = $this->post('/gourmet/v1/ja/auth/login', [
            'loginId' => 'y-nakazato20201026@adventure-inc.co.jp',
            'password' => 'nakazato20201026',
        ]);
        $user->assertStatus(200);

        $cmThApplication = new CmThApplication();
        $cmThApplication->user_id = $user['userId'];
        $cmThApplication->lang_id = 1;
        $cmThApplication->ip = 0;
        $cmThApplication->user_agent = '';
        $cmThApplication->save();

        $cmThApplicationDetail = new CmThApplicationDetail();
        $cmThApplicationDetail->cm_application_id = $cmThApplication->cm_application_id;
        $cmThApplicationDetail->application_id = $this->reservation->id;
        $cmThApplicationDetail->service_cd = config('code.serviceCd');
        $cmThApplicationDetail->save();
    }

    public function tearDown(): void
    {
        DB::rollBack();

        parent::tearDown();
    }

    public function testStartCooking()
    {
        $this->login();
        $response = $this->post('/gourmet/v1/ja/dish-up/startCooking', ['reservationId' => $this->reservation->id]);
        $response->assertStatus(200);
    }

    public function testStartCookingException()
    {
        $this->login();
        $dishUpService = \Mockery::mock(DishUpService::class);
        $dishUpService->shouldReceive('startCooking')->andReturn(false);
        $this->app->instance(DishUpService::class, $dishUpService);

        $response = $this->post('/gourmet/v1/ja/dish-up/startCooking', ['reservationId' => '1']);
        $response->assertStatus(500);
    }

    public function testList()
    {
        $this->login();
        $response = $this->get('/gourmet/v1/ja/dish-up/list'.'?reservationDate='.$this->reservation->pick_up_datetime);
        $response->assertStatus(200);
    }

    public function testListException()
    {
        $this->login();
        $dishUpService = \Mockery::mock(DishUpService::class);
        $dishUpService->shouldReceive('list')->andThrow(new \Exception());
        $this->app->instance(DishUpService::class, $dishUpService);

        $response = $this->get('/gourmet/v1/ja/dish-up/list'.'?reservationDate='.$this->reservation->pick_up_datetime);
        $response->assertStatus(500);
    }

    private function login()
    {
        // ログイン
        $response = $this->post('/gourmet/v1/en/dish-up/login', [
                'userName' => 'y-nakazatoo',
                'password' => 'y-nakazato',
            ]);
        $response->assertStatus(200);

        $this->staff->rememberToken = $response['rememberToken'];
        $this->withHeaders([
            'Authorization' => 'Bearer '.$this->staff->rememberToken,
        ]);

        // ログイン状態であるのでエラーにならない
        $response = $this->get('/gourmet/v1/ja/dish-up/login');
        $response->assertStatus(200);
    }

    private function logout()
    {
        $info = null;
        $r = StaffLogin::getUserInfo($this->staff->rememberToken, $info);
        if ($r) {
            $this->withHeaders([
                'Authorization' => 'Bearer '.$this->staff['remember_token'],
            ]);
            // ログアウト
            $response = $this->post('/gourmet/v1/ja/dish-up/logout');
            $response->assertStatus(200);
        }
        // 未ログイン状態
        $response = $this->get('/gourmet/v1/en/dish-up/login');

        $response->assertStatus(401)->assertJson(['error' => 'ログインしていません。']);
    }

    public function testSession()
    {
        // ログアウト状態にする
        $this->logout();
        // ログイン
        $this->login();
        // ログアウト
        $this->logout();
    }

    public function testLoginError()
    {
        $response = $this->post('/gourmet/v1/en/dish-up/login', [
            'userName' => 'y-nakazatoo',
            'password' => 'y-nakazato00000000',
        ]);
        $response->assertStatus(401)->assertJson(['error' => 'ログインに失敗しました。']);
    }
}
