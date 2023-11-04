<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Menu;
use App\Models\Reservation;

class menuVacancyRequest extends FormRequest
{
    use ValidationFailTrait;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'visitDate' => 'date_format:Y-m-d|required',
            'menuId' => 'integer|required_without:reservationId',
            'reservationId' => 'integer|required_without:menuId',
        ];
    }

    /**
     * バリデータインスタンスの設定
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // メニュー情報取得
            $menuId = isset($this->menuId) ? $this->menuId : NULL;

            // 予約情報取得
            $reservationId = isset($this->reservationId) ? $this->reservationId : NULL;

            // menuIdがNULLではない場合
            if (!is_null($menuId)) {
                $menu = Menu::find($menuId);
                // DBにメニュー情報がない場合
                if (is_null($menu)) {
                    $validator->errors()->add('menuId', 'メニューが存在しません。');
                }
            }

            // reservationIdがNULLではない場合
            if (!is_null($reservationId)) {
                $reservation = Reservation::find($reservationId);
                // DBに予約情報がない場合
                if (is_null($reservation)) {
                    $validator->errors()->add('reservationId', '予約が存在しません。');
                }
            }
        });
    }
}
