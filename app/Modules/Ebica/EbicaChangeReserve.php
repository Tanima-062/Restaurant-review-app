<?php

namespace App\Modules\Ebica;
use App\Modules\Ebica\EbicaBase;
use App\Modules\Ebica\EbicaValidation;

class EbicaChangeReserve extends EbicaBase {

    /**
     * 予約変更
     * リクエストエラー,paramsに不正がある際はfalseを返す
     *
     * @param array $params
     *
     * @return boolean
     */
    public function patchReservation(array $params)
    {
        // トークン取得
        $token = $this->getToken();

        if (is_string($token)) {

            $validate = new EbicaValidation;
            if (is_array($validate->validateChangeReserve($params))) {

                //リクエスト用配列
                $request = [
                    'method' => 'PATCH',
                    'path' => '/v2/shops/'.$params['shop_id'].'/reservations/'.$params['reservation_id'],
                    'options' => [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $token,
                            'x-api-key' => $this->ebicaApiKey,
                            'Content-Type' => $this->contentType,
                        ],
                        'json' => $validate->validateChangeReserve($params),
                    ]
                ];

                //リクエスト送信
                $response = $this->doRequest($request);
                if (!$response) {
                    return false;
                }

                return json_decode($response->getBody(), true);
            } else {
                $this->errorMsg = $validate->errorMsg;
                return false;
            }

        }else{
            $this->errorMsg = 'validation error(token)';
            return false;
        }
    }
}
