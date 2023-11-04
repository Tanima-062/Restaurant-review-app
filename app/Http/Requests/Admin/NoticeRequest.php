<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class NoticeRequest extends FormRequest
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
            'title' => 'required|string|max:128',
            'message' => 'required|string',
            'published_at' => 'required|date',
            'datetime_from' => 'required|date',
            'datetime_to' => 'required|date|after_or_equal:datetime_from',
        ];
    }

    public function attributes()
    {
        return [
            'title' => 'タイトル',
            'message' => '本文',
            'published_at' => '公開日時',
            'datetime_from' => '掲載開始日時',
            'datetime_to' => '掲載終了日時',
        ];
    }
}
