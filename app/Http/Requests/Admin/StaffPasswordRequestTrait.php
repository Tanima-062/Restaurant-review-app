<?php

namespace App\Http\Requests\Admin;

trait StaffPasswordRequestTrait
{
    public function rules()
    {
        return [
            'val-password2' => 'required|min:6|max:30|'.
                'regex:/\A(?=.*?[a-z])(?=.*?[A-Z])(?=.*?\d)[a-zA-Z\d]+\z/',
            'val-confirm-password2' => 'required|min:6|max:30|same:val-password2',
        ];
    }

    public function attributes()
    {
        return [
            'val-password2' => 'パスワード',
            'val-confirm-password2' => 'パスワード確認',
        ];
    }

    public function messages()
    {
        return [
            'val-password2.regex' => '新しいパスワードに半角英字の大文字が含まれていない、もしくは、記号が含まれています。'
        ];
    }
}
