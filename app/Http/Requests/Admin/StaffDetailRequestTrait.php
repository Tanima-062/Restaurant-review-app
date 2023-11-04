<?php

namespace App\Http\Requests\Admin;

trait StaffDetailRequestTrait
{
    public function rules()
    {
        return [
            'name' => 'required|min:1|max:64'
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'お名前',
        ];
    }
}
