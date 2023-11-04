<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class NoticeSearchRequest extends FormRequest
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
            'datetime_from' => 'nullable|date',
            'datetime_to' => 'nullable|date|after_or_equal:datetime_from',
            'updated_by' => 'nullable|integer|min:1|exists:staff,id,staff_authority_id,1',
        ];
    }

    public function attributes()
    {
        return [
            'datetime_from' => '掲載開始日時',
            'datetime_to' => '掲載終了日時',
            'updated_by' => '更新者',
        ];
    }
}
