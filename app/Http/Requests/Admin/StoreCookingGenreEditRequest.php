<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCookingGenreEditRequest extends FormRequest
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
            'cooking_middle_genre.*' => 'required|string',
            'cooking_small_genre.*' => 'required|string',
            'cooking_small2_genre.*' => 'nullable|string',
            'cooking_genre_group_id.*' => 'nullable|integer',
            'cooking_delegate.*' => 'nullable|string',
        ];
    }
}
