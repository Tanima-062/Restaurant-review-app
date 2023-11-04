<?php

namespace App\Http\Requests\Api\v1;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class RestaurantChangeRequest extends FormRequest
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
            'request' => 'string|nullable',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $now = Carbon::now();
            $reservation = Reservation::find($this->input('reservationId'));
            $visitDate = new Carbon($reservation->pick_up_datetime);
            $changeLimit = $visitDate->hour(23)->minute(59)->subDay();
            if (strtotime($now->format('Y-m-d H:i')) > strtotime($changeLimit->format('Y-m-d H:i'))) {
                $validator->errors()->add('reservationId', '変更期限が過ぎているため変更できません。');
            }
        });
    }
}
