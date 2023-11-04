<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GenreGroup extends Model
{
    protected $guarded = ['id'];

    public function genre()
    {
        return $this->hasOne('App\Models\Genre', 'id', 'genre_id');
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
    ];

    // store_idから店舗ジャンル(料理ジャンル)を取得
    public function getCookingGenreByStoreId($storeId, $genreType = null)
    {
        $result = [];
        $genreGroups = GenreGroup::where('store_id', $storeId)->get();

        foreach ($genreGroups as $genreGroup) {
            $isDelegate = $genreGroup->is_delegate;
            if ($genreGroup->genre->level === 3) {
                $genre = $genreGroup->genre;
                // mypage予約確認の時以外ははこだわりジャンルは除外する
                if (!empty($genre) && !empty($genreType) && (strpos($genre->path, $genreType)) === false) {
                    continue;
                }
                $genre->isDelegate = $isDelegate;
                $result[] = $genre;
            } elseif ($genreGroup->genre->level === 4) {
                $tmp = explode('/', $genreGroup->genre->path);
                $query = Genre::where('level', 3)->where('genre_cd', $tmp[3])->where('published', 1);
                if (!empty($genreType)) {
                    $query->where('path', 'like', '%'.$genreType.'%');
                }
                $genre = $query->first();
                if (empty($genre)) {
                    continue;
                }
                $genre->isDelegate = $isDelegate;
                $result[] = $genre;
            } else {
                // levelが3,4以外は想定してない
                continue;
            }
        }

        return $result;
    }
}
