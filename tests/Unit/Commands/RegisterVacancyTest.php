<?php

namespace Tests\Unit\Commands;

use App\Models\OpeningHour;
use App\Models\Store;
use App\Models\Reservation;
use App\Models\ReservationStore;
use App\Models\Vacancy;
use App\Models\VacancyQueue;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegisterVacancyTest extends TestCase
{
    private $testStoreId;

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

    public function testRegisterVacancy()
    {
        // テストするため、登録されているデータを削除（トランザクション内なので問題なし）
        $this->_setVacancyQueue();

        // バッチ実行事前確認
        $this->assertSame(1, VacancyQueue::where('store_id', $this->testStoreId)->get()->count());
        $this->assertSame(0, Vacancy::where('store_id', $this->testStoreId)->whereBetWeen('date', ['2022-11-01', '2022-11-04'])->get()->count());

        // バッチ実行
        $this->artisan('register:vacancy')
        ->expectsOutput(0);

        // 実行結果確認
        // Queueレコードが削除されている
        $this->assertSame(0, VacancyQueue::where('store_id', $this->testStoreId)->get()->count());
        // 空席データを参照
        $vacancy = Vacancy::where('store_id', $this->testStoreId)
            ->whereBetWeen('date', ['2022-11-01', '2022-11-05'])
            ->orderBy('date')
            ->orderBy('time')
            ->orderBy('headcount')
            ->get();
        // 11/1は営業日だが、火曜日はQueue登録指定なしなので空席データなし
        $this->assertSame(0, $vacancy->where('date', '2022-11-01')->wherebetWeen('headcount', [1, 10])->count());
        // 11/2は営業日なので11:00,11:30のheadcount1-10の20レコードの空席データあり
        $result = $vacancy->where('date', '2022-11-02')->wherebetWeen('headcount', [1, 10]);
        $result2 = $result->where('time', '11:00:00');
        $result3 = $result->where('time', '11:30:00');
        $result4 = $result->where('time', '12:00:00');
        $this->assertSame(20, $result->count());
        $this->assertSame(10, $result2->count()); // 11時のレコード
        $this->assertSame(10, $result3->count()); // 11時半のレコード
        $this->assertSame(0, $result4->count());  // 12時のレコード
        $this->assertSame(1, $result2[0]['headcount']);   // headcount:1
        $this->assertSame(5, $result2[0]['base_stock']);
        $this->assertSame(5, $result2[0]['stock']);
        $this->assertSame(2, $result2[1]['headcount']);   // headcount:2
        $this->assertSame(5, $result2[1]['base_stock']);
        $this->assertSame(2, $result2[1]['stock']);
        $this->assertSame(3, $result2[2]['headcount']);   // headcount:3
        $this->assertSame(5, $result2[2]['base_stock']);
        $this->assertSame(1, $result2[2]['stock']);
        $this->assertSame(4, $result2[3]['headcount']);   // headcount:4
        $this->assertSame(5, $result2[3]['base_stock']);
        $this->assertSame(1, $result2[3]['stock']);
        $this->assertSame(5, $result2[4]['headcount']);   // headcount:5
        $this->assertSame(5, $result2[4]['base_stock']);
        $this->assertSame(1, $result2[4]['stock']);
        $this->assertSame(6, $result2[5]['headcount']);   // headcount:6
        $this->assertSame(5, $result2[5]['base_stock']);
        $this->assertSame(0, $result2[5]['stock']);
        $this->assertSame(7, $result2[6]['headcount']);   // headcount:7
        $this->assertSame(5, $result2[6]['base_stock']);
        $this->assertSame(0, $result2[6]['stock']);
        $this->assertSame(8, $result2[7]['headcount']);   // headcount:8
        $this->assertSame(5, $result2[7]['base_stock']);
        $this->assertSame(0, $result2[7]['stock']);
        $this->assertSame(9, $result2[8]['headcount']);   // headcount:9
        $this->assertSame(5, $result2[8]['base_stock']);
        $this->assertSame(0, $result2[8]['stock']);
        $this->assertSame(10, $result2[9]['headcount']);   // headcount:10
        $this->assertSame(5, $result2[9]['base_stock']);
        $this->assertSame(0, $result2[9]['stock']);
        // 11/3祝日は定休日なので空席データなし
        $this->assertSame(0, $vacancy->where('date', '2022-11-03')->count());
        // 11/4は営業日だが、予約が入っているため空席データ入れない
        $this->assertSame(0, $vacancy->where('date', '2022-11-04')->count());
    }

    private function _setVacancyQueue()
    {
        $store = new Store();
        $store->app_cd = 'RS';
        $store->number_of_seats = 10;
        $store->regular_holiday = '111111000';  // 日祝以外営業
        $store->save();
        $this->testStoreId = $store->id;

        $openingHour = new OpeningHour();
        $openingHour->store_id = $this->testStoreId;
        $openingHour->week = '11111100';        // 日祝以外提供可
        $openingHour->start_at = '09:00:00';
        $openingHour->end_at = '11:30:00';
        $openingHour->save();

        // テストデータ以外は一時的に削除
        VacancyQueue::where('store_id', 0)->whereNull('deleted_at')->delete();

        $vacancyQueue = new VacancyQueue();
        $vacancyQueue->store_id = $this->testStoreId;
        $vacancyQueue->request = json_encode([
            '_token' => 'testtoken',
            'intervalTime' => '30',
            'week' => ["0","0","1","1","1","1","0","0"],    // 登録する曜日は水木金土
            'start' => '2022-11-01',
            'end' => '2022-11-04',
            'regexp' => '[0|1][0|1][1][1][1][1][0|1][0|1]',
            'interval' => [
                '11:00:00' => [
                    'base_stock' => 5,
                    'is_stop_sale' => 0,
                ],
                '11:30:00' => [
                    'base_stock' => 5,
                    'is_stop_sale' => 0,
                ],
                '12:00:00' => [
                    'base_stock' => -5,     // マイナスの空席を設定（skip確認
                    'is_stop_sale' => 0,
                ],
            ]
        ]);
        $vacancyQueue->save();

        // 予約情報を作成
        $reservation = new Reservation();
        $reservation->app_cd = 'RS';
        $reservation->reservation_status = 'RESERVE';
        $reservation->pick_up_datetime = '2022-11-04';
        $reservation->save();

        $reservationStore = new ReservationStore();
        $reservationStore->reservation_id = $reservation->id;
        $reservationStore->store_id = $store->id;
        $reservationStore->save();
    }
}
