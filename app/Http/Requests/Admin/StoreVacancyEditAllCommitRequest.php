<?php

namespace App\Http\Requests\Admin;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;

class StoreVacancyEditAllCommitRequest extends FormRequest
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
            $store = Store::find($this->route('id'));

            // 座席数0の時は登録できない
            if (empty($store->number_of_seats) || $store->number_of_seats <= 0) {
                $validator->errors()->add('number_of_seats', '店舗の座席数が設定されていないか0のため空席を登録できません。');
            }
        });
    }
}
