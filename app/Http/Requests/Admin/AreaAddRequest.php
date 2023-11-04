<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Admin\AreaEditRequest;
use App\Rules\AlphaNumDashUscore;
use App\Http\Controllers\Admin\AreaController;
use Illuminate\Validation\Rule;

class AreaAddRequest extends FormRequest
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
        // エリアID取得(コードを見たときにわかりやすいように取得しているだけ)
        $area_id = $this->id;

        return [
            'big_area' => 'nullable|string',
            'middle_area' => 'nullable|string',
            'name' => 'required|string',
            'area_cd' => [
                'required',
                'string',
                new AlphaNumDashUscore,
                // areasテーブルのarea_cdでのユニーク制約
                Rule::unique('areas')
                    ->ignore($area_id),
            ],
            'weight' => 'nullable|numeric|between:0,9999.99',
            'sort' => 'nullable|numeric',
        ];
    }

    /**
     * バリデーションエラーのカスタム属性の取得
     *
     * @return array
     */
    public function attributes()
    {
        return (new AreaEditRequest)->attributes();
    }
}
