<?php

namespace Tests\Unit\Commands;

use App\Models\OpeningHour;
use App\Models\Store;
use App\Models\Reservation;
use App\Models\ReservationStore;
use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegisterAllVacancyTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testRegisterAllVacancy()
    {
        // テスト対象店舗を一旦クリアする
        $this->_clearStore();

        // テスト１（対象店舗なしで実行エラー）
        {
            // バッチ実行
            $this->artisan('register_all:vacancy 3')
                ->expectsOutput(0);
        }

        // テスト2（パラメータエラーエラー）
        {
            // バッチ実行
            $this->artisan('register_all:vacancy 3 2999/01/01 2999/05/01')
                ->expectsOutput(0);

            $resultCount = Vacancy::where('date', '2999-01-01')->get()->count();
            $this->assertSame(0, $resultCount);
        }

        // テストデータ作成
        $store = $this->_createStore();
        $storeId = $store->id;
        $this->_createReservation($store, '2022-11-02 11:00:00');   // 2022-11-02 11:00:00に予約ある想定
        $this->_createVancancy($store, '2022-11-02', '11:00:00', 1);

        // テスト3(空席数を期間中３つに設定)
        {
            // テスト結果比較情報取得
            $beforeCount = Vacancy::where('store_id', $storeId)->get()->count();
            $this->assertSame(1, $beforeCount);

            // バッチ実行
            $this->artisan('register_all:vacancy 3 2022/11/01 2022/11/05 ' . $storeId . ' ' . $storeId)
                ->expectsOutput(0);

            // 結果比較
            // 店舗の空席データが増えていること
            $result = Vacancy::where('store_id', $storeId)->get();
            $this->assertTrue(($result->count() > $beforeCount));
            // 予約が入っている日の空席データは更新されていないこと
            $result2 = $result->where('date', '2022-11-02')->where('time', '11:00:00')->where('headcount', 1);
            $this->assertTrue(($result2->count() > 0));
            $this->assertSame(1, $result2->first()->stock);
            // 跨ぎ営業している店舗の場合、0:00〜10:00の在庫は登録されないこと
            $result3 = $result->where('date', '2022-11-01')->whereBetween('time', ['00:00:00', '10:00:00']);
            $this->assertFalse(($result3->count() > 0));
            // データが登録されていること
            $result4 = $result->where('date', '2022-11-01')->whereBetween('time', ['11:30:00', '12:00:00']);
            $this->assertTrue(($result4->count() > 0));
            $this->assertSame(3, $result4->first()->stock);
            // データが登録されていないこと（定休日）
            $result5 = $result->where('date', '2022-11-05');
            $this->assertFalse(($result5->count() > 0));
        }

        // テスト４(空席数０を指定)
        {
            // テスト前確認取得
            $result = Vacancy::where('store_id', $storeId)->where('date', '2022-11-04')->get();
            $this->assertTrue(($result->count() > 0));
            $this->assertTrue(($result->first()->stock) > 0);

            // バッチ実行
            $this->artisan('register_all:vacancy 0 2022/11/04 2022/11/04 ' . $storeId . ' ' . $storeId)
                ->expectsOutput(0);

            // 空席座席がなくなっていること
            $result = Vacancy::where('store_id', $storeId)->where('date', '2022-11-04')->get();
            $this->assertCount(0, $result);
        }
    }

    private function _clearStore()
    {
        // 全て対象外にする（トランザクション内のことなので問題なし）
        Store::where('batch_register_all_vacancy_flg', 1)
            ->update(['batch_register_all_vacancy_flg' => 0]);
    }

    private function _createStore()
    {
        $store = new Store();
        $store->app_cd = 'RS';
        $store->batch_register_all_vacancy_flg = 1;
        $store->number_of_seats = 10;
        $store->regular_holiday = '111111100';  // 土祝以外営業
        $store->save();

        $openingHour = new OpeningHour();
        $openingHour->store_id = $store->id;
        $openingHour->week = '11111010';        // 土祝以外提供可
        $openingHour->start_at = '09:00:00';
        $openingHour->end_at = '12:00:00';
        $openingHour->save();

        $openingHour = new OpeningHour();       // 日跨ぎ営業データ
        $openingHour->store_id = $store->id;
        $openingHour->week = '11111010';        // 土祝以外提供可
        $openingHour->start_at = '23:00:00';
        $openingHour->end_at = '23:59:00';
        $openingHour->save();

        $openingHour = new OpeningHour();       // 日跨ぎ営業データ
        $openingHour->store_id = $store->id;
        $openingHour->week = '11111010';        // 土祝以外提供可
        $openingHour->start_at = '00:00:00';
        $openingHour->end_at = '00:59:00';
        $openingHour->save();

        return $store;
    }

    private function _createReservation($store, $pick_up_datetime)
    {
        $reservation = new Reservation();
        $reservation->app_cd = 'RS';
        $reservation->reservation_status = 'RESERVE';
        $reservation->pick_up_datetime = $pick_up_datetime;
        $reservation->save();

        $reservationStore = new ReservationStore();
        $reservationStore->reservation_id = $reservation->id;
        $reservationStore->store_id = $store->id;
        $reservationStore->save();
    }

    private function _createVancancy($store, $data, $time, $stock)
    {
        $vacancy = new Vacancy();
        $vacancy->store_id = $store->id;
        $vacancy->date = $data;
        $vacancy->time = $time;
        $vacancy->stock = $stock;
        $vacancy->headcount = 1;
        $vacancy->save();
    }
}
