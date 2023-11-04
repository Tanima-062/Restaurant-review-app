<?php

namespace App\Modules\Ebica;

use App\Libs\CommonLog;
use GuzzleHttp\Client;
use App\Models\EbicaToken;
use Carbon\Carbon;

class EbicaBase
{
    protected $ebicaApiKey;               //APIキー
    protected $ebicaUserId;               //ユーザーID
    protected $ebicaPassword;             //パスワード
    protected $ebicaApiDomain;            //APIドメイン
    protected $contentType;               //Content-Type
    protected $acceptEncoding;            //Accept-Encodeing

    public $errorMsg = '';              //エラーメッセージ

    public function __construct()
    {
        $this->ebicaApiKey = config('services.ebica.key');
        $this->ebicaUserId = config('services.ebica.user');
        $this->ebicaPassword = config('services.ebica.password');
        $this->ebicaApiDomain = config('services.ebica.domain');
        $this->contentType = 'application/json';
        $this->acceptEncoding = 'gzip,deflate';
    }

    /**
     * ebicaAPI通信部.
     *
     * @param array $params
     *
     * @return mixed
     */
    public function doRequest($params)
    {
        $client = new Client();
        $method = $params['method'];
        $sendUrl = $this->ebicaApiDomain.$params['path'];
        $options = $params['options'];

        //リクエスト送信
        try {
            $response = $client->request($method, $sendUrl, $options);
        } catch (\Exception $e) {
            CommonLog::notifyToChat(
                'ebisol',
                $e->getMessage()
            );

            return false;
        }

        return $response;
    }

    /**
     * 認証トークンの取得
     * 取得できない場合はfalseを返す.
     *
     * @return string|bool
     */
    public function getToken()
    {
        $threeDaysAgo = Carbon::today()->subDays(3);
        $isExistApiToken = EbicaToken::where('api_cd', config('code.externalApiCd.ebica'))->first();

        // APIトークンが存在する場合
        if (!is_null($isExistApiToken)) {
            $apiToken = EbicaToken::where('api_cd', config('code.externalApiCd.ebica'))->where('updated_at', '<=', $threeDaysAgo->format('Y-m-d H:i:s'))->first();

            // 3日前より更に古いAPIトークンが存在する場合
            if (!is_null($apiToken)) {
                $newApiToken = $this->makeRequest();
                if (!$newApiToken){
                    return false;
                }

                $isExistApiToken->update([
                    'token' => $newApiToken,
                ]);

                return $newApiToken;
            } else { // APIトークンの更新日が3日以内の場合
                return $isExistApiToken->token;
            }
        } else { // APIトークンが存在しない場合
            $newApiToken = $this->makeRequest();
            if (!$newApiToken) {
                return false;
            }

            EbicaToken::create([
                'api_cd' => config('code.externalApiCd.ebica'),
                'token' => $newApiToken,
            ]);
            return $newApiToken;
        }
    }

    /**
     * リクエスト作成＆実行
     * 取得できない場合はfalseを返す.
     *
     * @return string|bool
     */
    public function makeRequest() {
        // リクエスト用配列
        $request = [
            'method' => 'POST',
            'path' => '/v2/auth',
            'options' => [
                'headers' => [
                    'x-api-key' => $this->ebicaApiKey,
                    'Content-Type' => $this->contentType,
                ],
                'query' => [
                    'user_id' => $this->ebicaUserId,
                ],
                'json' => [
                    'password' => $this->ebicaPassword,
                ],
            ],
        ];

        //リクエスト送信
        $response = $this->doRequest($request);
        if (!$response) {
            return false;
        }

        return json_decode($response->getBody())->token;
    }
}
