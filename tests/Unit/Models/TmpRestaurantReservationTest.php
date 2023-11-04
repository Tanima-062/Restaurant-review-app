<?php

namespace Tests\Unit\Models;

use App\Models\TmpRestaurantReservation;
use Exception;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TmpRestaurantReservationTest extends TestCase
{
    private $testTmpRestaurantReservationId;
    private $tmpRestaurantReservation;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->tmpRestaurantReservation = new TmpRestaurantReservation();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testSaveSession()
    {
        $info = [
            'customer' => [
                'firstName' => '太朗',
                'lastName' => 'グルメ',
                'email' => 'gourmet-test@adventure-inc.co.jp',
                'tel' => '0698765432',
                'request' => '卵アレルギーです',
            ]
        ];

        // 正常
        $errMsg = null;
        $this->assertTrue($this->tmpRestaurantReservation->saveSession('testsession', $info, $errMsg, 1));
        $this->assertNull($errMsg);

        // 例外エラー
        $errMsg = null;
        $this->assertFalse($this->tmpRestaurantReservation->saveSession('testsession', [], $errMsg, 1));
        $this->assertSame('save failed', $errMsg);
    }

    public function testSaveRes()
    {
        // 正常
        $this->_createTmpRestaurantReservation();
        $this->assertTrue($this->tmpRestaurantReservation->saveRes('testsession', [], 'COMPLETE'));
    }

    public function testGetInfo()
    {
        // 該当データなし
        try {
            $result = $this->tmpRestaurantReservation->getInfo('testsession');
        } catch (Exception $e) {
            $this->assertSame('No query results for model [App\Models\TmpRestaurantReservation].', $e->getMessage());
        }

        // 正常
        $this->_createTmpRestaurantReservation();
        $result = $this->tmpRestaurantReservation->getInfo('testsession');
        $this->assertSame(['test'], $result);

        // 例外エラー
        try {
            // テストデータ更新（info列をnull)
            $tmpRestaurantReservation = $this->tmpRestaurantReservation::find($this->testTmpRestaurantReservationId);
            $tmpRestaurantReservation->info = null;
            $tmpRestaurantReservation->save();

            $result = $this->tmpRestaurantReservation->getInfo('testsession');
        } catch (Exception $e) {
            $this->assertSame('Undefined variable: info', $e->getMessage());
        }
    }

    private function _createTmpRestaurantReservation()
    {
        $tmpRestaurantReservation = new TmpRestaurantReservation();
        $tmpRestaurantReservation->session_id = 'testsession';
        $tmpRestaurantReservation->info = json_encode(['test']);
        $tmpRestaurantReservation->save();
        $this->testTmpRestaurantReservationId = $tmpRestaurantReservation->id;
    }
}
