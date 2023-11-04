<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\AlphaNumDashUscore;
use App\Http\Controllers\Admin\AreaController;
use App\Models\Area;
use Illuminate\Validation\Rule;

class AreaEditRequest extends FormRequest
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

    public function attributes()
    {
        return [
            'big_area' => 'エリア(大)',
            'middle_area' => 'エリア(中)',
            'name' => '名前',
            'area_cd' => 'エリアコード',
            'weight' => '優先度',
            'sort' => 'ソート順',
        ];
    }

    /**
     * バリデータインスタンスの設定
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            // エリアコントローラのインスタンス生成
            $areaController = new AreaController;

            // 入力データ取得
            $data = $this->input();

            // 入力データ整形
            $data['big_area'] === 'none' ? $data['big_area'] = null : '';

            // publishedの設定
            // $data['published'] = (!empty($data['published'])) ? 1 : 0;

            // path設定
            $path = $areaController->makePath($data);

            // 編集前のpath設定
            $oldPathAreaCd = $areaController->makeOldPath($data);

            // エリア情報取得
            $area = Area::find($this->id);

            // フォーム入力内容からlevelを設定
            $area->level = $areaController->setLevel($data, $path);

            // 編集後のlevelと編集前のlevelが違った場合、編集前の「path/エリアコード」が他のエリアで使用されているか判定
            if ($data['old_area_level'] != $area->level && Area::where('path', $oldPathAreaCd)->exists()) {
                $validator->errors()->add('area_cd', sprintf('エリア「%s」を更新出来ませんでした。すでに別のエリアでPATH「%s」を使用しています。', $data['name'], $oldPathAreaCd));
            }
        });
    }
}
