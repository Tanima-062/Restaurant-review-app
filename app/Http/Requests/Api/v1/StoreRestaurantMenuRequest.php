<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class StoreRestaurantMenuRequest extends FormRequest
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
            'visitTime' => 'date_format:H:i|required_if:dateUndecided,false',
            'visitPeople' => 'numeric|required_if:dateUndecided,false',
            'dateUndecided' => 'string|required'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->input('dateUndecided') != 'false') {
                if ($this->input('dateUndecided') != 'true') {
                    $validator->errors()->add('dateUndecided', 'dateUndecededには、trueかfalseを入力して下さい。');
                }
            }
        });
    }
}
