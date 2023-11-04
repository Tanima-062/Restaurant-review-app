<?php

namespace Tests\Unit\Models;

use App\Libs\Cipher;
use App\Models\CancelDetail;
use App\Models\CommissionRate;
use App\Models\Menu;
use App\Models\Option;
use App\Models\PaymentDetail;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationStore;
use App\Models\Review;
use App\Models\Staff;
use App\Models\SettlementCompany;
use App\Models\Store;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    private $reservation;
    private $testOptionId;  //RS用
    private $testOptionId2; //TO用
    private $testMenuId;    //RS用
    private $testMenuId2;   //TO用
    private $testReservationId;
    private $testReservationId2;
    private $testReservationStoreId;
    private $testReservationMenuId;
    private $testReviewId;
    private $testPaymentDetailId;
    private $testCancelDetailId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->reservation = new Reservation();

        $this->_createReservation();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testReservationMenus()
    {
        $testReservationMenuId = $this->testReservationMenuId;
        $result = $this->reservation::whereHas('reservationMenus', function ($query) use ($testReservationMenuId) {
            $query->where('id', $testReservationMenuId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationId, $result[0]['id']);
    }

    public function testReservationStore()
    {
        $testReservationStoreId = $this->testReservationStoreId;
        $result = $this->reservation::whereHas('reservationStore', function ($query) use ($testReservationStoreId) {
            $query->where('id', $testReservationStoreId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationId, $result[0]['id']);
    }

    public function testReview()
    {
        $testReviewId = $this->testReviewId;
        $result = $this->reservation::whereHas('review', function ($query) use ($testReviewId) {
            $query->where('id', $testReviewId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationId, $result[0]['id']);
    }

    public function testPaymentDetails()
    {
        $testPaymentDetailId = $this->testPaymentDetailId;
        $result = $this->reservation::whereHas('paymentDetails', function ($query) use ($testPaymentDetailId) {
            $query->where('id', $testPaymentDetailId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationId, $result[0]['id']);
    }

    public function testCancelDetails()
    {
        $testCancelDetailId = $this->testCancelDetailId;
        $result = $this->reservation::whereHas('cancelDetails', function ($query) use ($testCancelDetailId) {
            $query->where('id', $testCancelDetailId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationId, $result[0]['id']);
    }

    public function testGetFullNameAttribute()
    {
        $result = $this->reservation::find($this->testReservationId);
        $this->assertSame('グルメ 太郎', $result->getFullNameAttribute());
    }

    public function testGetReservationNoAttribute()
    {
        $result = $this->reservation::find($this->testReservationId);
        $this->assertSame('RS' . $this->testReservationId, $result->getReservationNoAttribute());
    }

    public function testDecryptionAttribute()
    {
        // 暗号化文字列を用意し、復号結果と同じか比較
        // getFirstNameAttribute
        $str = Cipher::encrypt('グルメ');
        $this->assertSame('グルメ', $this->reservation->getFirstNameAttribute($str));

        // getLastNameAttribute
        $str = Cipher::encrypt('太郎');
        $this->assertSame('太郎', $this->reservation->getLastNameAttribute($str));

        // getEmailAttribute
        $str = Cipher::encrypt('gourmet-test@adventure-inc.co.jp');
        $this->assertSame('gourmet-test@adventure-inc.co.jp', $this->reservation->getEmailAttribute($str));

        // getTelAttribute
        $str = Cipher::encrypt('0312345678');
        $this->assertSame('0312345678', $this->reservation->getTelAttribute($str));

        // getRequestAttribute
        $str = Cipher::encrypt('アレルギーがあります');
        $this->assertSame('アレルギーがあります', $this->reservation->getRequestAttribute($str));
    }

    public function testGetIsCloseStrAttribute()
    {
        $result = $this->reservation::find($this->testReservationId);
        $this->assertSame('済', $result->getIsCloseStrAttribute());

        $result = $this->reservation::find($this->testReservationId2);
        $this->assertSame('未', $result->getIsCloseStrAttribute());
    }

    public function testSetAttribute()
    {
        // first_nameを変更
        $reservation = $this->reservation::find($this->testReservationId);
        $reservation->setFirstNameAttribute('gourmet');
        $this->assertSame('gourmet', $reservation->first_name);

        // last_nameを変更
        $reservation = $this->reservation::find($this->testReservationId);
        $reservation->setLastNameAttribute('tarou');
        $this->assertSame('tarou', $reservation->last_name);

        // telを変更
        $reservation = $this->reservation::find($this->testReservationId);
        $reservation->setTelAttribute('0398765432');
        $this->assertSame('0398765432', $reservation->tel);

        // emailを変更
        $reservation = $this->reservation::find($this->testReservationId);
        $reservation->setEmailAttribute('gourmet-test2@adventure-inc.co.jp');
        $this->assertSame('gourmet-test2@adventure-inc.co.jp', $reservation->email);

        // requestを変更
        $reservation = $this->reservation::find($this->testReservationId);
        $reservation->setRequestAttribute('卵アレルギーがあります');
        $this->assertSame('卵アレルギーがあります', $reservation->request);
    }

    public function testScopeAdminSearchFilter()
    {
        // 店舗IDで絞り込み
        $valid = [
            'id' => "RS{$this->testReservationId}\tTO{$this->testReservationId2}",
        ];
        $result = $this->reservation::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(2, $result->count());

        // 店舗ID＋予約ステータス（注文確定）
        $valid = [
            'id' => "RS{$this->testReservationId}\tTO{$this->testReservationId2}",
            'reservation_status' => 'ENSURE',
        ];
        $result = $this->reservation::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationId, $result[0]['id']);

        // 店舗ID＋入金ステータス（計上）
        $valid = [
            'id' => "RS{$this->testReservationId}\tTO{$this->testReservationId2}",
            'payment_status' => 'PAYED',
        ];
        $result = $this->reservation::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationId, $result[0]['id']);

        // 店舗ID＋first_name
        $valid = [
            'id' => "RS{$this->testReservationId}\tTO{$this->testReservationId2}",
            'first_name' => '太郎',
        ];
        $result = $this->reservation::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationId, $result[0]['id']);

        // 店舗ID＋last_name
        $valid = [
            'id' => "RS{$this->testReservationId}\tTO{$this->testReservationId2}",
            'last_name' => 'グルメ',
        ];
        $result = $this->reservation::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationId, $result[0]['id']);

        // 店舗ID＋email
        $valid = [
            'id' => "RS{$this->testReservationId}\tTO{$this->testReservationId2}",
            'email' => 'gourmet-test@adventure-inc.co.jp',
        ];
        $result = $this->reservation::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationId, $result[0]['id']);

        // 店舗ID＋tel
        $valid = [
            'id' => "RS{$this->testReservationId}\tTO{$this->testReservationId2}",
            'tel' =>  '0312345678',
        ];
        $result = $this->reservation::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationId, $result[0]['id']);

        // 店舗ID＋created_at_from
        $valid = [
            'id' => "RS{$this->testReservationId}\tTO{$this->testReservationId2}",
            'created_at_from' => '2022-10-01 11:00:00',
        ];
        $result = $this->reservation::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationId2, $result[0]['id']);

        // 店舗ID＋created_at_to
        $valid = [
            'id' => "RS{$this->testReservationId}\tTO{$this->testReservationId2}",
            'created_at_to' => '2022-10-02 00:00:00',
        ];
        $result = $this->reservation::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationId, $result[0]['id']);

        // 店舗ID＋pick_up_datetime_from
        $valid = [
            'id' => "RS{$this->testReservationId}\tTO{$this->testReservationId2}",
            'pick_up_datetime_from' => '2022-10-02 00:00:00',
        ];
        $result = $this->reservation::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationId2, $result[0]['id']);

        // 店舗ID＋pick_up_datetime_to
        $valid = [
            'id' => "RS{$this->testReservationId}\tTO{$this->testReservationId2}",
            'pick_up_datetime_to' => '2022-10-02 00:00:00',
        ];
        $result = $this->reservation::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationId, $result[0]['id']);

        // 店舗ID＋store_name
        $valid = [
            'id' => "RS{$this->testReservationId}\tTO{$this->testReservationId2}",
            'store_name' => 'テスト店舗A',
        ];
        $result = $this->reservation::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationId, $result[0]['id']);

        // 店舗ID＋store_tel
        $valid = [
            'id' => "RS{$this->testReservationId}\tTO{$this->testReservationId2}",
            'store_tel' => '0312341234',
        ];
        $result = $this->reservation::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationId, $result[0]['id']);
    }

    public function testSaveRestaurant()
    {
        $userInfo = [
            'firstName' => '太朗',
            'lastName' => 'グルメ',
            'email' => 'gourmet-test@adventure-inc.co.jp',
            'tel' => '0698765432',
            'request' => '卵アレルギーです',
        ];
        $menu = Menu::where('id', $this->testMenuId)->first()->toArray();
        $menus = [[
            'menu' => $menu,
            'options' => [array_merge(Option::find($this->testOptionId)->toArray(), ['count' => 2])],
        ]];
        $info = [
            'customer' => $userInfo,
            'application' => [
                'visitDate' => '2022-10-01',
                'visitTime' => '09:00:00',
                'persons' => '2',
                'menus' => [$menus[0]],
            ],
        ];
        $menuInfo = [];
        $menuInfo[$this->testMenuId] = $menu;
        $menuInfo[$this->testMenuId]['menuPrice']['price'] = 1000;

        $result = $this->reservation->saveRestaurant($info, $menuInfo);
        $this->assertIsObject($result);
        $this->assertSame(2200, $result->total);
    }

    public function testSaveRestaurantThrowable()
    {
        $userInfo = [
            'firstName' => '太朗',
            'lastName' => 'グルメ',
            'email' => 'gourmet-test@adventure-inc.co.jp',
            'tel' => '0698765432',
            'request' => '卵アレルギーです',
        ];
        $menu = Menu::where('id', $this->testMenuId)->first()->toArray();
        $menus = [[
            'menu' => $menu,
            'options' => [array_merge(Option::find($this->testOptionId)->toArray(), ['count' => 2])],
        ]];
        $info = [
            'customer' => $userInfo,
            'application' => [
                'visitDate' => '2022-10-01',
                'visitTime' => '09:00:00',
                'persons' => '2',
                'menus' => [$menus[0]],
            ],
        ];
        $menuInfo = []; // 空配列にする事で、例外エラーを発生させる

        try {
            $result = $this->reservation->saveRestaurant($info, $menuInfo);
            $this->assertTrue(false);   // 上記処理でThrowable発生のため、ここは通過しない
        } catch (\Throwable $e) {
            $this->assertTrue(true);    // 例外発生し、ここを通過したことを確認
        }
    }

    public function testSaveTakeout()
    {
        $userInfo = [
            'firstName' => '太朗',
            'lastName' => 'グルメ',
            'email' => 'gourmet-test@adventure-inc.co.jp',
            'tel' => '0698765432',
            'request' => '卵アレルギーです',
        ];
        $menu = array_merge(Menu::where('id', $this->testMenuId2)->first()->toArray(), ['count' => 2]);
        $menus = [[
            'menu' => $menu,
            'options' => [Option::find($this->testOptionId2)->toArray()],
        ]];
        $info = [
            'customer' => $userInfo,
            'application' => [
                'pickUpDate' => '2022-10-01',
                'pickUpTime' => '09:00:00',
                'menus' => [$menus[0]],
            ],
        ];
        $menuInfo = [];
        $menuInfo[$this->testMenuId2] = $menu;
        $menuInfo[$this->testMenuId2]['menuPrice']['price'] = 1000;

        $result = $this->reservation->saveTakeout($info, $menuInfo);
        $this->assertIsObject($result);
        $this->assertSame(2200, $result->total);
    }

    public function testSaveTakeoutThrowable()
    {
        $userInfo = [
            'firstName' => '太朗',
            'lastName' => 'グルメ',
            'email' => 'gourmet-test@adventure-inc.co.jp',
            'tel' => '0698765432',
            'request' => '卵アレルギーです',
        ];
        $menu = array_merge(Menu::where('id', $this->testMenuId2)->first()->toArray(), ['count' => 2]);
        $menus = [[
            'menu' => $menu,
            'options' => [Option::find($this->testOptionId2)->toArray()],
        ]];
        $info = [
            'customer' => $userInfo,
            'application' => [
                'pickUpDate' => '2022-10-01',
                'pickUpTime' => '09:00:00',
                'menus' => [$menus[0]],
            ],
        ];

        $menuInfo = []; // 空配列にする事で、例外エラーを発生させる

        try {
            $result = $this->reservation->saveTakeout($info, $menuInfo);
            $this->assertTrue(false);   // 上記処理でThrowable発生のため、ここは通過しない
        } catch (\Throwable $e) {
            $this->assertTrue(true);    // 例外発生し、ここを通過したことを確認
        }
    }

    public function testGetMypage()
    {
        // 無効な予約情報（電話番号が正しくない）
        $reservationNo = 'RS' . $this->testReservationId;
        $tel = '0123456789';
        $result = $this->reservation->getMypage($reservationNo, $tel);
        $this->assertNull($result);

        // 有効な予約情報
        $reservationNo = 'RS' . $this->testReservationId;
        $tel = '0312345678';
        $result = $this->reservation->getMypage($reservationNo, $tel);
        $this->assertIsObject($result);
        $this->assertSame($this->testReservationId, $result->id);
    }

    public function testStartCooking()
    {
        // 注文確定に変更
        {
            // ステータスを「申込」に変更する
            $reservation = $this->reservation::find($this->testReservationId);
            $reservation->reservation_status = 'RESERVE';
            $reservation->save();

            $this->reservation->startCooking($this->testReservationId);

            // 「受注確定」になっているか確認
            $reservation = $this->reservation::find($this->testReservationId);
            $this->assertSame('ENSURE', $reservation->reservation_status);
        }

        // 例外エラー
        {
            // ステータスを「申込」に変更する
            $reservation = $this->reservation::find($this->testReservationId);
            $reservation->reservation_status = 'CANCEL';
            $reservation->save();

            try {
                $this->reservation->startCooking($this->testReservationId);
            } catch (Exception $e) {
                $this->assertSame('already cancel', $e->getMessage());
            }
        }
    }


    public function testGetTakeoutReservationNo()
    {
        $result = $this->reservation->getTakeoutReservationNo($this->testReservationId2);
        $this->assertSame('TO' . $this->testReservationId2, $result);
    }

    public function testGetRestaurantReservationNo()
    {
        $result = $this->reservation->getRestaurantReservationNo($this->testReservationId);
        $this->assertSame('RS' . $this->testReservationId, $result);
    }

    public function testGetReservationId()
    {
        $result = $this->reservation->getReservationId('TO' . $this->testReservationId2);
        $this->assertSame($this->testReservationId2, $result);
    }

    public function testGetDeviceAttribute()
    {
        // スマホ（iphone)
        $reservation = $this->reservation::find($this->testReservationId);
        $reservation->user_agent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 11_2_1 like Mac OS X) AppleWebKit/604.4.7 (KHTML, like Gecko) Version/11.0 Mobile/15C153 Safari/604.1';
        $result = $reservation->getDeviceAttribute();
        $this->assertSame('スマホ', $result);

        // スマホ（Android)
        $reservation = $this->reservation::find($this->testReservationId);
        $reservation->user_agent = 'Mozilla/5.0 (Linux; U; Android 1.5; ja-jp; GDDJ-09 Build/CDB56) AppleWebKit/528.5+ (KHTML, like Gecko) Version/3.1.2 Mobile Safari/525.20.1';
        $result = $reservation->getDeviceAttribute();
        $this->assertSame('スマホ', $result);

        // スマホ（Windows Phone)
        $reservation = $this->reservation::find($this->testReservationId);
        $reservation->user_agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; KDDI-TS01; Windows Phone 6.5.3.5)';
        $result = $reservation->getDeviceAttribute();
        $this->assertSame('スマホ', $result);

        // スマホ（BlackBerry)
        $reservation = $this->reservation::find($this->testReservationId);
        $reservation->user_agent = 'BlackBerry9000/4.6.0.294 Profile/MIDP-2.0 Configuration/CLDC-1.1 VendorID/220';
        $result = $reservation->getDeviceAttribute();
        $this->assertSame('スマホ', $result);

        // PC
        $reservation = $this->reservation::find($this->testReservationId);
        $reservation->user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.2.149.27 Safari/525.13';
        $result = $reservation->getDeviceAttribute();
        $this->assertSame('PC', $result);

        // user agentなし
        $reservation = $this->reservation::find($this->testReservationId);
        $reservation->user_agent = '';
        $result = $reservation->getDeviceAttribute();
        $this->assertSame('', $result);
    }

    public function testScopePastReserve()
    {
        $result = $this->reservation::PastReserve('gourmet-test@adventure-inc.co.jp')->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationId, $result[0]['id']);
    }

    public function testScopeStillNotClose()
    {
        $result = $this->reservation::StillNotClose('2022-10-02 18:00:00')->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationId2, $result[0]['id']);
    }

    public function testScopeList()
    {
        // ユーザ認証して、データ取得可能か確認
        Auth::attempt([
            'username' => 'goumet-tarou',
            'password' => 'gourmettaroutest',
        ]);
        $result = $this->reservation::where('id', $this->testReservationId)->List()->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationId, $result[0]['id']);

        // ユーザ認証して、データ取得可能か確認
        Auth::attempt([
            'username' => 'goumet-hanako',
            'password' => 'gourmethanakotest',
        ]);
        $result = $this->reservation::where('id', $this->testReservationId)->List()->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationId, $result[0]['id']);
    }

    private function _createReservation()
    {
        $settlementCompany = new SettlementCompany();
        $settlementCompany->save();

        $commissionRate = new CommissionRate();
        $commissionRate->settlement_company_id = $settlementCompany->id;
        $commissionRate->app_cd = 'RS';
        $commissionRate->apply_term_from = '2022-01-01 00:00:00';
        $commissionRate->apply_term_to = '2999-12-31 23:59:59';
        $commissionRate->fee = '10.0';
        $commissionRate->accounting_condition = 'FIXED_RATE';
        $commissionRate->save();

        $commissionRate = new CommissionRate();
        $commissionRate->settlement_company_id = $settlementCompany->id;
        $commissionRate->app_cd = 'TO';
        $commissionRate->apply_term_from = '2022-01-01 00:00:00';
        $commissionRate->apply_term_to = '2999-12-31 23:59:59';
        $commissionRate->fee = '180.0';
        $commissionRate->accounting_condition = 'FLAT_RATE';
        $commissionRate->save();

        $store = new Store();
        $store->settlement_company_id = $settlementCompany->id;
        $store->save();

        // RSメニュー
        {
            $menu = new Menu();
            $menu->store_id = $store->id;
            $menu->app_cd = 'RS';
            $menu->save();
            $this->testMenuId = $menu->id;

            $option = new Option();
            $option->menu_id = $menu->id;
            $option->price = 100;
            $option->save();
            $this->testOptionId = $option->id;
        }
        // TOメニュー
        {
            $menu = new Menu();
            $menu->store_id = $store->id;
            $menu->app_cd = 'TO';
            $menu->save();
            $this->testMenuId2 = $menu->id;

            $option = new Option();
            $option->menu_id = $menu->id;
            $option->price = 100;
            $option->save();
            $this->testOptionId2 = $option->id;
        }

        // RSデータ
        {
            $reservation = new Reservation();
            $reservation->app_cd = 'RS';
            $reservation->last_name = 'グルメ';
            $reservation->first_name = '太郎';
            $reservation->email = 'gourmet-test@adventure-inc.co.jp';
            $reservation->is_close = 1;
            $reservation->reservation_status = 'ENSURE';
            $reservation->payment_status = 'PAYED';
            $reservation->payment_method = 'CREDIT';
            $reservation->tel = '0312345678';
            $reservation->created_at = '2022-10-01 10:00:00';
            $reservation->pick_up_datetime = '2022-10-01 15:00:00';
            $reservation->save();
            $this->testReservationId = $reservation->id;

            $reservationStore = new ReservationStore();
            $reservationStore->reservation_id = $this->testReservationId;
            $reservationStore->store_id = $store->id;
            $reservationStore->name = 'テスト店舗A';
            $reservationStore->tel = '0312341234';
            $reservationStore->save();
            $this->testReservationStoreId = $reservationStore->id;
            $this->testReservationId = $reservation->id;

            $reservationMenu = new ReservationMenu();
            $reservationMenu->reservation_id = $this->testReservationId;
            $reservationMenu->save();
            $this->testReservationMenuId = $reservationMenu->id;

            $review = new Review();
            $review->store_id = $store->id;
            $review->reservation_id = $this->testReservationId;
            $review->evaluation_cd = 'GOOD_DEAL';
            $review->body = 'テストbody';
            $review->user_id = 1;
            $review->user_name = 'グルメ 太郎';
            $review->published = 1;
            $review->created_at = '2022-10-01 10:00:00';
            $review->save();
            $this->testReviewId = $review->id;

            $paymentDetail = new PaymentDetail();
            $paymentDetail->reservation_id = $this->testReservationId;
            $paymentDetail->save();
            $this->testPaymentDetailId = $paymentDetail->id;

            $cancelDetail = new CancelDetail();
            $cancelDetail->reservation_id = $this->testReservationId;
            $cancelDetail->save();
            $this->testCancelDetailId = $cancelDetail->id;
        }

        //　TOデータ
        {
            $reservation = new Reservation();
            $reservation->app_cd = 'TO';
            $reservation->last_name = 'テイク';
            $reservation->first_name = '花子';
            $reservation->email = 'gourmet-test2@adventure-inc.co.jp';
            $reservation->reservation_status = 'RESERVE';
            $reservation->payment_status = 'AUTH';
            $reservation->payment_method = 'CREDIT';
            $reservation->tel = '0356785678';
            $reservation->created_at = '2022-10-02 12:00:00';
            $reservation->pick_up_datetime = '2022-10-02 15:00:00';
            $reservation->is_close = 0;
            $reservation->save();
            $this->testReservationId2 = $reservation->id;

            $reservationStore = new ReservationStore();
            $reservationStore->reservation_id = $this->testReservationId2;
            $reservationStore->store_id = $store->id;
            $reservationStore->name = 'テスト店舗B';
            $reservationStore->tel = '0312344321';
            $reservationStore->save();
        }

        $staff = new Staff();
        $staff->name = 'グルメ太郎';
        $staff->username = 'goumet-tarou';
        $staff->password = bcrypt('gourmettaroutest');
        $staff->staff_authority_id = '1';
        $staff->published = '1';
        $staff->store_id = '0';
        $staff->password_modified = '2022-10-01 10:00:00';
        $staff->save();

        $staff = new Staff();
        $staff->name = 'グルメ花子';
        $staff->username = 'goumet-hanako';
        $staff->password = bcrypt('gourmethanakotest');
        $staff->staff_authority_id = '3';
        $staff->published = '1';
        $staff->store_id = $store->id;
        $staff->password_modified = '2022-10-01 10:00:00';
        $staff->save();
    }
}
