<?php

namespace Tests\Unit\Libs;

use App\Libs\BirthDate;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BirthDateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testConvertAge()
    {
        $date = Carbon::now()->subYears(15);    // 15年前の1月1日
        $date->month = 1;
        $date->day = 1;
        $this->assertSame(15.0, BirthDate::convertAge($date));
    }
}
