<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoryAddRequest extends FormRequest
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
            'image' => 'required|mimes:jpeg,jpg,png,gif|max:8192',
            'title' => 'required|max:100',
            'url' => 'required|url|max:255',
            'app_cd' => 'required',
        ];
    }

    public function attributes()
    {
        return (new StoryEditRequest())->attributes();
    }
}
