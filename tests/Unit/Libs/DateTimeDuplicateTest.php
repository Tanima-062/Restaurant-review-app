<?php

namespace Tests\Unit\Libs;

use App\Libs\DateTimeDuplicate;
use Tests\TestCase;

class DateTimeDuplicateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testIsDuplicatePeriod()
    {
        // 範囲１と範囲2が一部重なっている
        // 範囲1：2023−01−01〜2023-12-31
        // 範囲2：2022-01-01〜2023-01-31
        $this->assertTrue(DateTimeDuplicate::isDuplicatePeriod('2023-01-01', '2023-12-31', '2022-01-01', '2023-01-31'));
        // 範囲１と範囲2が全く一緒
        // 範囲1：2023-01-01〜2023-12-31
        // 範囲2：2023-01-01〜2023-12-31
        $this->assertTrue(DateTimeDuplicate::isDuplicatePeriod('2023-01-01', '2023-12-31', '2023-01-01', '2023-12-31'));
        // 範囲１と範囲2は全く重ならない（範囲1<範囲2)
        // 範囲1：2023-01-01〜2023-12-31
        // 範囲2:2024−01−01〜2024-12-31
        $this->assertFalse(DateTimeDuplicate::isDuplicatePeriod('2023-01-01', '2023-12-31', '2024-01-01', '2024-12-31'));
        // 範囲１と範囲2は全く重ならない（範囲1>範囲2)
        // 範囲1:2024−01−01〜2024-12-31
        // 範囲2：2023-01-01〜2023-12-31
        $this->assertFalse(DateTimeDuplicate::isDuplicatePeriod('2024-01-01', '2024-12-31', '2023-01-01', '2023-12-31'));
    }

    public function testIsTimeDuplicate()
    {
        // 範囲１と範囲2が一部重なっている
        // 範囲1：09:00:00〜14:00:00
        // 範囲2：12:00:00〜17:00:00
        $this->assertTrue(DateTimeDuplicate::isTimeDuplicate('09:00:00', '14:00:00', '12:00:00', '17:00:00'));
        // 範囲１と範囲2が一部重なっている(範囲1開始=範囲2終了、範囲2開始=範囲1終了)
        // 範囲1：09:00:00〜12:00:00
        // 範囲2：12:00:00〜09:00:00
        $this->assertFalse(DateTimeDuplicate::isTimeDuplicate('09:00:00', '12:00:00', '12:00:00', '09:00:00'));
        // 範囲１と範囲2が一部重なっている(範囲1開始=範囲2終了、範囲2開始<範囲1終了)
        // 範囲1：09:00:00〜13:00:00
        // 範囲2：12:00:00〜09:00:00
        $this->assertFalse(DateTimeDuplicate::isTimeDuplicate('09:00:00', '13:00:00', '12:00:00', '09:00:00'));
        // 範囲１と範囲2が一部重なっている(範囲1開始>範囲2終了、範囲2開始=範囲1終了)
        // 範囲1：10:00:00〜12:00:00
        // 範囲2：12:00:00〜09:00:00
        $this->assertFalse(DateTimeDuplicate::isTimeDuplicate('10:00:00', '12:00:00', '12:00:00', '09:00:00'));
        // 範囲１と範囲2が全く一緒
        // 範囲1：09:00:00〜14:00:00
        // 範囲2：09:00:00〜14:00:00
        $this->assertTrue(DateTimeDuplicate::isTimeDuplicate('09:00:00', '14:00:00', '09:00:00', '14:00:00'));
        // 範囲１と範囲2が全く重ならない（範囲1<範囲2)
        // 範囲1：09:00:00〜12:59:59
        // 範囲2：13:00:00〜21:00:00
        $this->assertFalse(DateTimeDuplicate::isTimeDuplicate('09:00:00', '12:59:59', '13:00:00', '21:00:00'));
        // 範囲１と範囲2が全く重ならない（範囲1>範囲2)
        // 範囲1：13:00:00〜21:00:00
        // 範囲2：09:00:00〜12:59:59
        $this->assertFalse(DateTimeDuplicate::isTimeDuplicate('13:00:00', '21:00:00', '09:00:00', '12:59:59'));
    }
}
