<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Libs\CommonLog;
use Exception;

class CallReachJob extends Model
{
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
    ];

    protected $dates = ['pick_up_datetime'];

    public function createJob(string $jobCd, Reservation $reservation) {

        DB::transaction(function () use($jobCd, $reservation){
            try {
                $reservationStore = ReservationStore::with('store')->where('reservation_id', $reservation->id)->first();
                // 電話通知の設定が「不要」であったら、jobを作成せず終了
                $telSupport = TelSupport::where('store_id', $reservationStore->store->id)->where('is_tel_support', 1)->first();
                if(!$telSupport) {
                    return;
                }

                $callReachjob = new CallReachJob();
                $callReachjob->reservation_id = $reservation->id;
                $callReachjob->store_id = $reservationStore->store->id;
                $callReachjob->job_status = 'SET';
                $callReachjob->job_cd = $jobCd;
                $callReachjob->persons = $reservation->persons;
                $callReachjob->name = $reservation->last_name;
                if($jobCd === 'CANCEL') {
                    $callReachjob->pick_up_datetime = $reservation->pick_up_datetime;
                }else {
                    $callReachjob->pick_up_datetime = $reservation->pick_up_datetime.':00';
                }
                $callReachjob->repeat_count = 0;
                $callReachjob->save();
            } catch(Exception $e) {
                CommonLog::notifyToChat(
                    '【CallReach】job作成エラー　予約ID:'. $reservation->id,
                    $e->getMessage()
                );
                return;
            }
        });
    }

    public function receiveResult($request) {

        DB::transaction(function () use($request){
            try {
                if($request['dial_stat'] === 'ANSWER') {
                    CallReachJob::where('turn_id', $request['trn_id'])
                    ->update([
                        'job_status' => 'FINISH',
                        'result_data' => json_encode($request->input()),
                    ]);
                }else {
                    CallReachJob::where('turn_id', $request['trn_id'])
                    ->update([
                        'job_status' => 'NOT_ANSWER',
                        'result_data' => json_encode($request->input()),
                    ]);
                }
            } catch(Exception $e) {
                CommonLog::notifyToChat(
                    '【CallReach】CallReachJobsテーブルへの電話通知結果反映でエラー　trn_id:'. $request['trn_id'],
                    $e->getMessage()
                );
                return;
            }
        });
    }

    public function getClosedJobsCount() {
        return CallReachJob::select(DB::raw('count(*) as count, store_id'))
        ->where('job_status', 'CLOSED')
        ->where('repeat_count', '<', 3)
        ->groupBy('store_id')
        ->get();
    }

    public function getJobs() {
        return CallReachJob::whereIn('job_status', ['SET', 'RETRY'])
        ->where('repeat_count', '<', 3)
        ->get();
    }

    public function updateServerErrorResponseByStoreId($storeId, $requestData, $errorResponse) {
        $res_data = json_decode($errorResponse->getResponse()->getBody(), true);
        $err_msg = $errorResponse->getMessage();

        DB::transaction(function () use($storeId, $res_data, $requestData, $err_msg){
            try {
                CallReachJob::where('job_status', 'CLOSED')
                ->where('repeat_count', '<', 3)
                ->where('store_id', $storeId)
                ->update([
                    'repeat_count' => DB::raw('repeat_count+1'),
                    'turn_id' => $res_data['trn_id'],
                    'request_data' => $requestData,
                    'response_data' => $err_msg,
                ]);
            }catch (Exception $e){
                CommonLog::notifyToChat(
                    '【CallReach：営業時間外フロー】エラー情報(500)更新エラー　店舗ID：'. $storeId,
                    $e->getMessage()
                );
            }
        });
    }

    public function updateErrorResponseByStoreId($storeId, $requestData, $errorResponse) {
        $res_data = json_decode($errorResponse->getResponse()->getBody(), true);
        $err_msg = $errorResponse->getMessage();
        DB::transaction(function () use($storeId, $res_data, $requestData, $err_msg){
            try {
                CallReachJob::where('job_status', 'CLOSED')
                ->where('repeat_count', '<', 3)
                ->where('store_id', $storeId)
                ->update([
                    'repeat_count' => DB::raw('repeat_count+1'),
                    'turn_id' => $res_data['trn_id'],
                    'job_status'   => 'FAILED',
                    'request_data' => $requestData,
                    'response_data' => $err_msg,
                ]);
            }catch (Exception $e){
                CommonLog::notifyToChat(
                    '【CallReach：営業時間外フロー】エラー情報更新エラー　店舗ID：'. $storeId,
                    $e->getMessage()
                );
            }
        });
    }

    public function updateResponseByStoreId($storeId, $reqData, $resData) {
        DB::transaction(function () use($storeId, $reqData, $resData){
            try {
                CallReachJob::where('job_status', 'CLOSED')
                    ->where('repeat_count', '<', 3)
                    ->where('store_id', $storeId)
                    ->update([
                        'repeat_count' => DB::raw('repeat_count+1'),
                        'turn_id' => $resData['trn_id'],
                        'job_status'   => 'RUNNING',
                        'request_data' => $reqData,
                        'response_data' => $resData,
                    ]);
            }catch (Exception $e){
                CommonLog::notifyToChat(
                    '【CallReach：営業時間外フロー】レスポンス情報更新エラー　店舗ID：'. $storeId,
                    $e->getMessage()
                );
            }
        });
    }

    public function updateStatusWithMaxCount() {
        DB::transaction(function () {
            try {
                $callReachJobs = CallReachJob::where('repeat_count', '>=', 3)
                ->whereIn('job_status', ['SET', 'CLOSED', 'RETRY'])
                ->pluck('id')->toArray();
                if(!empty($callReachJobs)) {
                    $ids = implode(',', $callReachJobs);
                    CommonLog::notifyToChat(
                        '【CallReach】次のJobがリトライ回数3に達したためステータスをFAILEDに更新します',
                        $ids
                    );
                }

                CallReachJob::where('repeat_count', '>=', 3)
                ->whereIn('job_status', ['SET', 'CLOSED', 'RETRY'])
                ->update(['job_status' => 'FAILED']);

            }catch (Exception $e){
                CommonLog::notifyToChat(
                    '【CallReach】リトライ回数が３に達したデータのステータス更新でエラー',
                    $e->getMessage()
                );
            }
        });
    }

    public function updateResponse($callReachJob, $res_data, $data){
        DB::transaction(function () use($callReachJob, $res_data, $data){
            try {
                $callReachJob->turn_id = $res_data['trn_id'];
                $callReachJob->request_data = $data;
                $callReachJob->response_data = $res_data;
                $callReachJob->repeat_count = $callReachJob->repeat_count + 1;
                $callReachJob->job_status = 'RUNNING';
                $callReachJob->save();
            }catch (Exception $e){
                CommonLog::notifyToChat(
                    '【CallReach】レスポンス情報更新エラー　JobID：'. $callReachJob->id,
                    $e->getMessage()
                );
            }
        });
    }

    public function updateServerErrorResponse($callReachJob, $res_data, $data, $e){
        DB::transaction(function () use($callReachJob, $res_data, $data, $e){
            try {
                $callReachJob->turn_id = $res_data['trn_id'];
                $callReachJob->request_data = $data;
                $callReachJob->response_data = $e->getMessage();
                $callReachJob->repeat_count = $callReachJob->repeat_count + 1;
                $callReachJob->job_status = 'RETRY';
                $callReachJob->save();
            }catch (Exception $e){
                CommonLog::notifyToChat(
                    '【CallReach】エラー(500)レスポンス情報更新エラー　JobID：'. $callReachJob->id,
                    $e->getMessage()
                );
            }
        });
    }

    public function updateErrorResponse($callReachJob, $res_data, $data, $e){
        DB::transaction(function () use($callReachJob, $res_data, $data, $e){
            try {
                $callReachJob->turn_id = $res_data['trn_id'];
                $callReachJob->request_data = $data;
                $callReachJob->response_data = $e->getMessage();
                $callReachJob->repeat_count = $callReachJob->repeat_count + 1;
                $callReachJob->job_status = 'FAILED';
                $callReachJob->save();
            }catch (Exception $e){
                CommonLog::notifyToChat(
                    '【CallReach】エラーレスポンス情報更新エラー　JobID：'. $callReachJob->id,
                    $e->getMessage()
                );
            }
        });
    }
}
