<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettlementAggregateRequest extends FormRequest
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
            'monthOne' => 'nullable|integer',
            'monthTwo' => 'nullable|integer',
            'termYear' => 'nullable|integer',
            'termMonth' => 'nullable|integer',
            'settlementCompanyId' => 'nullable|integer',
        ];
    }
}
