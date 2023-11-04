<?php

namespace Tests\Unit\Commands;

use App\Models\Holiday;
use App\Models\Reservation;
use App\Models\Stock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegisterHolidayTest extends TestCase
{
    private $startDate;
    private $endDate;

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

    public function testRegisterHoliday()
    {
        // 実行前準備
        $this->_clearHoliday();
        $testDate = new Carbon();
        $testDate->day = 1;         // 実行日が月末だと$testDate->month = 2にした際、2023-02-3xとなり、2023−03-xxに変わるため、テスト結果が実行日で変わってしまうため、初期値は1日にしておく。

        // 実行前確認
        $this->assertFalse(Holiday::whereBetWeen('date', [$this->startDate, $this->endDate])->exists());

        // バッチ実行
        $this->artisan('register:holiday')
        ->expectsOutput('[RegisterHoliday] ##### START #####')
        ->expectsOutput(0);

        // 実行確認
        $this->assertTrue(Holiday::whereBetWeen('date', [$this->startDate, $this->endDate])->exists());

        // いくつか確認する
        // 建国記念の日
        $testDate->month = 2;
        $testDate->day = 11;
        $result =  Holiday::whereBetWeen('date', [$this->startDate, $this->endDate])->where('name', 'like', '建国記念の日%')->first();
        $this->assertSame($testDate->toDateString(), $result->date);
        // 天皇誕生日
        $testDate->month = 2;
        $testDate->day = 23;
        $result2 =  Holiday::whereBetWeen('date', [$this->startDate, $this->endDate])->where('name', 'like', '天皇誕生日%')->first();
        $this->assertSame($testDate->toDateString(), $result2->date);
        // 昭和の日
        $testDate->month = 4;
        $testDate->day = 29;
        $result3 =  Holiday::whereBetWeen('date', [$this->startDate, $this->endDate])->where('name', 'like', '昭和の日%')->first();
        $this->assertSame($testDate->toDateString(), $result3->date);
        // 憲法記念日
        $testDate->month = 5;
        $testDate->day = 3;
        $result3 =  Holiday::whereBetWeen('date', [$this->startDate, $this->endDate])->where('name', 'like', '憲法記念日%')->first();
        $this->assertSame($testDate->toDateString(), $result3->date);
        // 勤労感謝の日
        $testDate->month = 11;
        $testDate->day = 23;
        $result4 =  Holiday::whereBetWeen('date', [$this->startDate, $this->endDate])->where('name', 'like', '勤労感謝の日%')->first();
        $this->assertSame($testDate->toDateString(), $result4->date);
    }

    private function _clearHoliday()
    {
        $startDate = new Carbon();
        $startDate->month = 1;
        $startDate->day = 1;
        $this->startDate = $startDate;

        $endDate = $startDate->copy();
        $endDate->month = 12;
        $endDate->day = 31;
        $this->endDate = $endDate;

        Holiday::whereBetWeen('date', [$startDate, $endDate])->delete();
    }
}
