<?php

namespace Tests\Unit\Models;

use App\Models\Station;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class stationTest extends TestCase
{
    private $station;
    private $testStationId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->station = new Station();

        $this->_createStation();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testScopeAdminSearchFilter()
    {
        // ID検索
        $valid = [
            'id' => $this->testStationId,
        ];
        $result = $this->station::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStationId, $result[0]['id']);

        // name検索
        $valid = [
            'name' => 'テスト',
        ];
        $result = $this->station::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStationId, $result[0]['id']);
    }

    private function _createStation()
    {
        $station = new Station();
        $station->name = 'テスト';
        $station->save();
        $this->testStationId = $station->id;
    }
}
