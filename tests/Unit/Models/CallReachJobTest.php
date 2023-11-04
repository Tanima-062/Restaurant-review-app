<?php

namespace Tests\Unit\Models;

use App\Models\CallReachJob;
use App\Models\Reservation;
use App\Models\ReservationStore;
use App\Models\Store;
use App\Models\TelSupport;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CallReachJobTest extends TestCase
{
    private $testCallReachJobId;
    private $testReservationId;
    private $testStoreId;
    private $testData = [
        "phone" => '0612345678',
        "args" => [
            "update_nums" => 0,
        ],
    ];

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->callReachJob = new CallReachJob();

        $this->_createReservation();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testCreateJob()
    {

        // job追加しない
        {
            $reservation = Reservation::find($this->testReservationId);
            $this->callReachJob->createJob('RESERVE_RS', $reservation);

            $callReachJob = $this->callReachJob::where('reservation_id', $this->testReservationId)->get();
            $this->assertIsObject($callReachJob);
            $this->assertSame(0, $callReachJob->count());
        }

        // job追加
        {
            $telSupport = TelSupport::where('store_id', $this->testStoreId)->first();
            $telSupport->is_tel_support = 1;
            $telSupport->save();

            $reservation = Reservation::find($this->testReservationId);

            // 成功
            {
                $this->callReachJob->createJob('CANCEL', $reservation);

                $callReachJob = $this->callReachJob::where('reservation_id', $this->testReservationId)->where('job_cd', 'CANCEL')->get();
                $this->assertIsObject($callReachJob);
                $this->assertSame(1, $callReachJob->count());
                $this->assertSame($this->testReservationId, $callReachJob[0]['reservation_id']);
            }

            // 成功
            {
                $reservation->pick_up_datetime = '2022-10-01 09:00';

                $this->callReachJob->createJob('RESERVE_RS', $reservation);

                $callReachJob = $this->callReachJob::where('reservation_id', $this->testReservationId)->where('job_cd', 'RESERVE_RS')->get();
                $this->assertIsObject($callReachJob);
                $this->assertSame(1, $callReachJob->count());
                $this->assertSame($this->testReservationId, $callReachJob[0]['reservation_id']);
            }

            // 例外エラー
            {
                $reservation = Reservation::find($this->testReservationId);

                try {
                    $this->callReachJob->createJob('RESERVE_RS', $reservation);

                    $callReachJob = $this->callReachJob::where('reservation_id', $this->testReservationId)->where('job_cd', 'RESERVE_RS')->get();
                } catch (Exception $e) {
                    $this->assertSame('Trailing data', $e->getMessage());
                }
            }
        }
    }

    public function testReceiveReult()
    {
        $this->_createCallReachJob();

        $request = new Request();
        $request->merge(['phone' => '0312345678', 'update_nums' => 1]);
        $request['trn_id'] = 'testturnid1234567890';

        // dial_statが'ANSWER'の場合
        {
            $request['dial_stat'] = 'ANSWER';
            $this->callReachJob->receiveResult($request);

            $callReachJob = $this->callReachJob::where('turn_id', 'testturnid1234567890')->get();
            $this->assertIsObject($callReachJob);
            $this->assertSame(1, $callReachJob->count());
            $this->assertSame($this->testReservationId, $callReachJob[0]['reservation_id']);
            $this->assertSame('FINISH', $callReachJob[0]['job_status']);
        }

        // dial_statが'ANSWER'以外の場合
        {
            $request['dial_stat'] = 'FINISH';
            $this->callReachJob->receiveResult($request);

            $callReachJob = $this->callReachJob::where('turn_id', 'testturnid1234567890')->get();
            $this->assertIsObject($callReachJob);
            $this->assertSame(1, $callReachJob->count());
            $this->assertSame($this->testReservationId, $callReachJob[0]['reservation_id']);
            $this->assertSame('NOT_ANSWER', $callReachJob[0]['job_status']);
        }

        // 例外エラー
        {
            try {
                $this->callReachJob->receiveResult([]);
            } catch (Exception $e) {
                $this->assertSame('Undefined index: trn_id', $e->getMessage());
            }
        }
    }

    public function testGetClosedJobsCount()
    {
        $this->_createCallReachJob('CLOSED');
        $result = $this->callReachJob->getClosedJobsCount()
            ->where('store_id', $this->testStoreId);
        $this->assertIsObject($result);
        $this->assertSame(1, $result[0]['count']);
    }

    public function testGetJobs()
    {
        $this->_createCallReachJob('SET');
        $result = $this->callReachJob->getJobs()
            ->where('store_id', $this->testStoreId)
            ->where('id', $this->testCallReachJobId);
        $this->assertIsObject($result);
        $this->assertSame($this->testCallReachJobId, $result[0]['id']);

        $this->_createCallReachJob('RETRY');
        $result = $this->callReachJob->getJobs()
            ->where('store_id', $this->testStoreId)
            ->where('id', $this->testCallReachJobId);
        $this->assertIsObject($result);
        $this->assertSame($this->testCallReachJobId, $result[1]['id']);
    }

    public function testUpdateServerErrorResponseByStoreId()
    {
        $this->_createCallReachJob('CLOSED');

        // 正常
        {
            try {
                // わざと500エラーを起こすようにモック作成
                // 参考：https://sota1235.hatenablog.com/entry/2015/10/24/165708
                $mockRes = new Response(500, [], '{"trn_id":"testturnid12345678901"}');
                $mock    = new MockHandler([$mockRes]);
                $handler = HandlerStack::create($mock);
                $mockClient = new Client(['handler' => $handler]);
                $res = $mockClient->request('GET', 'http://example.com');
            } catch (Exception $e) {
                if ($e->getCode() === 500) {
                    try {
                        $this->callReachJob->updateServerErrorResponseByStoreId($this->testStoreId, $this->testData, $e);
                    } catch (Exception $e) {
                        // 特に何も返されない
                    }
                }
            }
            $result = $this->callReachJob::where('store_id', $this->testStoreId)->get();
            $this->assertSame($this->testCallReachJobId, $result[0]['id']);
            $this->assertSame(1, $result[0]['repeat_count']);
            $this->assertSame('testturnid12345678901', $result[0]['turn_id']);
        }

        // 例外エラー
        {
            $callReachJob = $this->callReachJob::find($this->testCallReachJobId);
            $callReachJob->job_status = 'test';
            $callReachJob->save();

            try {
                // わざとエラーを起こすようにモック作成
                $mockRes = new Response(500, [], '{}');
                $mock    = new MockHandler([$mockRes]);
                $handler = HandlerStack::create($mock);
                $mockClient = new Client(['handler' => $handler]);
                $res = $mockClient->request('GET', 'http://example.com');
            } catch (Exception $e) {
                if ($e->getCode() === 500) {
                    try {
                        $this->callReachJob->updateServerErrorResponseByStoreId($this->testStoreId, $this->testData, $e);
                    } catch (Exception $e) {
                        // 特に何も返されない
                    }
                }
            }
            $result = $this->callReachJob::where('store_id', $this->testStoreId)->get();
            $this->assertSame($this->testCallReachJobId, $result[0]['id']);
            $this->assertSame($callReachJob->repeat_count, $result[0]['repeat_count']); // 更新されていないことが確認できる
            $this->assertSame('test', $result[0]['job_status']);  // 更新されていないことが確認できる
        }
    }

    public function testUpdateErrorResponseByStoreId()
    {
        $this->_createCallReachJob('CLOSED');

        // 正常
        {
            try {
                // わざとエラーを起こすようにモック作成
                $mockRes = new Response(400, [], '{"trn_id":"testturnid12345678901"}');
                $mock    = new MockHandler([$mockRes]);
                $handler = HandlerStack::create($mock);
                $mockClient = new Client(['handler' => $handler]);
                $res = $mockClient->request('GET', 'http://example.com');
            } catch (Exception $e) {
                if ($e->getCode() === 400) {
                    try {
                        $this->callReachJob->updateErrorResponseByStoreId($this->testStoreId, $this->testData, $e);
                    } catch (Exception $e) {
                        // 特に何も返されない
                    }
                }
            }
            $result = $this->callReachJob::where('store_id', $this->testStoreId)->get();
            $this->assertSame($this->testCallReachJobId, $result[0]['id']);
            $this->assertSame(1, $result[0]['repeat_count']);
            $this->assertSame('FAILED', $result[0]['job_status']);
            $this->assertSame('testturnid12345678901', $result[0]['turn_id']);
        }

        // 例外エラー
        {
            $callReachJob = $this->callReachJob::where('store_id', $this->testStoreId)->first();
            $callReachJob->job_status = 'test';
            $callReachJob->save();

            try {
                // わざとエラーを起こすようにモック作成
                $mockRes = new Response(400, [], '{}');
                $mock    = new MockHandler([$mockRes]);
                $handler = HandlerStack::create($mock);
                $mockClient = new Client(['handler' => $handler]);
                $res = $mockClient->request('GET', 'http://example.com');
            } catch (Exception $e) {
                if ($e->getCode() === 400) {
                    try {
                        $this->callReachJob->updateErrorResponseByStoreId($this->testStoreId, $this->testData, $e);
                    } catch (Exception $e) {
                        // 特に何も返されない
                    }
                }
            }
            $result = $this->callReachJob::where('store_id', $this->testStoreId)->get();
            $this->assertSame($this->testCallReachJobId, $result[0]['id']);
            $this->assertSame($callReachJob->repeat_count, $result[0]['repeat_count']); // 更新されていないことが確認できる
            $this->assertSame('test', $result[0]['job_status']);  // 更新されていないことが確認できる
        }
    }

    public function testUpdateResponseByStoreId()
    {
        $this->_createCallReachJob('CLOSED');

        // 正常
        {
            $mockRes = new Response(200, [], '{"trn_id":"testturnid12345678901"}');
            $mock    = new MockHandler([$mockRes]);
            $handler = HandlerStack::create($mock);
            $mockClient = new Client(['handler' => $handler]);
            $res = $mockClient->request('GET', 'http://example.com');
            $res_data = json_decode($res->getBody(), true);
            $this->callReachJob->updateResponseByStoreId($this->testStoreId, $this->testData, $res_data);
            $result = $this->callReachJob::where('store_id', $this->testStoreId)->get();
            $this->assertSame($this->testCallReachJobId, $result[0]['id']);
            $this->assertSame(1, $result[0]['repeat_count']);
            $this->assertSame('RUNNING', $result[0]['job_status']);
            $this->assertSame('testturnid12345678901', $result[0]['turn_id']);
        }

        // 例外エラー
        {
            $this->callReachJob->updateResponseByStoreId($this->testStoreId, $this->testData, []);
            $result = $this->callReachJob::where('store_id', $this->testStoreId)->get();
            $this->assertSame($this->testCallReachJobId, $result[0]['id']);
            $this->assertSame(1, $result[0]['repeat_count']); // 更新されていないことが確認できる
            $this->assertSame('RUNNING', $result[0]['job_status']);  // 更新されていないことが確認できる
            $this->assertSame('testturnid12345678901', $result[0]['turn_id']);
        }
    }

    public function testUpdateStatusWithMaxCount()
    {
        $this->_createCallReachJob('CLOSED', 3);

        // 正常
        {
            $this->callReachJob->updateStatusWithMaxCount();
            $result = $this->callReachJob::where('store_id', $this->testStoreId)->get();
            $this->assertSame($this->testCallReachJobId, $result[0]['id']);
            $this->assertSame('FAILED', $result[0]['job_status']);
        }
    }

    public function testUpdateResponse()
    {
        $this->_createCallReachJob('CLOSED');
        $res_data = ['trn_id' => 'testturnid12345678901'];
        $callReachJob = $this->callReachJob::find($this->testCallReachJobId);

        // updateResponse
        // 正常
        {
            $this->callReachJob->updateResponse($callReachJob, $res_data, 'request_data');
            $result = $this->callReachJob::find($this->testCallReachJobId);
            $this->assertSame('testturnid12345678901', $result['turn_id']);
            $this->assertSame('RUNNING', $result['job_status']);
        }

        // 例外エラー
        {
            $callReachJob = $this->callReachJob::find($this->testCallReachJobId);
            $callReachJob->job_status = 'test';
            $callReachJob->save();

            $this->callReachJob->updateResponse($callReachJob, [], 'request_data');
            $result = $this->callReachJob::find($this->testCallReachJobId);
            $this->assertSame('test', $result['job_status']);
        }

        // updateServerErrorResponse
        // 正常
        {
            try {
                // わざとエラーを起こすようにモック作成
                $mockRes = new Response(500);
                $mock    = new MockHandler([$mockRes]);
                $handler = HandlerStack::create($mock);
                $mockClient = new Client(['handler' => $handler]);
                $res = $mockClient->request('GET', 'http://example.com');
            } catch (Exception $e) {
                $this->callReachJob->updateServerErrorResponse($callReachJob, $res_data, 'request_data', $e);
                $result = $this->callReachJob::find($this->testCallReachJobId);
                $this->assertSame('testturnid12345678901', $result['turn_id']);
                $this->assertSame('RETRY', $result['job_status']);
                $this->assertSame($e->getMessage(), $result['response_data']);
            }
        }

        // 例外エラー
        {
            $callReachJob = $this->callReachJob::find($this->testCallReachJobId);
            $callReachJob->job_status = 'test';
            $callReachJob->save();

            $this->callReachJob->updateServerErrorResponse($callReachJob, [], 'request_data', null);
            $result = $this->callReachJob::find($this->testCallReachJobId);
            $this->assertSame('test', $result['job_status']); // 更新されていないことが確認できる
        }

        // updateErrorResponse
        // 正常
        {
            try {
                // わざとエラーを起こすようにモック作成
                $mockRes = new Response(400);
                $mock    = new MockHandler([$mockRes]);
                $handler = HandlerStack::create($mock);
                $mockClient = new Client(['handler' => $handler]);
                $res = $mockClient->request('GET', 'http://example.com');
            } catch (Exception $e) {
                $this->callReachJob->updateErrorResponse($callReachJob, $res_data, 'request_data', $e);
                $result = $this->callReachJob::find($this->testCallReachJobId);
                $this->assertSame('testturnid12345678901', $result['turn_id']);
                $this->assertSame('FAILED', $result['job_status']);
                $this->assertSame($e->getMessage(), $result['response_data']);
            }
        }

        // 例外エラー
        {
            $callReachJob = $this->callReachJob::find($this->testCallReachJobId);
            $callReachJob->job_status = 'test';
            $callReachJob->save();

            $this->callReachJob->updateErrorResponse($callReachJob, [], 'request_data', null);
            $result = $this->callReachJob::find($this->testCallReachJobId);
            $this->assertSame('test', $result['job_status']); // 更新されていないことが確認できる
        }
    }

    private function _createReservation()
    {
        $store = new Store();
        $store->name = 'テストtest店舗';
        $store->code = 'testtest01';
        $store->app_cd = 'RS';
        $store->published = 1;
        $store->save();
        $this->testStoreId = $store->id;

        $telSupport = new TelSupport();
        $telSupport->store_id = $this->testStoreId;
        $telSupport->is_tel_support = 0;
        $telSupport->save();

        $reservation = new Reservation();
        $reservation->app_cd = 'TO';
        $reservation->persons = 1;
        $reservation->last_name = 'TestCallReachJob';
        $reservation->pick_up_datetime = '2022-10-01 09:00';
        $reservation->save();
        $this->testReservationId = $reservation->id;

        $reservationStore = new ReservationStore();
        $reservationStore->reservation_id = $this->testReservationId;
        $reservationStore->store_id = $this->testStoreId;
        $reservationStore->save();
    }

    private function _createCallReachJob($job_status = 'NOT_ANSWER', $repeat_count = 0)
    {
        $callReachJob = new CallReachJob();
        $callReachJob->reservation_id = $this->testReservationId;
        $callReachJob->store_id = $this->testStoreId;
        $callReachJob->job_cd = 'RESERVE_RS';
        $callReachJob->job_status = $job_status;
        $callReachJob->turn_id = 'testturnid1234567890';
        $callReachJob->repeat_count = $repeat_count;
        $callReachJob->save();
        $this->testCallReachJobId = $callReachJob->id;
    }
}
