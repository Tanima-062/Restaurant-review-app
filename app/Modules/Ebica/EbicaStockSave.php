<?php

namespace App\Modules\Ebica;
use App\Modules\Ebica\EbicaBase;
use App\Modules\Ebica\EbicaReserveSetting;
use App\Models\Vacancy;
use App\Modules\Reservation\IFStock;
use DB;
use Carbon\Carbon;
use App\Models\Store;

class EbicaStockSave extends EbicaBase implements IFStock {

    /**
     * 空席情報の取得(直接参照)
     * 取得できない場合はfalseを返す
     *
     * @param int $shopId
     * @param string $reservationDate
     *
     * @return object|boolean
     */
    public function getStock(int $shopId, string $reservationDate)
    {
        // トークン取得
        $token = $this->getToken();

        if (is_string($token)) {

            //リクエスト用配列
            $request = [
                'method' => 'GET',
                'path' => '/v2/shops/'.$shopId.'/stocks',
                'options' => [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'x-api-key' => $this->ebicaApiKey,
                        'Content-Type' => $this->contentType,
                        'Accept-Encoding' => $this->acceptEncoding,
                    ],
                    'query' => [
                        'reservation_date' => $reservationDate,
                    ],
                ]
            ];

            //リクエスト送信
            $response = $this->doRequest($request);
            if (!$response) {
                return false;
            }

            return json_decode($response->getBody());
        } else {
            $this->errorMsg = 'validation error(token)';
            return false;
        }
    }

    /**
     * 空席情報概要の取得
     * 取得できない場合はfalseを返す
     *
     * @param int $shopId
     * @param string $since
     * @param string $until
     *
     * @return array|boolean
     */
    public function getRoundedStock(int $shopId, string $since, string $until)
    {
        // トークン取得
        $token = $this->getToken();

        if (is_string($token)) {

            //リクエスト用配列
            $request = [
                'method' => 'GET',
                'path' => '/v2/shops/'.$shopId.'/rounded_stocks',
                'options' => [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'x-api-key' => $this->ebicaApiKey,
                        'Content-Type' => $this->contentType,
                    ],
                    'query' => [
                        'since' => $since,
                        'until' => $until,
                    ],
                ]
            ];

            //リクエスト送信
            $response = $this->doRequest($request);
            if (!$response) {
                return false;
            }

            return json_decode($response->getBody());
        } else {
            $this->errorMsg = 'validation error(token)';
            return false;
        }
    }

    /**
     * 空席情報をDBに登録or更新
     * 情報がなければDBに情報追加
     * 情報が既にDBにありstockに変更があれば更新
     * 店舗の時間設定、人数設定に変更があれば既存の情報を削除し入れ直し
     * 成功:true 失敗:false
     *
     * @param int $apiShopId
     * @param int $shopId
     * @param string $reservationDate
     *
     * @return boolean
     */
    public function storeStock(int $apiShopId, int $shopId, string $reservationDate)
    {

        $insert = [];
        $stockData = $this->getStock($apiShopId, $reservationDate);
        $now = Carbon::now();       //created_ad用の現在時刻

        //インサート用配列の作成
        foreach ($stockData->stocks as $stocks) {
            foreach ($stocks->stock as $stock) {
                $data['api_store_id'] = $apiShopId;
                $data['date'] = $reservationDate;
                $data['time'] = $stock->reservation_time.':00';
                $data['headcount'] = $stocks->headcount;
                $data['stock'] = $stock->sets;
                $data['store_id'] = $shopId;
                $data['created_at'] = $now;
                $insert[] = $data;
            }
        }

        //レコードの存在チェック
        if (Vacancy::where('api_store_id', $apiShopId)
            ->where('date', $reservationDate)->exists()) {

            $oldStocks = Vacancy::where('api_store_id', $apiShopId)
                ->where('date', $reservationDate)->get();

            //店舗の設定が変更されていた場合(レコード数と差があった場合)
            if ($oldStocks->count() !== count($insert)) {

                //レコード削除
                Vacancy::where('api_store_id', $apiShopId)
                ->where('date', $reservationDate)->delete();

                //情報登録
                try {
                    DB::beginTransaction();
                    $result = Vacancy::insert($insert);
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollback();
                    $this->errorMsg = $e->getMessage();
                    return false;
                }
                return $result;
            }

            $counter = 0;       //ループ用カウンター

            //レコード更新
            try {
                DB::beginTransaction();
                foreach ($oldStocks as $oldStock) {
                    if ($oldStock->time === $insert[$counter]['time']
                    && $oldStock->headcount === $insert[$counter]['headcount']
                    && $oldStock->stock !== $insert[$counter]['stock']) {

                        $oldStock->stock = $insert[$counter]['stock'];
                        $oldStock->save();
                    }
                    ++$counter;
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                $this->errorMsg = $e->getMessage();
                return false;
            }

            return true;

        } else {

            //情報登録
            try {
                DB::beginTransaction();
                $result = Vacancy::insert($insert);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                $this->errorMsg = $e->getMessage();
                return false;
            }

            return $result;
        }
    }

    /**
     * 予約完了/事前処理/変更 空席があるかの確認
     *
     * @param Carbon $dt
     * @param int $headcount
     * @param int $storeId
     * @param string $msg
     *
     * @return boolean
     */
    public function isVacancy(Carbon $dt, int $headcount, int $storeId, string &$msg = null)
    {
        //予約人数のチェック
        $ebicaReserveSetting = new EbicaReserveSetting;
        $apiShopId = Store::find($storeId)->external_api->api_store_id;
        $setting = $ebicaReserveSetting->getReservationSetting($apiShopId);

        if ($setting['maximum_headcount'] < $headcount) {
            $msg = sprintf(\Lang::get('message.headcountCheckFailure0'), $setting['maximum_headcount']);
            return false;
        } elseif ($setting['minimum_headcount'] > $headcount) {
            $msg = sprintf(\Lang::get('message.headcountCheckFailure1'), $setting['minimum_headcount']);
            return false;
        }

        //ebicaAPIのレスポンスから該当の空席情報を検索
        $stockData = json_decode(json_encode($this->getStock($apiShopId, $dt->format('Y-m-d'))), true);
        $headcountKey = array_search($headcount, array_column($stockData['stocks'], 'headcount'));
        if ($headcountKey === false) {
            $msg = \Lang::get('message.vacancyCheckFailure2');
            return false;
        }
        $timeKey = array_search($dt->format('H:i'), array_column($stockData['stocks'][$headcountKey]['stock'], 'reservation_time'));
        if ($timeKey === false) {
            $msg = \Lang::get('message.vacancyCheckFailure1');
            return false;
        }
        $stock = $stockData['stocks'][$headcountKey]['stock'][$timeKey]['sets'];
        if ($stock === 0) {
            $msg = \Lang::get('message.vacancyCheckFailure0');
            return false;
        }

        return true;
    }
}
