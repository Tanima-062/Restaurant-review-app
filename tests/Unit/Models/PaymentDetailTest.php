<?php

namespace Tests\Unit\Models;

use App\Models\Menu;
use App\Models\Option;
use App\Models\PaymentDetail;
use App\Models\Reservation;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentDetailTest extends TestCase
{
    private $paymentDetail;
    private $testOptionId;  //TO用
    private $testOptionId2; //RS用
    private $testMenuId;    //TO用
    private $testMenuId2;   //RS用
    private $testPaymentDetailId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->paymentDetail = new PaymentDetail();

        $this->_createPaymentDetail();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testGetAccountCodeStrAttribute()
    {
        // 該当データあり
        $result = $this->paymentDetail::find($this->testPaymentDetailId)->getAccountCodeStrAttribute();
        $this->assertSame('メニュー', $result);

        // 該当データなし
        {
            $paymentDetail = $this->paymentDetail::find($this->testPaymentDetailId);
            $paymentDetail->account_code = '';
            $paymentDetail->save();

            $result = $this->paymentDetail::find($this->testPaymentDetailId)->getAccountCodeStrAttribute();
            $this->assertSame('', $result);

            $paymentDetail = $this->paymentDetail::find($this->testPaymentDetailId);
            $paymentDetail->account_code = 'MENU';
            $paymentDetail->save();
        }
    }

    public function testGetSumPriceAttribute()
    {
        $result = $this->paymentDetail::find($this->testPaymentDetailId)->getSumPriceAttribute();
        $this->assertSame(3000, $result);
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
        $menu = array_merge(Menu::where('id', $this->testMenuId)->first()->toArray(), ['count' => 2]);
        $menus = [[
            'menu' => $menu,
            'options' => [Option::find($this->testOptionId)->toArray()],
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
        $menuInfo[$this->testMenuId] = $menu;
        $menuInfo[$this->testMenuId]['menuPrice']['price'] = 1500;

        $reservation = new Reservation();
        $reservation->app_cd = 'TO';
        $reservation->save();

        // paymentDetail insert
        $this->paymentDetail->saveTakeout($info, $reservation, $menuInfo);
        $paymentDetail = $this->paymentDetail::where('reservation_id', $reservation->id)->get();
        $this->assertIsObject($paymentDetail);
        $this->assertSame(2, $paymentDetail->count());
        $this->assertSame($this->testMenuId, $paymentDetail[0]['target_id']);
        $this->assertSame('MENU', $paymentDetail[0]['account_code']);
        $this->assertSame(1500, $paymentDetail[0]['price']);
        $this->assertSame(2, $paymentDetail[0]['count']);
        $this->assertSame('自動', $paymentDetail[0]['remarks']);
        $this->assertSame($this->testOptionId, $paymentDetail[1]['target_id']);
        $this->assertSame('OKONOMI', $paymentDetail[1]['account_code']);
        $this->assertSame(100, $paymentDetail[1]['price']);
        $this->assertSame(2, $paymentDetail[1]['count']);
        $this->assertSame('自動', $paymentDetail[1]['remarks']);
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
        $menu = Menu::where('id', $this->testMenuId2)->first()->toArray();
        $menus = [[
            'menu' => $menu,
            'options' => [array_merge(Option::find($this->testOptionId2)->toArray(), ['count' => 2])],
        ]];
        $info = [
            'customer' => $userInfo,
            'application' => [
                'pickUpDate' => '2022-10-01',
                'pickUpTime' => '09:00:00',
                'persons' => '2',
                'menus' => [$menus[0]],
            ],
        ];
        $menuInfo = [];
        $menuInfo[$this->testMenuId2] = $menu;
        $menuInfo[$this->testMenuId2]['menuPrice']['price'] = 2000;

        $reservation = new Reservation();
        $reservation->app_cd = 'RS';
        $reservation->save();

        // paymentDetail insert
        $this->paymentDetail->saveRestaurant($info, $reservation, $menuInfo);
        $paymentDetail = $this->paymentDetail::where('reservation_id', $reservation->id)->get();
        $this->assertIsObject($paymentDetail);
        $this->assertSame(2, $paymentDetail->count());
        $this->assertSame($this->testMenuId2, $paymentDetail[0]['target_id']);
        $this->assertSame('MENU', $paymentDetail[0]['account_code']);
        $this->assertSame(2000, $paymentDetail[0]['price']);
        $this->assertSame(2, $paymentDetail[0]['count']);
        $this->assertSame('自動', $paymentDetail[0]['remarks']);
        $this->assertSame($this->testOptionId2, $paymentDetail[1]['target_id']);
        $this->assertSame('OKONOMI', $paymentDetail[1]['account_code']);
        $this->assertSame(150, $paymentDetail[1]['price']);
        $this->assertSame(2, $paymentDetail[1]['count']);
        $this->assertSame('自動', $paymentDetail[1]['remarks']);
    }

    private function _createPaymentDetail()
    {
        $store = new Store();
        $store->save();

        // TOメニュー
        {
            $menu = new Menu();
            $menu->store_id = $store->id;
            $menu->app_cd = 'TO';
            $menu->save();
            $this->testMenuId = $menu->id;

            $option = new Option();
            $option->menu_id = $menu->id;
            $option->option_cd = 'OKONOMI';
            $option->price = 100;
            $option->save();
            $this->testOptionId = $option->id;
        }

        // RSメニュー
        {
            $menu = new Menu();
            $menu->store_id = $store->id;
            $menu->app_cd = 'RS';
            $menu->save();
            $this->testMenuId2 = $menu->id;

            $option = new Option();
            $option->menu_id = $menu->id;
            $option->option_cd = 'OKONOMI';
            $option->price = 150;
            $option->save();
            $this->testOptionId2 = $option->id;
        }

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

        $paymentDetail = new PaymentDetail();
        $paymentDetail->reservation_id = $reservation->id;
        $paymentDetail->account_code = 'MENU';
        $paymentDetail->price = 1500;
        $paymentDetail->count = 2;
        $paymentDetail->save();
        $this->testPaymentDetailId = $paymentDetail->id;
    }
}
