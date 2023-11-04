<?php

namespace Tests\Unit\Services;

use App\Models\Option;
use App\Models\Menu;
use App\Models\Price;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationOption;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    private $paymentService;

    public function setUp(): void
    {
        parent::setUp();
        $this->paymentService = $this->app->make('App\Services\PaymentService');
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testSumPrice()
    {
        $params = [];
        $this->assertSame(0, $this->paymentService->sumPrice($params));

        list($menu, $option) = $this->_createMenuPrice();
        $params = [
            'application' => [
                'menus' => [[
                    'menu' => [
                        'id' => $menu->id,
                        'count' => 2,
                    ],
                    'options' => [[
                        'id' => $option->id,
                    ]],
                ]],
                'pickUpDate' => '2022-10-01',
            ],
        ];
        $this->assertSame(2200, $this->paymentService->sumPrice($params));
    }

    public function testSumPriceRestarant()
    {
        $params = [];
        $this->assertSame(0, $this->paymentService->sumPriceRestarant($params));

        list($menu, $option) = $this->_createMenuPrice();
        $params = [
            'application' => [
                'menus' => [[
                    'menu' => [
                        'id' => $menu->id,
                    ],
                    'options' => [[
                        'id' => $option->id,
                        'count' => 2,
                    ]],
                ]],
                'visitDate' => '2022-10-01',
                'persons' => 2,
            ],
        ];
        $this->assertSame(2200, $this->paymentService->sumPriceRestarant($params));
    }

    public function testCalcPrice()
    {
        $reservation = $this->_createReservation();
        $dt = new Carbon();
        $result = $this->paymentService->calcPrice($reservation, $dt, 2);
        $this->assertCount(3, $result);
        $this->assertSame(1000, $result[0]);    // メニューの単価
        $this->assertSame(2000, $result[1]);    // オプションなしのメニュー合計
        $this->assertSame(2200, $result[2]);    // オプション込みのメニュー合計
    }

    private function _createMenuPrice()
    {
        $menu = new Menu();
        $menu->save();

        $price = new Price();
        $price->menu_id = $menu->id;
        $price->start_date = '2022-01-01';
        $price->end_date = '2999-12-31';
        $price->price = 1000;
        $price->save();

        $option = new Option();
        $option->menu_id = $menu->id;
        $option->price = 100;
        $option->save();

        return [$menu, $option];
    }

    private function _createReservation()
    {
        $reservation = new Reservation();
        $reservation->save();

        $reservationMenu = new ReservationMenu();
        $reservationMenu->reservation_id = $reservation->id;
        $reservationMenu->unit_price = 1000;
        $reservationMenu->count = 2;
        $reservationMenu->price = 2000;
        $reservationMenu->save();

        $reservationOption = new ReservationOption();
        $reservationOption->reservation_menu_id = $reservationMenu->id;
        $reservationOption->unit_price = 100;
        $reservationOption->count = 2;
        $reservationOption->price = 200;
        $reservationOption->save();

        return $reservation;
    }
}
