<?php

namespace App\Http\Requests\Admin;

use App\Rules\GenrePrefix;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenreRequest extends FormRequest
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
        $const = config('const.genre.bigGenre');

        return [
            'big_genre' => ['required', Rule::in(array_keys($const))],
            'middle_genre' => 'nullable|string',
            'small_genre' => 'nullable|string',
            'app_cd' => 'required|string',
            'name' => 'required|string',
            'genre_cd' => ['required','string','alpha_dash', new GenrePrefix($this->request->all())],
        ];
    }

    public function attributes()
    {
        return [
            'big_genre' => 'ジャンル(大)',
            'middle_genre' => 'ジャンル(中)',
            'small_genre' => 'ジャンル(小)',
            'app_cd' => '利用サービス',
            'name' => 'カテゴリ名',
            'genre_cd' => 'カテゴリコード',
        ];
    }
}
