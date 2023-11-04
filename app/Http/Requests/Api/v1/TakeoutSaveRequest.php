<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class TakeoutSaveRequest extends FormRequest
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
            'customer.firstName' => 'string|required',
            'customer.lastName' => 'string|required',
            'customer.email' => 'string|required',
            'customer.tel' => 'string|required',
            'customer.request' => 'string|nullable',
            'application.menus' => 'required',
            'application.menus.*.menu.id' => 'integer|required',
            'application.menus.*.menu.count' => 'integer|required|max:10',
            'application.menus.*.options.*.id' => 'integer|nullable',
            'application.menus.*.options.*.keywordId' => 'integer|nullable',
            'application.menus.*.options.*.contentsId' => 'integer|nullable',
            'application.pickUpDate' => 'string|required',
            'application.pickUpTime' => 'string|required',
            'payment.returnUrl' => 'string|required',
        ];
    }
}
