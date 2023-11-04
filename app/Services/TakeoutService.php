<?php

namespace App\Services;

use App\Libs\Cipher;
use App\Libs\Mail\TakeoutMail;
use App\Models\Area;
use App\Models\Favorite;
use App\Models\Genre;
use App\Models\Maintenance;
use App\Models\Menu;
use App\Models\OrderInterval;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\Review;
use App\Models\Stock;
use App\Models\Store;
use App\Models\Story;
use App\Modules\UserLogin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redis;

class TakeoutService
{
    public function __construct(
        Menu $menu,
        Genre $genre,
        Story $story
    ) {
        $this->menu = $menu;
        $this->genre = $genre;
        $this->story = $story;
    }

    /**
     * テイクアウト-メニュー一覧取得.
     *
     * @param array params 検索パラメータ配列
     *
     * @return array
     */
    public function getRecommendation($params)
    {
        $res = [];
        $list = $this->menu->getRecommendation($params);

        if (!$list || $list->count() == 0) {
            return $res;
        }

        foreach ($list->toArray() as $key => $menu) {
            $res['result'][$key]['id'] = $menu['id'];
            $res['result'][$key]['name'] = $menu['name'];
            $res['result'][$key]['description'] = $menu['description'];

            // 画像がないときに画面側でエラーになりづらいように
            $res['result'][$key]['thumbImage'] = [
                'id' => (!empty($menu['image']['id'])) ? $menu['image']['id'] : 0,
                'imageCd' => (!empty($menu['image']['image_cd'])) ? $menu['image']['image_cd'] : '',
                'imageUrl' => (!empty($menu['image']['url'])) ? $menu['image']['url'] : '',
            ];

            if (isset($menu['menuPrice'])) {
                $res['result'][$key]['price']['id'] = $menu['menuPrice']['id'];
                $res['result'][$key]['price']['priceCd'] = $menu['menuPrice']['price_cd'];
                $res['result'][$key]['price']['price'] = $menu['menuPrice']['price'];
            }

            if (isset($menu['store'])) {
                $res['result'][$key]['store']['id'] = $menu['store']['id'];
                $res['result'][$key]['store']['name'] = $menu['store']['name'];
                //$res['result'][$key]['store']['latitude'] = $menu['store']['latitude'];
                //$res['result'][$key]['store']['longitude'] = $menu['store']['longitude'];
                //$res['result'][$key]['store']['distance'] = 0;
                $res['result'][$key]['store']['access'] = $menu['store']['access'];
                if (isset($menu['store']['genres'])) {
                    $tmpGenre = [];
                    $tmpGenre['id'] = $menu['store']['genres']['id'];
                    $tmpGenre['name'] = $menu['store']['genres']['name'];
                    $tmpGenre['genre_cd'] = $menu['store']['genres']['genre_cd'];
                    $tmpGenre['app_cd'] = $menu['store']['genres']['app_cd'];
                    $tmpGenre['path'] = $menu['store']['genres']['path'];
                    $tmpGenre['level'] = $menu['store']['genres']['level'];
                    $res['result'][$key]['store']['genres'][] = $tmpGenre;
                }
            }

            /*if (isset($menu['store']['stations'])) {
                $res['result'][$key]['store']['station']['id'] = $menu['store']['stations']['id'];
                $res['result'][$key]['store']['station']['name'] = $menu['store']['stations']['name'];
                $res['result'][$key]['store']['station']['latitude'] = $menu['store']['stations']['latitude'];
                $res['result'][$key]['store']['station']['longitude'] = $menu['store']['stations']['longitude'];
                $res['result'][$key]['store']['station']['distance'] = 0;
            }*/
        }

        return $res;
    }

    /**
     * テイクアウト-メニュー一覧取得.
     *
     * @param array params 検索パラメータ配列
     *
     * @return array
     */
    public function search($params)
    {
        //$cacheKey = config('takeout.searchApiCache.prefix').url()->full();
        //$res = json_decode(Redis::get($cacheKey), true);
        //if (!empty($res)) {
        //    return $res;
        //}

        $genres = [];
        $list = $this->menu->search($params, $genres);
        $res = [];
        $res['sumCount'] = $list['count'];
        $res['page'] = empty($params['page']) ? 1 : $params['page'];
        $res['pageMax'] = $list['pageMax'];

        // ジャンル一覧の設定
        $genreList = [];
        foreach ($genres as $recGenre) {
            $tmpGenre = [];

            $tmpGenre['id'] = $recGenre->id;
            $tmpGenre['name'] = $recGenre->name;
            $tmpGenre['genreCd'] = $recGenre->genre_cd;
            $tmpGenre['appCd'] = $recGenre->app_cd;
            $tmpGenre['path'] = $recGenre->path;
            $tmpGenre['level'] = $recGenre->level;
            // 重複防止のためキーを設定
            $genreList[$recGenre->id] = $tmpGenre;
        }
        // idを昇順
        usort($genreList, function ($a, $b) {return $a['id'] > $b['id']; });
        // 重複防止のキーはもう不要
        $res['genres'] = array_values($genreList);

        // メニュー一覧設定
        foreach ($list['list']->toArray() as $key => $menu) {
            $tmp = [];
            $tmp['id'] = $menu['id'];
            $tmp['name'] = $menu['name'];
            $tmp['description'] = $menu['description'];

            // 画像がないときに画面側でエラーになりづらいように
            $tmp['thumbImage'] = [
                'id' => (!empty($menu['image']['id'])) ? $menu['image']['id'] : 0,
                'imageCd' => (!empty($menu['image']['image_cd'])) ? $menu['image']['image_cd'] : '',
                'imageUrl' => (!empty($menu['image']['url'])) ? $menu['image']['url'] : '',
            ];

            if (isset($menu['menuPrice'])) {
                $tmp['price']['id'] = $menu['menuPrice']['id'];
                $tmp['price']['priceCd'] = $menu['menuPrice']['price_cd'];
                $tmp['price']['price'] = $menu['menuPrice']['price'];
            }

            if (isset($menu['store'])) {
                $tmp['store']['id'] = $menu['store']['id'];
                $tmp['store']['name'] = $menu['store']['name'];
                $tmp['store']['latitude'] = $menu['store']['latitude'];
                $tmp['store']['longitude'] = $menu['store']['longitude'];
                $tmp['store']['distance'] = (isset($menu['store']['storeDistance'])) ? $menu['store']['storeDistance'] : 0;
            }

            /*if (isset($menu['store']['stations'])) {
                $tmp['store']['station']['id'] = $menu['store']['stations']['id'];
                $tmp['store']['station']['name'] = $menu['store']['stations']['name'];
                $tmp['store']['station']['latitude'] = $menu['store']['stations']['latitude'];
                $tmp['store']['station']['longitude'] = $menu['store']['stations']['longitude'];
                $tmp['store']['station']['distance'] = (isset($menu['store']['stations']['stationDistance'])) ? $menu['store']['stations']['stationDistance'] : 0;
            }*/
            $res['menus'][] = $tmp;
        }

        //Redis::set($cacheKey, json_encode($res), config('takeout.searchApiCache.expiration'));

        return $res;
    }

    /**
     * 検索条件ファイル出力.
     *
     * @param array params 検索パラメータ配列
     *
     * @return void
     */
    public function logSearchParameters($params)
    {
        $params['pickUpDatetime'] = null;
        if (isset($params['pickUpDate']) && isset($params['pickUpTime'])) {
            $params['pickUpDatetime'] = $params['pickUpDate'].' '.$params['pickUpTime'];
        }

        unset($params['pickUpDate']);
        unset($params['pickUpTime']);
        \Log::channel('searchTakeout')->info(json_encode($params));
    }

    /**
     * メニュー詳細.
     *
     * @param int id メニューid
     * @param string msg チェックメッセージ
     *
     * @return array
     */
    public function detailMenu($id, $params, &$msg)
    {
        $stock = new Stock();
        $orderInterval = new OrderInterval();

        $menuModel = new Menu();

        // 拾う時間指定がない場合は現在時間で絞る
        $params['pickUpTime'] = empty($params['pickUpTime']) ? Carbon::now()->format('H:i') : $params['pickUpTime'];
        // 拾う日付指定がない場合は現在日付で絞る
        $params['pickUpDate'] = empty($params['pickUpDate']) ? Carbon::now()->format('Y-m-d') : $params['pickUpDate'];

        $pickUpDatetime = $params['pickUpDate'].' '.$params['pickUpTime'];
        $menu = $this->menu->detail($id, $params['pickUpDate'])->toArray();

        // opening_hours store menuの曜日と祝日のチェック
        if (!$this->menu->canSale($menu['id'], $menu['store_id'], $pickUpDatetime, $msg)) {
            //$msg = $msg;
        }

        // 在庫チェック
        if (empty($msg) && !$stock->hasStock($params['pickUpDate'], $menu['id'], 1)) {
            $msg = Lang::get('message.stockCheckFailure');
        }

        // 同時間帯注文組数チェック
        $message = null;
        if (empty($msg) && !$orderInterval->isOrderable($params['pickUpDate'], $params['pickUpTime'], $menu['id'], 1, $message)) {
            $msg = (empty($message)) ? Lang::get('message.intervalOrderCheckFailure') : $message;
        }

        //注文日の予約数チェック
        $reservationMenus = ReservationMenu::whereHas('reservation', function ($q) use ($params) {
            $q->where('pick_up_datetime', 'LIKE', "$params[pickUpDate]%");
        })->where('menu_id', $id)->get();

        $reservationStock = 0;
        foreach ($reservationMenus as $reservationMenu) {
            $reservationStock += $reservationMenu->count;
        }

        $res = [];

        if (UserLogin::isLogin()) {
            $user = UserLogin::getLoginUser();
            $ids = (new Favorite())->getFavoriteIds($user['userId'], key(config('code.appCd.to')));
            $res['isFavorite'] = (in_array($menu['id'], $ids)) ? true : false;
        } else {
            $res['isFavorite'] = false;
        }

        $reviews = Review::getCountByEvaluationCd($id)->get();
        $total = $reviews->sum('cnt');
        foreach ($reviews as $review) {
            $res['evaluations'][] = [
                'evaluationCd' => $review->evaluation_cd,
                'percentage' => round(($review->cnt * 100 / $total)),
            ];
        }

        $maintenanceMsg = null;
        Maintenance::isInMaintenance(config('code.maintenances.type.stopSale'), $maintenanceMsg);
        $res['status']['canSale'] = is_null($maintenanceMsg) && empty($msg) ? true : false;
        $res['status']['message'] = (!is_null($maintenanceMsg)) ? $maintenanceMsg : $msg;
        $res['id'] = $menu['id'];
        $res['name'] = $menu['name'];
        $res['description'] = $menu['description'];
        $res['appCd'] = $menu['app_cd'];
        $res['salesLunchStartTime'] = $menu['sales_lunch_start_time'];
        $res['salesLunchEndTime'] = $menu['sales_lunch_end_time'];
        $res['salesDinnerStartTime'] = $menu['sales_dinner_start_time'];
        $res['salesDinnerEndTime'] = $menu['sales_dinner_end_time'];
        $res['stockNumber'] = $menu['stockOne']['stock_number'] - $reservationStock;

        if (isset($menu['image'])) {
            $res['menuImage'] = [
                    'id' => $menu['image']['id'],
                    'imageCd' => $menu['image']['image_cd'],
                    'imageUrl' => $menu['image']['url'],
                ];
        }
        $res['store']['id'] = $menu['store']['id'];
        $res['store']['name'] = $menu['store']['name'];
        $res['store']['latitude'] = $menu['store']['latitude'];
        $res['store']['longitude'] = $menu['store']['longitude'];
        $res['store']['address'] =
            sprintf('%s %s %s',
                (!empty($menu['store']['address_1'])) ? $menu['store']['address_1'] : '',
                (!empty($menu['store']['address_2'])) ? $menu['store']['address_2'] : '',
                (!empty($menu['store']['address_3'])) ? $menu['store']['address_3'] : ''
            );

        if (isset($menu['store']['stations'])) {
            $res['store']['station'] = [
                    'id' => $menu['store']['stations']['id'],
                    'name' => $menu['store']['stations']['name'],
                    'latitude' => $menu['store']['stations']['latitude'],
                    'longitude' => $menu['store']['stations']['longitude'],
                    'distance' => 0,
                ];
        }

        if (isset($menu['store']['genres'])) {
            foreach ($menu['store']['genres'] as $val) {
                $res['store']['storeGenres'][] = [
                    'id' => $val['id'],
                    'name' => $val['name'],
                    'genreCd' => $val['genre_cd'],
                    'appCd' => $val['app_cd'],
                    'path' => $val['path'],
                ];
            }
        }

        if (isset($menu['menuPrice'])) {
            $res['menuPrice'] = [
                    'id' => $menu['menuPrice']['id'],
                    'priceCd' => $menu['menuPrice']['price_cd'],
                    'price' => $menu['menuPrice']['price'],
                ];
        }

        if (isset($menu['options'])) {
            foreach ($menu['options'] as $val) {
                $res['options'][] = [
                    'id' => $val['id'],
                    'optionCd' => $val['option_cd'],
                    'required' => $val['required'],
                    'keywordId' => $val['keyword_id'],
                    'keyword' => $val['keyword'],
                    'contentsId' => $val['contents_id'],
                    'contents' => $val['contents'],
                    'price' => $val['price'],
                ];
            }
        }

        return $res;
    }

    /**
     * ストーリー取得.
     *
     * @return array
     */
    public function getStory($request)
    {
        $list = $this->story->getStory(config('code.gmServiceCd.to'), $request->page);

        $res = [];

        foreach ($list as $key => $story) {
            $res[$key]['id'] = $story->id;
            $res[$key]['title'] = $story->title;
            $res[$key]['appCd'] = $story->app_cd;
            $res[$key]['guideUrl'] = $story->guide_url;

            $image = $story->image;
            if (!is_null($image)) {
                $res[$key]['image']['id'] = $image->id;
                $res[$key]['image']['imageCd'] = $image->image_cd;
                $res[$key]['image']['imageUrl'] = $image->url;
            }
        }

        return $res;
    }

    /**
     * テイクアウト-ジャンル一覧取得.
     *
     * @param string genreCd
     * @param int optionLevel
     *
     * @return array
     */
    public function getTakeoutGenre(string $genreCd = null, int $optionLevel = null)
    {
        $res = [];

        // DBにないgenre_cdの場合
        if (strtoupper($genreCd) === config('const.genre.bigGenre.b-cooking.key') || strtoupper($genreCd) === config('const.genre.bigGenre.b-detailed.key')) {
            $query = Genre::query();
            $query->where('path', 'LIKE', '/'.strtolower($genreCd).'%');
            if ($optionLevel > 1) {
                $query->where('level', '<=', $optionLevel + 1);
            } else {
                $query->where('level', 2);
            }
            $query->where('published', 1);
            $list = $query->get();
            // DBにあるgenre_cdの場合
        } else {
            $list=$this->getChildGenreList($genreCd, $optionLevel);
        }

        foreach ($list as $key => $rec) {
            $res['genres'][$key]['id'] = $rec->id;
            $res['genres'][$key]['name'] = $rec->name;
            $res['genres'][$key]['genreCd'] = $rec->genre_cd;
            $res['genres'][$key]['appCd'] = $rec->app_cd;
            $res['genres'][$key]['path'] = $rec->path;
            $res['genres'][$key]['level'] = $rec->level;
            if (strtoupper($genreCd) === config('const.genre.bigGenre.b-cooking.key') || strtoupper($genreCd) === config('const.genre.bigGenre.b-detailed.key')){
                $res['genres'][$key]['childGenres'] = $this->getChildGenreList($rec->genre_cd, $optionLevel);
            }
        }

        return $res;
    }

    /**
     * テイクアウト-商品を受取る.
     *
     * @return void
     */
    public function close($request)
    {
        $reservationId = substr($request->reservationNo, 2);

        try {
            $query = Reservation::query();
            $query->where('id', $reservationId);
            $query->where('tel', Cipher::encrypt($request->tel));
            $query->whereNull('pick_up_receive_datetime');
            $reservation = $query->firstOrFail();

            $reservation->pick_up_receive_datetime = Carbon::now();
            $reservation->save();

            // アンケートメールを送る
            //$takeoutMail = new TakeoutMail($reservation->id);
            //$takeoutMail->closeReservation();
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * テイクアウト-検索ボックス取得.
     *
     * @return array
     */
    public function searchBox()
    {
        $res = [];

        $stores = Store::leftjoin('areas', 'areas.id', '=', 'stores.area_id')
            ->select('stores.area_id',
                'areas.name as area_name',
                'areas.area_cd',
                'areas.path',
                'areas.level',
                'areas.weight', )
            ->where('stores.published', 1)
            ->where(function ($query) {
                $query->where('stores.app_cd', key(config('code.appCd.to')))
                      ->orWhere('stores.app_cd', key(config('code.appCd.tors')));
            })
            ->get();
        $sortedStores = $stores->unique('area_name')->sortByDesc('areas.weight');

        $parentPathArr = [];
        $areasLevel2 = [];
        foreach ($sortedStores as  $val) {
            if (!is_null($val->area_name)) {
                $tmp = [];
                $tmp['id'] = $val->area_id;
                $tmp['name'] = $val->area_name;
                $tmp['areaCd'] = $val->area_cd;
                $tmp['path'] = $val->path;
                $tmp['level'] = $val->level;
                $tmp['weight'] = $val->weight;
                $path = explode('/', $val->path);
                if (!in_array($path[1], $parentPathArr)) {
                    $parentPathArr[] = $path[1];
                }
                $areasLevel2[] = $tmp;
            }
        }
        $parentAreas = Area::getParentAreas($parentPathArr);

        $areasLevel1 = [];
        foreach ($parentAreas as $val) {
            $tmp = [];
            $tmp['id'] = $val->id;
            $tmp['name'] = $val->name;
            $tmp['areaCd'] = $val->area_cd;
            $tmp['path'] = $val->path;
            $tmp['level'] = $val->level;
            $tmp['weight'] = $val->weight;
            $areasLevel1[] = $tmp;
        }

        $res['areas'] = array_merge($areasLevel1, $areasLevel2);

        return $res;
    }

    /**
     * テイクアウト-ジャンル一覧取得.
     *
     * @param string genreCd
     * @param int optionLevel
     *
     * @return array
     */
    private function getChildGenreList($genreCd, $optionLevel){
        $genre = Genre::where('genre_cd', $genreCd)->first();
        if (empty($genre)) {
            return $res;
        }
        $query = Genre::query();
        if ($optionLevel > 1) {
            if ($optionLevel === 2) {
                $query->where('path', 'LIKE', $genre->path.'/'.$genre->genre_cd.'/%');
            // level3を指定するほどの階層はデータ上はないが一応
            } elseif ($optionLevel === 3) {
                $query->where('path', 'LIKE', $genre->path.'/'.$genre->genre_cd.'/%'.'/');
            }
        } else {
            $query->where('path', $genre->path.'/'.$genre->genre_cd);
        }

        $query->where('published', 1);
        $list = $query->get();
        return $list;
    }
}
