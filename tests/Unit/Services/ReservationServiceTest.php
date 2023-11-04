<?php

namespace Tests\Unit\Services;

use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReservationServiceTest extends TestCase
{
    private $reservationService;

    public function setUp(): void
    {
        parent::setUp();
        $this->reservationService = $this->app->make('App\Services\ReservationService');
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testGetStoreEmails()
    {
        $store = $this->_createStore();

        $result = $this->reservationService->getStoreEmails($store->id);
        $this->assertCount(3, $result);
        $this->assertSame('gourmet-test1@adventure-inc.co.jp', $result[0]);
        $this->assertSame('gourmet-test2@adventure-inc.co.jp', $result[1]);
        $this->assertSame('gourmet-test3@adventure-inc.co.jp', $result[2]);
    }

    private function _createStore()
    {
        $store = new Store();
        $store->email_1 = 'gourmet-test1@adventure-inc.co.jp';
        $store->email_2 = 'gourmet-test2@adventure-inc.co.jp';
        $store->email_3 = 'gourmet-test3@adventure-inc.co.jp';
        $store->save();
        return $store;
    }

}
