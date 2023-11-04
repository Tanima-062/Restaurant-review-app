<?php

namespace App\Modules\Payment;
use GuzzleHttp\Client;

class Gateway {

    protected $contentType;

    public function __construct()
    {
        $this->contentType = 'application/json';
    }

    /**
     * GatewayAPI通信部
     *
     * @param array $params
     *
     * @return mixed
     */
    public function doRequest($params)
    {
        $client = new Client();
        $method = $params['method'];
        $options = $params['options'];
        if (config('app.env') === 'production' || config('app.env') === 'staging') {
            $sendUrl = config('const.gateway.endPoint.production').$params['path'];
        } else {
            $sendUrl = config('const.gateway.endPoint.develop').$params['path'];
        }

        //リクエスト送信
        try {
            $response = $client->request($method, $sendUrl, $options);
        } catch (\Exception $e) {
            \Log::error("[Gateway] {$e->getMessage()}");
            return false;
        }
        return $response;
    }

    /**
     * カートIDを外部から登録する
     *
     * @param int $cmApplicationId
     *
     * @return array
     */
    public function externalRegistration($cmApplicationId, $appCd)
    {
        $request = [
            'method' => 'POST',
            'path' => config('const.gateway.path.externalRegistration'),
            'options' => [
                'headers' => [
                    'Content-Type' => $this->contentType,
                ],
                'json' => [
                    'offerId' => [$appCd.$cmApplicationId], // $appCdは小文字じゃないとリクエスト先で500エラー
                ],
            ]
        ];

        //リクエスト送信
        $response = $this->doRequest($request);
        if (!$response) {
            return false;
        }

        return json_decode($response->getBody(), true);
    }
}

