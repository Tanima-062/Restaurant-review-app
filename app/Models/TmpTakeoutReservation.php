<?php

namespace App\Models;

use App\Libs\Cipher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;

class TmpTakeoutReservation extends Model
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
     * テイクアウト-予約申し込み事前処理.
     *
     * @param  string sessionId
     * @param  array info
     * @param  string msg
     *
     * @return bool 成功:true 失敗:false
     */
    public function saveSession(string $sessionId, array $info, &$msg = null)
    {
        try {
            $stock = new Stock();
            $orderInterval = new OrderInterval();
            $menuModel = new Menu();
            $menuIdAndCount = [];
            $tmp = [];
            foreach ($info['application']['menus'] as $menu) {
                if (!array_key_exists($menu['menu']['id'], $tmp)) {
                    $tmp[$menu['menu']['id']] = $menu['menu']['count'];
                } else {
                    $tmp[$menu['menu']['id']] = $tmp[$menu['menu']['id']] + $menu['menu']['count'];
                }
            }
            $tmp3 = [];
            foreach ($tmp as $menuId => $count) {
                $tmp2 = ['menu' => []];
                $tmp2['menu']['id'] = $menuId;
                $tmp2['menu']['count'] = $count;
                $tmp3[] = $tmp2;
            }
            $menuIdAndCount = $tmp3;

            $dt = new Carbon($info['application']['pickUpDate'].' '.$info['application']['pickUpTime']);

            foreach ($menuIdAndCount as $menu) {
                $menuObj = Menu::find($menu['menu']['id']);
                // opening_hours store menuの曜日と祝日のチェック
                if (!$menuModel->canSale($menuObj->id, $menuObj->store_id, $dt, $msg)) {
                    throw new \Exception($msg);
                }

                // 在庫チェック
                if (!$stock->hasStock($info['application']['pickUpDate'], $menu['menu']['id'], $menu['menu']['count'])) {
                    $msg = Lang::get('message.stockCheckFailure');
                    throw new \Exception('no stocks left');
                }

                // 同時間帯注文組数チェック
                $message = null;
                if (!$orderInterval->isOrderable($info['application']['pickUpDate'], $info['application']['pickUpTime'], $menu['menu']['id'], $menu['menu']['count'], $message)) {
                    $msg = (empty($message)) ? Lang::get('message.intervalOrderCheckFailure') : $message;
                    throw new \Exception('reached order limit');
                }
            }

            // 暗号化
            $info['customer']['firstName'] = Cipher::encrypt($info['customer']['firstName']);
            $info['customer']['lastName'] = Cipher::encrypt($info['customer']['lastName']);
            $info['customer']['email'] = Cipher::encrypt($info['customer']['email']);
            $info['customer']['tel'] = Cipher::encrypt($info['customer']['tel']);
            if (isset($info['customer']['request'])) {
                $info['customer']['request'] = Cipher::encrypt($info['customer']['request']);
            }

            $rec = new TmpTakeoutReservation();
            $rec->session_id = $sessionId;
            $rec->info = json_encode($info);

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
     * テイクアウト-予約完了 一時データ削除.
     *
     * @param  string sessionId
     *
     * @return bool 成功:true 失敗:false
     */
    public function deleteSession(string $sessionId): bool
    {
        try {
            TmpTakeoutReservation::where('session_id', $sessionId)->delete();
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * テイクアウト-予約完了 ステータス変更.
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
            $rec = TmpTakeoutReservation::where('session_id', $sessionId)->first();

            if (is_null($rec->status)) {
                $rec->response = json_encode($resValues);
                $rec->status = $status;
                $rec->save();
            }

            return true;
        } catch (\Throwable $e) {
            \Log::error($e);

            return false;
        }
    }

    /**
     * テイクアウト-予約完了 一時データ取得.
     *
     * @param  string sessionId
     *
     * @throws Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws Exception
     *
     * @return array
     */
    public function getInfo(string $sessionId)
    {
        try {
            $rec = TmpTakeoutReservation::where('session_id', $sessionId)->firstOrFail();

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
