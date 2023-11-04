<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class DishUpLoginRequest extends FormRequest
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
            'userName' => 'required_without:rememberToken|string',
            'password' => 'required_without:rememberToken|string',
            'isRemember' => 'nullable|boolean',
            'rememberToken' => 'required_without:userName|required_without:password|string',
        ];
    }
}
