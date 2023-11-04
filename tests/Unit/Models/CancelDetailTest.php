<?php

namespace Tests\Unit\Models;

use App\Models\CancelDetail;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CancelDetailTest extends TestCase
{
    private $cancelDetail;
    private $testCancelDetailId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->cancelDetail = new CancelDetail();

        $this->_createCancelDetail();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testGetAccountCodeStrAttribute()
    {
        // 該当データあり
        $result = $this->cancelDetail::find($this->testCancelDetailId)->getAccountCodeStrAttribute();
        $this->assertSame('メニュー', $result);

        // 該当データなし
        {
            $cancelDetail = $this->cancelDetail::find($this->testCancelDetailId);
            $cancelDetail->account_code = '';
            $cancelDetail->save();

            $result = $this->cancelDetail::find($this->testCancelDetailId)->getAccountCodeStrAttribute();
            $this->assertSame('', $result);
        }
    }

    public function testGetSumPriceAttribute()
    {
        $result = $this->cancelDetail::find($this->testCancelDetailId)->getSumPriceAttribute();
        $this->assertSame(2000, $result);
    }

    private function _createCancelDetail()
    {
        $cancelDetail = new CancelDetail();
        $cancelDetail->account_code = 'MENU';
        $cancelDetail->price = 1000;
        $cancelDetail->count = 2;
        $cancelDetail->save();
        $this->testCancelDetailId = $cancelDetail->id;
    }
}
