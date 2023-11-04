<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
    ];

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
     * お気に入り登録
     * 重複のIDを指定されても重複を削除し成功とします.
     *
     * @param int userId
     * @param int id
     * @param string appCd
     *
     * @return bool true:成功 false:失敗(内部エラーではなく上限制限による失敗)
     *
     * @throws Exception
     */
    public function registerFavorite(int $userId, int $id, string $appCd, &$msg)
    {
        try {
            $msg = '';
            $appCd = strtoupper($appCd) === key(config('code.appCd.to')) ? key(config('code.appCd.to')) : key(config('code.appCd.rs'));
            $favorite = Favorite::where('user_id', $userId)->where('app_cd', $appCd)->first();
            if (!empty($favorite) && $this->isLimit($favorite, $limit)) {
                $msg = \Lang::get('message.favoriteLimit');

                return false;
            }
            if (is_null($favorite)) {
                $favorite = new Favorite();
                $favorite->list = json_encode([(object) ['id' => $id]]);
                $favorite->app_cd = $appCd;
                $favorite->user_id = $userId;
                $favorite->save();
            } else {
                $list = json_decode($favorite->list, true);
                $list[] = ['id' => $id];
                $arrTmp = $arrResult = [];
                foreach ($list as $value) {
                    if (!in_array($value['id'], $arrTmp)) {
                        $arrTmp[] = $value['id'];
                        $arrResult[] = $value;
                    }
                }
                $favorite->list = json_encode($arrResult);
                $favorite->save();
            }

            return true;
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * お気に入り削除.
     *
     * @param int userId
     * @param int id
     * @param string appCd
     *
     * @return bool true:成功 false:失敗
     */
    public function deleteFavorite(int $userId, int $id, string $appCd)
    {
        try {
            $appCd = strtoupper($appCd) === key(config('code.appCd.to')) ? key(config('code.appCd.to')) : key(config('code.appCd.rs'));

            $favorite = Favorite::where('user_id', $userId)->where('app_cd', $appCd)->first();

            if (!is_null($favorite)) {
                $list = json_decode($favorite->list, true);
                $arrResult = [];
                foreach ($list as $value) {
                    if ($value['id'] !== $id) {
                        $arrResult[] = $value;
                    }
                }
                $favorite->list = json_encode($arrResult);
                $favorite->save();
            }

            return true;
        } catch (\Throwable $e) {
            \Log::error(
            sprintf('::params::userId=%d, id=%d, appCd=%s, error=%s', $userId, $id, $appCd, $e->getMessage()));

            return false;
        }
    }

    /**
     * お気に入りID取得.
     *
     * @param int userId
     * @param string appCd
     *
     * @return array
     *
     * @throws Illuminate\Database\QueryException
     */
    public function getFavoriteIds(int $userId, string $appCd)
    {
        $ids = [];
        $favorite = Favorite::where('user_id', $userId)->where('app_cd', $appCd)->first();
        if (!is_null($favorite)) {
            $ids = array_column(json_decode($favorite->list, true), 'id');
        }

        return $ids;
    }

    /**
     * お気に入り上限チェック.
     *
     * @param Favorite favorite
     * @param int limit
     *
     * @return bool true:上限 false:上限ではない
     */
    public function isLimit(Favorite $favorite, &$limit)
    {
        $result = false;

        $ids = array_column(json_decode($favorite->list, true), 'id');
        $limit = ($favorite->app_cd === key(config('code.appCd.to'))) ? config('takeout.favorite.limit') : config('restaurant.favorite.limit');
        if (count($ids) >= $limit) {
            $result = true;
        }

        return $result;
    }
}
