<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class AuthReviewRequest extends FormRequest
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
            'reservationNo' => 'required|string',
            'menuId' => 'required|int',
            'evaluationCd' => 'required|string',
            'body' => 'nullable|string',
            'isRealName' => 'nullable|boolean',
        ];
    }
}
