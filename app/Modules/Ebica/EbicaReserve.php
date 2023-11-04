<?php

namespace App\Modules\Ebica;

use App\Modules\Ebica\EbicaBase;
use App\Modules\Ebica\EbicaValidation;

class EbicaReserve extends EbicaBase {

    /**
     * 予約登録
     * リクエストエラー,paramsに不正がある際はfalseを返す
     *
     * @param array $params
     *
     * @return boolean
     */
    public function postReservation(array $params)
    {
        // トークン取得
        $token = $this->getToken();

        if (is_string($token)) {

            $validate = new EbicaValidation;
            if ($validate->validateReserve($params)) {

                //リクエスト用配列
                $request = [
                    'method' => 'POST',
                    'path' => '/v2/shops/'.$params['shop_id'].'/reservations',
                    'options' => [
                        'headers' => [
                            'Authorization' => 'bearer '.$token,
                            'x-api-key' => $this->ebicaApiKey,
                            'Content-Type' => $this->contentType,
                        ],
                        'json' => [
                            'date' => $params['date'],
                            'time' => $params['time'],
                            'first_name_kana' => $params['first_name'],
                            'last_name_kana' => $params['last_name'],
                            'headcount' => (int) $params['headcount'],
                            'phone_number' => $params['phone_number'],
                            'email' => $params['email'],
                            'note' => "コース名(数量) : {$params['course_name']}({$params['course_count']})\n追加オプション : {$params['option']}\n事前決済 : {$params['prepaid']}\n備考 : {$params['remarks']}"
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
                $this->errorMsg = $validate->errorMsg;
                return false;
            }

        } else {
            $this->errorMsg = 'validation error(token)';
            return false;
        }
    }

}
