<?php

namespace App\Modules\Ebica;
use App\Modules\Ebica\EbicaBase;
use App\Modules\Ebica\EbicaValidation;

class EbicaSearch extends EbicaBase {

    /**
     * 予約検索
     * 取得できない場合はfalseを返す
     *
     * @param array $params
     *
     * @return object|boolean
     */
    public function getReservation(array $params)
    {
        // トークン取得
        $token = $this->getToken();

        if (is_string($token)) {

            $validate = new EbicaValidation;
            if (is_array($validate->validateSearch($params))){

                //リクエスト用配列
                $request = [
                    'method' => 'GET',
                    'path' => '/v2/shops/'.$params['shop_id'].'/reservations',
                    'options' => [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $token,
                            'x-api-key' => $this->ebicaApiKey,
                            'Content-Type' => $this->contentType,
                        ],
                        'query' => $params,
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
        } else {
            $this->errorMsg = 'validation error(token)';
            return false;
        }
    }

}
