<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\GenreGroup;
use App\Models\Genre;
use App\Http\Controllers\Admin\StoreGenreController;

class StoreCookingGenreAddRequest extends FormRequest
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
            'is_delegate' => 'required|numeric'
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
            // ジャンルステータスにメインが選択された場合
            if ($this->input('is_delegate') == 1) {

                // ジャンル(小)2が入力されている場合
                if (!is_null($this->input('small2_genre'))) {
                    $validator->errors()->add('is_delegate', 'ジャンル(小)2が設定されている場合はメインジャンルに設定できません。');
                }

                // 店舗に結びついたジャンル情報を取得
                $genreGroups = GenreGroup::where('store_id', $this->id)->get();
                $count = 0;
                foreach ($genreGroups as $genreGroup) {
                    $count += $genreGroup->is_delegate === 1 ? 1 : 0;
                }

                // メインジャンルがすでに1つある場合
                if ($count >= 1) {
                    $validator->errors()->add('is_delegate', 'メインジャンルは2つ以上設定できません。');
                }
            }

            // 重複登録バリデーション
            $appCd = $this->input('app_cd', '');
            $middleGenre = $this->input('middle_genre');
            $smallGenre = $this->input('small_genre');
            $small2Genre = $this->input('small2_genre', '');
            $isDelegate = $this->input('is_delegate');
            $cooking = config('const.genre.bigGenre.b-cooking.key');

            if (empty($small2Genre)) {
                $path = sprintf('/%s/%s', $cooking, $middleGenre);
                $genres = Genre::getGenreMenu($path, $appCd, $smallGenre)->get();
            } else {
                $path = sprintf('/%s/%s/%s', $cooking, $middleGenre, $smallGenre);
                $genres = Genre::getGenreMenu($path, $appCd, $small2Genre )->get();
            }

            $genre = $genres->pop();

            $genreGroup = GenreGroup::firstOrNew([
                'genre_id' => $genre->id,
                'store_id' => $this->id,
                'is_delegate' => $isDelegate,
            ]);

            // 店舗に結びついたジャンル情報を取得
            $genreGroups = GenreGroup::where('store_id', $this->id)->get();
            $arrayGenreIds = array_column($genreGroups->toArray(), 'genre_id');
            $arrayGenreIds[] = $genreGroup->genre_id;
            $storeGenreController = new StoreGenreController();
            // 重複している場合
            if ($storeGenreController->checkSimilar($arrayGenreIds)) {
                $validator->errors()->add('multiple', '料理ジャンルは重複して登録することはできません。');
            }
        });
    }
}
