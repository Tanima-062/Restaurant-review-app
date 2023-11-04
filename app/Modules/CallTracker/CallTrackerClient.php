<?php

namespace App\Modules\CallTracker;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Libs\CommonLog;

class CallTrackerClient
{
    /**
     * 認証用のトークンを取得する
     * @return mixed|string
     */
    public function getToken()
    {
        $request = [
            'method' => 'POST',
            'path' => '/ct/3.0a/uauth/token/',
            'options' => [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'userid' => env('CALL_TRACKER_USER_ID'),
                    'password' => env('CALL_TRACKER_PASSWORD'),
                ],
            ],
        ];

        $response = $this->doRequest($request);
        if (!$response) {
            return '';
        }

        $result = json_decode($response->getBody(), true);

        return $result['result']['token'] ?? '';
    }

    /**
     * @param string $token
     * @param string $start
     * @param string $end
     * @return string
     */
    public function getLogs(string $token, string $start, string $end): string
    {
        $request = [
            'method' => 'GET',
            'path' => '/ct/3.0a/calllogs/',
            'options' => [
                'headers' => [
                    'Content-Type' => 'text/csv',
                    'Authorization' => 'Bearer '.$token,
                ],
                'query' => [
                    'start' => $start,
                    'end' => $end
                ],
            ],
        ];

        $response = $this->doRequest($request);
        if (!$response) {
            return '';
        }

        return $response->getBody()->getContents();
    }

    private function doRequest(array $params)
    {
        $client = new Client();
        $method = $params['method'];
        $domain = env('CALL_TRACKER_DOMAIN');
        $partnerId = env('CALL_TRACKER_PARTNER_ID');

        if (empty($domain) || empty($partnerId)) {
            return false;
        }

        $url = $domain.$params['path'].$partnerId;
        $options = $params['options'];

        try {
            $response = $client->request($method, $url, $options);
        } catch (GuzzleException | \Exception $e) {
            CommonLog::notifyToChat(
                'CallTracker Log API',
                $e->getMessage()
            );

            return false;
        }

        return $response;
    }
}
