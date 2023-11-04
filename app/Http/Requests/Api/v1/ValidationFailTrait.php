<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

trait ValidationFailTrait
{

    /**
     * @todo 共通化予定
     * [override] バリデーション失敗時ハンドリング.
     *
     * @throw HttpResponseException
     *
     * @see FormRequest::failedValidation()
     */
    protected function failedValidation(Validator $validator)
    {
        $response['errors'] = $validator->errors()->toArray();

        // JSON_UNESCAPED_UNICODEを第4引数に指定することで、
        // ブラウザ上でAPIを叩いた時の日本語エラーメッセージのエスケープを回避（文字化け回避）
        throw new HttpResponseException(
            response()->json($response, 422, [], JSON_UNESCAPED_UNICODE,)
        );
    }

    /**
     * バリデーションエラーメッセージ
     * 
     * 個別にエラーメッセージ設定したい場合は、各リクエストフォームでオーバーライドしてください。
     * 
     * @return array
     */
    public function messages()
    {
        // 各種カスタムバリデーションメッセージを変数に代入
        $dateFormatYMD = ':attributeは「YYYY-MM-DD」の形式で入力してください。';
        $dateFormatHI = ':attributeは「HH:MM」の形式で入力してください。';
        $arrayRequired = ':attributeは必ず指定してください。';

        return [
            'reservationDate.date_format' => $dateFormatYMD,
            'visitDate.date_format' => $dateFormatYMD,
            'pickUpDate.date_format' => $dateFormatYMD,
            'pickUpTime.date_format' => $dateFormatHI,
            'application.menus.*.menu.id.required' => $arrayRequired,
            'application.menus.*.menu.count.required' => $arrayRequired,
        ];
    }

    /**
     * バリデーションエラー文言
     * 
     * 個別に文言を設定したい場合は、各リクエストフォームでオーバーライドしてください。
     * 
     * @return array
     */
    public function attributes()
    {
        return [
            'reservationDate' => 'reservationDate',
            'reservationId' => 'reservationId',
            'reservationNo' => 'reservationNo',
            'visitDate' => 'visitDate',
            'visitTime' => 'visitTime',
            'visitPeople' => 'visitPeople',
            'loginId' => 'loginId',
            'menuId' => 'menuId',
            'menuIds' => 'menuIds',
            'evaluationCd' => 'evaluationCd',
            'isRealName' => 'isRealName',
            'userName' => 'userName',
            'isRemember' => 'isRemember',
            'rememberToken' => 'rememberToken',
            'pickUpDate' => 'pickUpDate',
            'pickUpTime' => 'pickUpTime',
            'cookingGenreCd' => 'cookingGenreCd',
            'menuGenreCd' => 'menuGenreCd',
            'suggestCd' => 'suggestCd',
            'suggestText' => 'suggestText',
            'appCd' => 'appCd',
            'lowerPrice' => 'lowerPrice',
            'upperPrice' => 'upperPrice',
            'sessionToken' => 'sessionToken',
            'cd3secResFlg' => 'cd3secResFlg',
            'customer.firstName' => 'customer.firstName',
            'customer.lastName' => 'customer.lastName',
            'customer.email' => 'customer.email',
            'customer.tel' => 'customer.tel',
            'customer.request' => 'customer.request',
            'application.menus.*.menu.id' => 'application.menus.*.menu.id',
            'application.menus.*.menu.count' => 'application.menus.*.menu.count',
            'application.menus.*.options.*.id' => 'application.menus.*.options.*.id',
            'application.menus.*.options.*.keywordId' => 'application.menus.*.options.*.keywordId',
            'application.menus.*.options.*.contentsId' => 'application.menus.*.options.*.contentsId',
            'application.pickUpDate' => 'application.pickUpDate',
            'application.pickUpTime' => 'application.pickUpTime',
            'payment.returnUrl' => 'payment.returnUrl',
        ];
    }
}