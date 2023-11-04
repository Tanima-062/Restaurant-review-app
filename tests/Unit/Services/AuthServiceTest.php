<?php

namespace Tests\Unit\Services;

use App\Http\Requests\Api\v1\AuthReviewRequest;
use App\Models\CmThApplication;
use App\Models\CmThApplicationDetail;
use App\Models\CmTmUser;
use App\Models\Genre;
use App\Models\GenreGroup;
use App\Models\Menu;
use App\Models\OpeningHour;
use App\Models\Option;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationOption;
use App\Models\ReservationStore;
use App\Models\Review;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    private $authService;

    public function setUp(): void
    {
        parent::setUp();
        $this->authService = $this->app->make('App\Services\AuthService');
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testGetMypage()
    {
        // 無効な予約情報で呼び出し
        $resValues = [];
        $this->assertFalse($this->authService->getMypage('RS1234567890123456789', '0612345678', $resValues));
        $this->assertArrayHasKey('status', $resValues);
        $this->assertArrayHasKey('message', $resValues);
        $this->assertFalse($resValues['status']);
        $this->assertSame('予約内容が見つかりませんでした。', $resValues['message']);

        // テスト用予約データを登録し、関数呼び出し(データ不足のため、エラー)
        list($reservation, $reservationOption, $genre) = $this->_createReservation('RS', false, false);
        $this->assertFalse($this->authService->getMypage('RS' . $reservation->id, '0612345678', $resValues));
        $this->assertArrayHasKey('status', $resValues);
        $this->assertArrayHasKey('message', $resValues);
        $this->assertFalse($resValues['status']);
        $this->assertSame('注文をできませんでした。', $resValues['message']);

        // テスト用予約データを登録し、関数呼び出し（正常）
        list($reservation, $reservationOption, $genre)  = $this->_createReservation('RS');
        $this->assertTrue($this->authService->getMypage('RS' . $reservation->id, '0612345678', $resValues));
        $this->assertArrayHasKey('status', $resValues);
        $this->assertArrayHasKey('message', $resValues);
        $this->assertArrayHasKey('reservation', $resValues);
        $this->assertTrue($resValues['status']);
        $this->assertSame('下記日時にご来店ください。', $resValues['message']);
        $this->assertSame($reservation->id, $resValues['reservation']['id']);
        $this->assertArrayHasKey('reservationStore', $resValues['reservation']);
        $this->assertArrayHasKey('openingHours', $resValues['reservation']['reservationStore']);
        $this->assertArrayHasKey('genres', $resValues['reservation']['reservationStore']);
        $this->assertArrayHasKey('reservationMenus', $resValues['reservation']);
        $this->assertArrayHasKey('reservationOptions', $resValues['reservation']['reservationMenus'][0]);
        $this->assertSame($reservation->reservationStore->id, $resValues['reservation']['reservationStore']['id']);
        $this->assertSame($genre->id, $resValues['reservation']['reservationStore']['genres'][0]['id']);
        $this->assertSame($reservationOption->id, $resValues['reservation']['reservationStore']['openingHours'][0]['id']);
        $this->assertSame($reservation->reservationMenus()->first()->id, $resValues['reservation']['reservationMenus'][0]['id']);
        $this->assertSame($reservation->reservationMenus()->first()->reservationOptions->first()->id, $resValues['reservation']['reservationMenus'][0]['reservationOptions'][0]['id']);
    }

    public function testRegisterReview()
    {
        // 無効な予約情報で呼び出し（エラー）
        $request = new AuthReviewRequest();
        $request->merge([
            'menuId' => null,
            'reservationNo' => null,
            'evaluationCd' => null,
            'body' => null,
            'isRealName' => true,
        ]);
        $resValues = [];
        $this->assertFalse($this->authService->registerReview($request, $resValues));
        $this->assertFalse($resValues['status']);
        $this->assertSame('アンケートを登録できませんでした。', $resValues['message']);

        // テスト用予約データを登録し、ログイン後、関数呼び出し（正常）
        // ログイン
        $user = $this->_createCmTmUser();
        $loginRequest = new Request();
        $loginRequest->merge([
            'loginId' => 'gourmet-test1@adventure-inc.co.jp',
            'password' =>  'gourmettest123',
        ]);
        $login = $this->authService->userLogin->login($loginRequest);
        // テスト用予約データ登録
        list($reservation, $reservationOption, $genre)  = $this->_createReservation('RS', true, true, $user->user_id);
        $request = new AuthReviewRequest();
        $request->merge([
            'menuId' => $reservation->reservationMenus()->first()->id,
            'reservationNo' => "RS{$reservation->id}",
            'evaluationCd' => 'GOOD_DEAL',
            'body' => 'とても美味しかったです',
            'isRealName' => true,
        ]);
        // 関数呼び出し
        $resValues = [];
        $this->assertTrue($this->authService->registerReview($request, $resValues));
        $this->assertTrue($resValues['status']);
        $this->assertSame('アンケートを登録しました。', $resValues['message']);
        $this->assertSame(1, Review::where('reservation_id', $reservation->id)->get()->count());    // データが登録されていることを確認
    }

    public function testToken()
    {
        // method getApiToken
        $ret = ['email' => 'gourmet-test1@adventure-inc.co.jp'];
        $this->assertTrue($this->authService->getApiToken($ret));
        $this->assertArrayHasKey('api_token', $ret);
        $this->assertNotEmpty($ret['api_token']);

        // method getUserInfo
        // 有効なトークンを渡す
        $info = null;
        $this->assertTrue($this->authService->getUserInfo($ret['api_token'], $info));
        $this->assertArrayHasKey('email', $ret);
        $this->assertArrayHasKey('api_token', $ret);
        $this->assertSame('gourmet-test1@adventure-inc.co.jp', $info['email']);
        $this->assertSame($ret['api_token'], $info['api_token']);

        // 無効なトークンを渡す
        $info = null;
        $this->assertFalse($this->authService->getUserInfo('testdummytoken', $info));
        $this->assertNull($info);

        // method clearToken
        $this->assertTrue($this->authService->clearToken($ret['api_token']));
        $this->assertEmpty(Redis::get(config('takeout.apiToken.prefix') . $ret['api_token']));  // Redisからなくなったことを確認
    }

    private function _createReservation($appCd, $addReservationOther = true, $addGenreGroup = true, $userId = null)
    {
        $store = new Store();
        $store->save();

        $openingHour = new OpeningHour();
        $openingHour->store_id = $store->id;
        $openingHour->week = '10111110';                // 火曜だけ休業（祝日は休み）
        $openingHour->opening_hour_cd = 'ALL_DAY';
        $openingHour->start_at = '09:00:00';
        $openingHour->end_at = '21:00:00';
        $openingHour->last_order_time = '20:30:00';
        $openingHour->save();

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->app_cd = $appCd;
        $menu->save();

        $option = new Option();
        $option->menu_id = $menu->id;
        $option->price = 100;
        $option->save();

        $reservation = new Reservation();
        $reservation->app_cd = $appCd;
        $reservation->total = 2200;
        $reservation->tax = 10;
        $reservation->persons = 2;
        $reservation->last_name = 'グルメ';
        $reservation->first_name = '太郎';
        $reservation->email = 'gourmet-test@adventure-inc.co.jp';
        $reservation->tel = '0612345678';
        $reservation->reservation_status = 'RESERVE';
        $reservation->payment_status = 'AUTH';
        $reservation->payment_method = 'CREDIT';
        $reservation->created_at = '2022-10-02 12:00:00';
        $reservation->pick_up_datetime = '2022-10-02 15:00:00';
        $reservation->is_close = 0;
        $reservation->save();

        if ($addReservationOther) {
            $reservationStore = new reservationStore();
            $reservationStore->reservation_id = $reservation->id;
            $reservationStore->store_id = $store->id;
            $reservationStore->name = 'テスト店舗';
            $reservationStore->save();

            $reservationMenu = new ReservationMenu();
            $reservationMenu->reservation_id = $reservation->id;
            $reservationMenu->menu_id = $menu->id;
            $reservationMenu->unit_price = 1000;
            $reservationMenu->count = 2;
            $reservationMenu->price = 2000;
            $reservationMenu->save();

            $reservationOption = new ReservationOption();
            $reservationOption->reservation_menu_id = $reservationMenu->id;
            $reservationOption->option_id = $option->id;
            $reservationOption->unit_price = 100;
            $reservationOption->count = 2;
            $reservationOption->price = 200;
            $reservationOption->save();
        }

        $genreLevel3 = null;
        if ($addGenreGroup) {
            $genreLevel3 = new Genre();
            $genreLevel3->name = 'test3';
            $genreLevel3->genre_cd = 'test3';
            $genreLevel3->app_cd = 'TORS';
            $genreLevel3->level = 3;
            $genreLevel3->published = 1;
            $genreLevel3->path = '/b-cooking/test2';
            $genreLevel3->save();

            $genreLevel4 = new Genre();
            $genreLevel4->name = 'test4';
            $genreLevel4->genre_cd = 'test4';
            $genreLevel4->app_cd = 'TORS';
            $genreLevel4->level = 4;
            $genreLevel4->published = 1;
            $genreLevel4->path = '/b-cooking/test2/test3';
            $genreLevel4->save();

            $genreGroup = new GenreGroup();
            $genreGroup->store_id = $store->id;
            $genreGroup->genre_id = $genreLevel4->id;
            $genreGroup->is_delegate = 0;
            $genreGroup->save();
        }

        if (!empty($userId)) {
            $cmThApplication = new CmThApplication();
            $cmThApplication->user_id = $userId;
            $cmThApplication->save();

            $cmThApplicationDetail = new CmThApplicationDetail();
            $cmThApplicationDetail->cm_application_id = $cmThApplication->cm_application_id;
            $cmThApplicationDetail->application_id = $reservation->id;
            $cmThApplicationDetail->service_cd = 'gm';
            $cmThApplicationDetail->save();
        }

        return [$reservation, $openingHour, $genreLevel3];
    }

    private function _createCmTmUser()
    {
        $cmTmUser = new CmTmUser();
        $cmTmUser->email_enc = 'gourmet-test1@adventure-inc.co.jp';
        $cmTmUser->password_enc = hash('sha384', 'gourmettest123');
        $cmTmUser->member_status = 1;
        $cmTmUser->save();
        return $cmTmUser;
    }
}
