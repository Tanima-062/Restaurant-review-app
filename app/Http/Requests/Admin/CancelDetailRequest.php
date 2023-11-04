<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CancelDetailRequest extends FormRequest
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
            'reservation_id' => 'required|integer',
            'account_code' => 'required|alpha_dash',
            'price' => 'required|integer',
            'count' => 'required|integer|max:100',
            'remarks' => 'max:100',
        ];
    }
}
