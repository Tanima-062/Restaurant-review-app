<?php

namespace App\Http\Requests\Admin;

use App\Models\Reservation;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreVacancyEditCommitRequest extends FormRequest
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
            'interval.*.base_stock' => 'required|integer',
            'interval.*.is_stop_sale' => 'required',
        ];
    }

    public function attributes()
    {
        $return = [];

        $posts = request()->all();

        foreach ($posts['interval'] as $key => $val) {
            $return['interval.'.$key.'.base_stock'] = $key.'の在庫数';
            $return['interval.'.$key.'.is_stop_sale'] = $key.'の有効/無効';
        }

        return $return;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = request()->all();
            $store = Store::find($this->route('id'));
            $id = $store->id;

            // 座席数0の時は登録できない
            if (empty($store->number_of_seats) || $store->number_of_seats <= 0) {
                $validator->errors()->add('number_of_seats', '店舗の座席数が設定されていないか0のため空席を登録できません。');
            }

            // 予約済み数(来店人数)の設定
            foreach ($data['interval'] as $key => $v) {
                $query = Reservation::where('app_cd', config('code.gmServiceCd.rs'));
                $query->where('reservation_status', '!=', config('code.reservationStatus.cancel.key'));
                $datetime = date('Y-m-d ', strtotime($data['date'])).$key;
                $query->where('pick_up_datetime', '>=', $datetime);
                $pickUpDatetimeStart = new Carbon($datetime);
                $pickUpDatetimeEnd = $pickUpDatetimeStart->addMinutes((int) $data['intervalTime']);
                $query->where('pick_up_datetime', '<=', $pickUpDatetimeEnd);

                $query->whereHas('reservationStore', function ($query) use ($id) {
                    $query->where('store_id', $id);
                });
                if ($v['base_stock'] < $query->sum('persons')) {
                    $validator->errors()->add('interval.'.$key.'.base_stock', $key.'の在庫数は予約済み数(来店人数)を下回ることは不可能です。');
                }
            }
        });
    }
}
