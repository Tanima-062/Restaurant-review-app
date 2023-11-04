<?php

namespace Tests\Unit\Models;

use App\Models\Maintenance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    private $maintenance;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->maintenance = new Maintenance();

        $this->_createMaintenance();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testIsInMaintenance()
    {
        $typeStopSale = config('code.maintenances.type.stopSale');
        $typeStopEcon = config('code.maintenances.type.stopEcon');

        // メンテナンス中のものなし
        {
            // type指定なし
            $msg = null;
            $result = $this->maintenance->isInMaintenance(null, $msg);
            $this->assertFalse($result);
            $this->assertNull($msg);

            // type指定あり(stopSale)
            $msg = null;
            $result = $this->maintenance->isInMaintenance($typeStopSale, $msg);
            $this->assertFalse($result);
            $this->assertNull($msg);

            // type指定あり(stopEcon)
            $msg = null;
            $result = $this->maintenance->isInMaintenance($typeStopEcon, $msg);
            $this->assertFalse($result);
            $this->assertNull($msg);
        }

        // stopSaleがメンテナンス中
        {
            // 該当データをメンテナンス中に更新しておく
            $maintenances = Maintenance::where('type', $typeStopSale)->first();
            $maintenances->is_under_maintenance = 1;
            $maintenances->save();

            // type指定なし
            $msg = null;
            $result = $this->maintenance->isInMaintenance(null, $msg);
            $this->assertTrue($result);
            $this->assertSame(Lang::get('message.maintenance.default'), $msg);

            // type指定あり
            $msg = null;
            $result = $this->maintenance->isInMaintenance($typeStopSale, $msg);
            $this->assertTrue($result);
            $this->assertSame(Lang::get('message.maintenance.stopSale'), $msg);
        }

        // stopEconがメンテナンス中
        {
            // 上記で更新したデータを元に戻しておく
            $maintenances = Maintenance::where('type', $typeStopSale)->first();
            $maintenances->is_under_maintenance = 0;
            $maintenances->save();

            // 該当データをメンテナンス中に更新しておく
            $maintenances = Maintenance::where('type', $typeStopEcon)->first();
            $maintenances->is_under_maintenance = 1;
            $maintenances->save();

            // type指定なし
            $msg = null;
            $result = $this->maintenance->isInMaintenance(null, $msg);
            $this->assertTrue($result);
            $this->assertSame(Lang::get('message.maintenance.default'), $msg);

            // type指定あり
            $msg = null;
            $result = $this->maintenance->isInMaintenance($typeStopEcon, $msg);
            $this->assertTrue($result);
            $this->assertSame('', $msg);
        }
    }

    private function _createMaintenance()
    {
        // 一旦全レコードのメンテナンスモードを0にしておく。
        $maintenances = Maintenance::where('is_under_maintenance', 1)->get();
        if ($maintenances->count() > 0) {
            foreach($maintenances as $maintenance ) {
                $maintenance->is_under_maintenance = 0;
                $maintenance->save();
            }
        }

        // もしデータないのであれば、コメントアウトはずす
        // $maintenance = new Maintenance();
        // $maintenance->type = 'STOP_SALE';   //config('code.maintenances.type.stopSale')
        // $maintenance->is_under_maintenance = '0';
        // $maintenance->staff_id = '0';
        // $maintenance->save();

        // $maintenance= new Maintenance();
        // $maintenance->type = 'STOP_ECON';   //config('code.maintenances.type.stopEcon')
        // $maintenance->is_under_maintenance = '0';
        // $maintenance->staff_id = '0';
        // $maintenance->save();
    }
}
