<?php

namespace App\Http\Requests\Admin;

use App\Models\GenreGroup;
use App\Models\Image;
use App\Models\Menu;
use App\Models\Price;
use Illuminate\Foundation\Http\FormRequest;

class MenuPublishRequest extends FormRequest
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
            'published' => 'required|integer|between:1,1',
        ];
    }

    /**
     * バリデータインスタンスの設定.
     *
     * @param \Illuminate\Validation\Validator $validator
     *
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // エラーメッセージ格納用
            $errors = [];

            // メニュー情報の取得
            $menu =  Menu::find($this->id);

            // メニューのジャンル取得
            $menuGenre = GenreGroup::where('menu_id', $menu->id)->first();
            // 設定ジャンルが存在しない場合
            if (!$menuGenre) {
                $errors[] = 'メニューのジャンルを設定してください。';
            }

            // メニューの画像の取得
            $menuImage = Image::menuId($this->id)->first();
            // 設定画像が存在しない場合
            if (!$menuImage) {
                $errors[] = 'メニューの画像を設定してください。';
            }

            //　メニューの料金の取得
            $menuPrice = Price::menuId($this->id)->first();
            // 設定料金が存在しない場合
            if (!$menuPrice) {
                $errors[] = 'メニューの料金を設定してください。';
            }

            // エラーがあった場合にエラーメッセージを表示する
            if ($errors) {
                foreach ($errors as $error) {
                    $validator->errors()->add('published', 'メニュー「'.$menu->name.'」を公開するには下記の設定をしてください。');
                    $validator->errors()->add('published', $error);
                }
            }
        });
    }
}
