<?php

namespace App\Libs\Fax;

use App\Libs\CommonLog;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;

class Faximo
{
    const URL_FAXIMO = 'https://rest.faximo.jp/faximo/v1/request.json';
    const URL_FAXIMO_SILVER = 'https://rest.faximo.jp/snd/v1/request.json';

    private $header = [];
    private $body = [];
    private $attachStr = '';

    private $response = null;

    public function __construct()
    {
    }

    /**
     * faxメタ情報作成(header).
     *
     * @param int reservationId
     *
     * @return array
     */
    public static function makeHeader(int $reservationId)
    {
        return [
                'Content-Type' => 'application/json;charset=UTF-8',
                'X-Auth' => config('const.fax.x-auth'),
                'X-Processkey' => sprintf("ad%d-%s", $reservationId, time()),
            ];
    }

    /**
     * faxメタ情報作成(body).
     *
     * @param string faxNo
     * @param string userkey
     * @param int reservationId
     * @param string pdfName
     *
     * @return array
     */
    public static function makeBody(string $faxNo, string $userkey, int $reservationId, string $pdfName)
    {
        return  [
            'sendto' => [
                ["faxno" => $faxNo]
            ],
            'userkey' => $userkey,
            'tsi' => 'from adventure.inc',
            'headerinfo' => "@d@ @t@ @T@ @p@",
            'resaddress' => config('const.fax.address'),
            'subject' => '<<<Reservation'.$reservationId,
            'Attachment' => [
                [
                    'attachmentname' => $pdfName,
                    'attachmentdata' => '',
                ],
            ],
        ];
    }

    public function setHeader(array $header)
    {
        $this->header = $header;
    }

    public function setBody(array $body)
    {
        $this->body = $body;
    }

    public function setAttachmentStr(string $attachStr)
    {
        $this->attachStr = $attachStr;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getResponseStatus()
    {
        try {
            $decoded = json_decode($this->response, true);

            return $decoded['result'];
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getResponseIdxcnt()
    {
        try {
            $decoded = json_decode($this->response, true);

            return $decoded['idxcnt'];
        } catch (\Exception $e) {
            return null;
        }
    }

    public function send()
    {
        if (!empty($this->attachStr)) { // 添付ファイル追加
            $this->body['Attachment'][0]['attachmentdata'] = $this->attachStr;
        }

        return $this->_guzzle();
    }

    private function _guzzle()
    {
        $client = new Client();
        try {
            $response = $client->request('post', self::URL_FAXIMO_SILVER, [
                'headers' => $this->header,
                'json' => $this->body,
            ]);

            $status = $response->getStatusCode();

            $this->response = (string) $response->getBody();

            $body = json_decode($this->response, true);
            if ($body['result'] != "000000") {
                throw new \Exception($body['result']);
            }

            return true;
        } catch (BadResponseException $e) {
            CommonLog::notifyToChat(
                'Faximo APIでBadResponseException発生',
                $e->getMessage()
            );
        } catch (GuzzleException $e) {
            CommonLog::notifyToChat(
                'Faximo APIでGuzzleException発生',
                $e->getMessage()
            );
        } catch (\Exception $e) {
            CommonLog::notifyToChat(
                'Faximo APIでException発生',
                $e->getMessage()
            );
        }

        return false;
    }
}
