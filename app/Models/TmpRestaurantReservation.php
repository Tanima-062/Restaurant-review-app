<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Libs\Cipher;
use Illuminate\Support\Facades\Lang;

class TmpRestaurantReservation extends Model
{
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * レストラン-予約申し込み事前処理/予約変更
     *
     * @param string $sessionId
     * @param array $info
     * @param string $msg
     * @param boolean $isChange
     *
     * @return boolean 成功:true 失敗:false
     */
    public function saveSession(string $sessionId, array $info, &$msg = null, $isChange = null)
    {
        try {

            // 暗号化
            $info['customer']['firstName'] = Cipher::encrypt($info['customer']['firstName']);
            $info['customer']['lastName'] = Cipher::encrypt($info['customer']['lastName']);
            $info['customer']['email'] = Cipher::encrypt($info['customer']['email']);
            $info['customer']['tel'] = Cipher::encrypt($info['customer']['tel']);
            if (isset($info['customer']['request'])) {
                $info['customer']['request'] = Cipher::encrypt($info['customer']['request']);
            }

            $rec = new TmpRestaurantReservation();
            $rec->session_id = $sessionId;
            $rec->info = json_encode($info);
            if ($isChange) {
                $rec->is_change = 1;
            }

            if (!$rec->save()) {
                throw new \Exception();
            }
        } catch (\Throwable $e) {
            \Log::error(
            sprintf(
                '::sessionId=(%s), info=(%s), error=%s',
                $sessionId,
                json_encode($info),
                $e
            ));

            $msg = empty($msg) ? 'save failed' : $msg;

            return false;
        }

        return true;
    }

    /**
     *  レストラン-予約完了 ステータス変更.
     *
     * @param  string sessionId
     * @param  array resValues
     * @param  string status
     *
     * @return bool 成功:true 失敗:false
     */
    public function saveRes(string $sessionId, $resValues, $status): bool
    {
        try {
            $rec = TmpRestaurantReservation::where('session_id', $sessionId)->first();

            $rec->response = json_encode($resValues);
            $rec->status = $status;
            $rec->save();

            return true;
        } catch (\Throwable $e) {
            \Log::error($e);

            return false;
        }
    }

    /**
     * レストラン-予約完了 一時データ取得
     *
     * @throws Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws Exception
     *
     * @return array
     */
    public function getInfo(string $sessionId)
    {
        try {
            $rec = TmpRestaurantReservation::where('session_id', $sessionId)->firstOrFail();

            if (!is_null($rec->info)) {
                $info = json_decode($rec->info, true);
            }

            if (is_null($info)) {
                throw new \Exception('info column is null');
            }
        } catch (\Throwable $e) {
            throw $e;
        }

        return $info;
    }

}
