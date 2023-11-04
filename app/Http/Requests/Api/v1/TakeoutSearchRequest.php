<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class TakeoutSearchRequest extends FormRequest
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
            'cookingGenreCd' => 'string|nullable',
            'menuGenreCd' => 'string|nullable',
            'suggestCd' => 'string|nullable',
            'suggestText' => 'string|nullable',
            'pickUpDate' => 'date_format:Y-m-d|nullable',
            'pickUpTime' => 'date_format:H:i|nullable',
            'page' => 'integer|nullable',
        ];
    }
}
