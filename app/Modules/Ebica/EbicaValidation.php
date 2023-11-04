<?php

namespace App\Modules\Ebica;

class EbicaValidation {

    public $errorMsg;           //バリデーションエラー用

    /**
     * 予約登録用のバリデーション
     *
     * @param array $params
     *
     * @return boolean
     */
    public function validateReserve(array &$params)
    {
        if (empty($params['shop_id']) || !is_int($params['shop_id'])) {
            $this->errorMsg = 'validation error(shop_id)';
            return false;
        }

        if (empty($params['date'])
            || !is_string($params['date'])
            || !preg_match("/^[0-9]{4}\-(0[1-9]|1[0-2])\-(0[1-9]|[12][0-9]|3[01])$/",$params['date'])) {
            $this->errorMsg = 'validation error(date)';
            return false;
        }

        if (empty($params['first_name'])
            || !is_string($params['first_name'])
            || !preg_match("/^[a-zA-Z]*$/",$params['first_name'])) {
            $this->errorMsg = 'validation error(first_name)';
            return false;
        }

        if (empty($params['last_name'])
            || !is_string($params['last_name'])
            || !preg_match("/^[a-zA-Z]*$/",$params['last_name'])) {
            $this->errorMsg = 'validation error(last_name)';
            return false;
        }

        // if (empty($params['headcount'])
        //     || !is_int($params['headcount'])
        //     || $params['headcount'] <= 0) {
        //     $this->errorMsg = 'validation error(headcount)';
        //     return false;
        // }

        //電話番号のチェック 携帯番号だけ通す時使う
        // if (empty($params['phone_number'])
        //     || !is_string($params['phone_number'])
        //     || !preg_match("/^0[7-9]0[0-9]{8}$/",$params['phone_number'])) {
        //     $this->errorMsg = 'validation error(phone_number)';
        //     return false;
        // }

        if (empty($params['time'])
            || !is_string($params['time'])
            || !preg_match("/^([01][0-9]|2[0-3]):[0-5][0-9]$/",$params['time'])) {
            $this->errorMsg = 'validation error(time)';
            return false;
        }

        if (empty($params['email']) || !is_string($params['email'])) {
            $this->errorMsg = 'validation error(email)';
            return false;
        }

        if (empty($params['course_name']) || !is_string($params['course_name'])) {
            $this->errorMsg = 'validation error(course_name)';
            return false;
        }

        // if (empty($params['course_count']) || !is_int($params['course_count'])) {
        //     $this->errorMsg = 'validation error(course_count)';
        //     return false;
        // }

        if (empty($params['prepaid']) || !is_string($params['prepaid'])) {
            $this->errorMsg = 'validation error(prepaid)';
            return false;
        }

        if (empty($params['option'])) {
            $params['option'] = 'なし';
        }

        if (empty($params['remarks'])) {
            $params['remarks'] = 'なし';
        }

        return true;
    }

    /**
     * 予約変更用のバリデーション
     * チェックOKであればbodyの配列を返す
     * チェックNGであればfalseを返す
     *
     * @param array $params
     *
     * @return array|boolean
     */
    public function validateChangeReserve(array $params)
    {
        $body = [];
        if (empty($params['shop_id']) || !is_int($params['shop_id'])) {
            $this->errorMsg = 'validation error(shop_id)';
            return false;
        }

        if (empty($params['reservation_id']) || !is_int($params['reservation_id'])) {
            $this->errorMsg = 'validation error(reservation_id)';
            return false;
        }

        if (empty($params['status'])) {
            $this->errorMsg = 'validation error(status)';
            return false;

        } elseif ($params['status'] === 'change') {
            $body['status'] = $params['status'];

        } elseif ($params['status'] === 'cancel') {
            $body['status'] = $params['status'];

        } else {
            $this->errorMsg = 'validation error(status)';
            return false;
        }

        //timeとdateセットじゃないとリクエストを送ることができない為、
        //ここでチェックして足りなければfalseを返す
        if (!empty($params['date'])
            || !empty($params['time'])) {

            if (empty($params['date'])
                || !is_string($params['date'])
                || !preg_match("/^[0-9]{4}\-(0[1-9]|1[0-2])\-(0[1-9]|[12][0-9]|3[01])$/",$params['date'])) {
                $this->errorMsg = 'validation error(date)';
                return false;
            }

            $body['date'] = $params['date'];

            if (empty($params['time'])
                || !is_string($params['time'])
                || !preg_match("/^([01][0-9]|2[0-3]):[0-5][0-9]$/",$params['time'])) {
                $this->errorMsg = 'validation error(time)';
                return false;
            }

            $body['time'] = $params['time'];

        }

        if (isset($params['headcount'])) {
            if (!is_int($params['headcount'])
                || $params['headcount'] <= 0) {
                $this->errorMsg = 'validation error(headcount)';
                return false;
            }

            $body['headcount'] = $params['headcount'];
        }

        if (isset($params['force'])) {
            if (!is_bool($params['force'])) {
                $this->errorMsg = 'validation error(force)';
                return false;
            }
            $body['force'] = $params['force'];
        }

        //備考欄の項目を変更する際は全て書き換えないといけない為、
        //course_name,course_count,prepaidは必須
        if (!empty($params['course_name'])
            || !empty($params['course_count'])
            || !empty($params['prepaid'])
            || !empty($params['option'])
            || !empty($params['remarks'])) {

            if (empty($params['course_name']) || !is_string($params['course_name'])) {
                $this->errorMsg = 'validation error(course_name)';
                return false;
            }

            if (empty($params['course_count']) || !is_int($params['course_count'])) {
                $this->errorMsg = 'validation error(course_count)';
                return false;
            }

            if (empty($params['prepaid']) || !is_string($params['prepaid'])) {
                $this->errorMsg = 'validation error(prepaid)';
                return false;
            }

            if (empty($params['option'])) {
                $params['option'] = 'なし';
            }

            if (empty($params['remarks'])) {
                $params['remarks'] = 'なし';
            }

            $body['note'] = "コース名(数量) : {$params['course_name']}({$params['course_count']})\n追加オプション : {$params['option']}\n事前決済 : {$params['prepaid']}\n備考 : {$params['remarks']}";
        }

        return $body;
    }

    /**
     * 予約検索用のバリデーション
     * チェックOKであればqueryの配列を返す
     * チェックNGであればfalseを返す
     *
     * @param array $params
     *
     * @return array|boolean
     */
    public function validateSearch(array $params)
    {
        $query = [];
        if (empty($params['shop_id']) || !is_int($params['shop_id'])) {
            $this->errorMsg = 'validation error(shop_id)';
            return false;
        }

        if (isset($params['limit'])) {
            if (!is_int($params['limit'])
                || $params['limit'] <= 0) {
                $this->errorMsg = 'validation error(limit)';
                return false;
            }

            $query['limit'] = $params['limit'];
        }

        if (isset($params['next_val'])) {
            if (!is_int($params['next_val'])) {
                $this->errorMsg = 'validation error(next_val)';
                return false;
            }

            $query['next_val'] = $params['next_val'];
        }

        //電話番号のチェック 携帯番号だけ通す時使う
        // if (!empty($params['phone_number'])) {
        //     if (!is_string($params['phone_number'])
        //     || !preg_match("/^0[7-9]0[0-9]{8}$/",$params['phone_number'])) {
        //         $this->errorMsg = 'validation error(phone_number)';
        //         return false;
        //     }

        //     $query['phone_number'] = $params['phone_number'];
        // }

        if (empty($params['since'])
                || !is_string($params['since'])
                || !preg_match("/^[0-9]{4}\-(0[1-9]|1[0-2])\-(0[1-9]|[12][0-9]|3[01])$/",$params['since'])) {
                $this->errorMsg = 'validation error(since)';
                return false;
        }

        if (!empty($params['until'])) {
            if (!is_string($params['until'])
            || !preg_match("/^[0-9]{4}\-(0[1-9]|1[0-2])\-(0[1-9]|[12][0-9]|3[01])$/",$params['until'])) {
                $this->errorMsg = 'validation error(until)';
                return false;
            }

            $query['until'] = $params['until'];
        }

        return $query;
    }
}

