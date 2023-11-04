<?php

namespace App\Modules;

class BaseApi
{
    private $endpoint;
    private $curl;
    private $curlTimeout;
    private $curlConnectTimeout;

    public function __construct()
    {
        $this->curlTimeout = 40;
        $this->curlConnectTimeout = 20;
    }

    /**
     * GETリクエスト実行.
     *
     * @return array リクエスト結果
     */
    public function get(): array
    {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');

        return $this->exec();
    }

    /**
     * POSTリクエスト実行.
     *
     * @return array リクエスト結果
     */
    public function post(array $params): array
    {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($params));

        return $this->exec();
    }

    /**
     * PUTリクエスト実行.
     *
     * @return array リクエスト結果
     */
    public function put(array $params): array
    {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');

        return $this->exec();
    }

    /**
     * DELETEリクエスト実行.
     *
     * @return array リクエスト結果
     */
    public function delete($params): array
    {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');

        return $this->exec();
    }

    /**
     * CURL実行.
     *
     * @return array リクエスト結果
     *
     * @throw \Exception
     */
    protected function exec(): array
    {
        curl_setopt($this->curl, CURLOPT_URL, $this->endpoint);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $this->curlConnectTimeout);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->curlTimeout);
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $res = curl_exec($this->curl);
        // CURLエラー
        if (curl_errno($this->curl)) {
            throw new \Exception('Curl Error No = '.curl_errno($this->curl));
        }
        // エラー
        $httpcode = curl_getinfo($this->curl, CURLINFO_RESPONSE_CODE);
        if ($httpcode !== 200) {
            $resArr = json_decode($res, true);
            if (isset($resArr['errors'])) {
                $msg = '';
                foreach ($resArr['errors'] as $k => $v) {
                    $msg = $v[0];
                    // エラーメッセージは１つ返す
                    break;
                }
                throw new \Exception($msg);
            }
        }

        //\Log::debug(json_decode($res, true));
        curl_close($this->curl);
        $res = json_decode($res, true);

        return is_null($res) ? [] : $res;
    }

    /**
     * エンドポイント設定.
     *
     * @param string value
     */
    protected function setEndpoint(string $value): void
    {
        $this->endpoint = $value;
    }

    /**
     * CURLOPT_TIMEOUT 設定.
     *
     * @param int value
     */
    protected function setCurlTimeout(int $value): void
    {
        $this->curlTimeout = $value;
    }

    /**
     * CURLOPT_CONNECTTIMEOUT 設定.
     *
     * @param int value
     */
    protected function setCurlConnectTimeout(int $value): void
    {
        $this->curlConnectTimeout = $value;
    }
}
