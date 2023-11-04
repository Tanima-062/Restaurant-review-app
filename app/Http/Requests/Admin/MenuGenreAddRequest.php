<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MenuGenreAddRequest extends FormRequest
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
        // 内部的なものなのでattributesは設定しない
        return [
            'middle_genre' => 'required|string',
            'small_genre' => 'required|string',
            'small2_genre' => 'nullable|string',
            'app_cd' => 'required|string',
        ];
    }
}
