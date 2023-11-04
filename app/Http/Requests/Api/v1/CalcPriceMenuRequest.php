<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class CalcPriceMenuRequest extends FormRequest
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
            'visitDate' => 'date_format:Y-m-d|nullable',
            'visitTime' => 'date_format:H:i|nullable',
            'persons' => 'integer|nullable',
            'reservationId' => 'integer|required',
        ];
    }
}