<?php

namespace Tests\Unit\Libs;

use App\Libs\ConsumptionTax;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ConsumptionTaxTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testCalc()
    {
        $this->assertSame(0, ConsumptionTax::calc('198902'));   // 1989/03以前は0％
        $this->assertSame(0, ConsumptionTax::calc('198903'));   // 1989/03以前は0％
        $this->assertSame(3, ConsumptionTax::calc('198904'));   // 1989/04-1997/03の間は3％
        $this->assertSame(3, ConsumptionTax::calc('199701'));   // 1989/04-1997/03の間は3％
        $this->assertSame(3, ConsumptionTax::calc('199703'));   // 1989/04-1997/03の間は3％
        $this->assertSame(5, ConsumptionTax::calc('199704'));   // 1997/04-2014/03の間は5％
        $this->assertSame(5, ConsumptionTax::calc('201401'));   // 1997/04-2014/03の間は5％
        $this->assertSame(5, ConsumptionTax::calc('201403'));   // 1997/04-2014/03の間は5％
        $this->assertSame(8, ConsumptionTax::calc('201404'));   // 2014/04-2019/09の間は8％
        $this->assertSame(8, ConsumptionTax::calc('201901'));   // 2014/04-2019/09の間は8％
        $this->assertSame(8, ConsumptionTax::calc('201909'));   // 2014/04-2019/09の間は8％
        $this->assertSame(10, ConsumptionTax::calc('201910'));  // 2019/10以上は10％
        $this->assertSame(10, ConsumptionTax::calc('202301'));  // 2019/10以上は10％

        // ｔａｘ設定が未設定の場合も確認しておく
        $orgTax =  Config::get('const.payment.tax');            // 現状の設定を取得
        Config::set('const.payment.tax', []);                   // 設定をから配列に変更
        $this->assertSame(0, ConsumptionTax::calc('202301'));   // 該当する設定がない場合は0％
        Config::set('const.payment.tax', $orgTax);              // 念の為、設定を元に戻しておく
    }
}
