<?php

namespace Tests\Unit\Models;

use App\Models\Store;
use App\Models\ReservationCancelPolicy;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReservationCancelPolicyTest extends TestCase
{
    private $reservationCancelPolicy;
    private $testStoreIdForTakeout;
    private $testStoreIdForRestraunt;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->reservationCancelPolicy = new ReservationCancelPolicy();

        $this->_createReservationCancelPolicy();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testSaveTakeout()
    {
        $result = $this->reservationCancelPolicy->saveTakeout($this->testStoreIdForTakeout);
        $this->assertIsObject($result);
    }

    public function testSaveRestaurant()
    {
        $result = $this->reservationCancelPolicy->saveRestaurant($this->testStoreIdForRestraunt);
        $this->assertIsObject($result);
    }

    private function _createReservationCancelPolicy()
    {
        $store = new Store();
        $store->save();
        $this->testStoreIdForTakeout = $store->id;

        $store = new Store();
        $store->save();
        $this->testStoreIdForRestraunt = $store->id;
    }
}
