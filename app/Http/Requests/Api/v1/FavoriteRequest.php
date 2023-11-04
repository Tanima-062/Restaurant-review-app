<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FavoriteRequest extends FormRequest
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
            'pickUpDate' => ['nullable', Rule::requiredIf(!filter_var(request()->input('dateUndecided'), FILTER_VALIDATE_BOOLEAN)), 'date_format:Y-m-d'],
            'pickUpTime' => ['nullable', Rule::requiredIf(!filter_var(request()->input('dateUndecided'), FILTER_VALIDATE_BOOLEAN)), 'date_format:H:i'],
            'menuIds' => 'nullable',
        ];
    }
}
