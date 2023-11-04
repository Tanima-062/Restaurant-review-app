<?php

namespace App\Modules\Ebica;
use App\Modules\Ebica\EbicaBase;

class EbicaReserveSetting extends EbicaBase {

    /**
     * 店舗のWEB予約設定情報の取得
     * 取得できない場合はfalseを返す
     *
     * @param integer $shopId
     *
     * @return array|boolean
     */
    public function getReservationSetting(int $shopId)
    {
        //トークン取得
        $token = $this->getToken();

        if (is_string($token)) {

            //リクエスト用配列
            $request = [
                'method' => 'GET',
                'path' => '/v2/shops/'.$shopId.'/web_reservation_setting',
                'options' => [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'x-api-key' => $this->ebicaApiKey,
                        'Content-Type' => $this->contentType,
                    ],
                ]
            ];

            //リクエスト送信
            $response = $this->doRequest($request);
            if (!$response) {
                return false;
            }

            return json_decode($response->getBody(), true);
        } else {
            $this->errorMsg = 'validation error(token)';
            return false;
        }
    }
}
