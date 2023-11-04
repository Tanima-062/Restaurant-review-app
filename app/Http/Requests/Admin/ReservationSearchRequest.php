<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReservationSearchRequest extends FormRequest
{
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
            'id' => 'nullable|string|min:1',
            'last_name' => 'nullable|string|min:1',
            'first_name' => 'nullable|string|min:1',
            'email' => 'nullable|email|min:1',
            'tel' => 'nullable|numeric|min:1',
            'reservation_status' => 'nullable|string|min:1',
            'payment_status' => 'nullable|string|min:1',
            'created_at_from' => 'nullable|date',
            'created_at_to' => 'nullable|date|after_or_equal:created_at_from',
            'pick_up_datetime_from' => 'nullable|date',
            'pick_up_datetime_to' => 'nullable|date|after_or_equal:pick_up_datetime_from',
            'store_name' => 'nullable|string|min:1',
            'store_tel' => 'nullable|regex:/^(0{1}\d{1,4}-{0,1}\d{1,4}-{0,1}\d{4})$/|min:1',
        ];
    }

    public function attributes()
    {
        return [
            'id' => 'id',
            'last_name' => '姓',
            'first_name' => '名',
            'email' => 'メールアドレス',
            'tel' => '電話番号',
            'reservation_status' => '予約ステータス',
            'payment_status' => '入金ステータス',
            'created_at_from' => '申込日時from',
            'created_at_to' => '申込日時to',
            'pick_up_datetime_from' => '来店日時from',
            'pick_up_datetime_to' => '来店日時to',
            'store_name' => '店舗名',
            'store_tel' => '店舗電話番号',
        ];
    }
}
