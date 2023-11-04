<?php

namespace App\Models;

use App\Modules\Search;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redis;
use Kyslik\ColumnSortable\Sortable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Menu extends Model
{
    use Notifiable;
    use Sortable;
    use SoftDeletes;

    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
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

    public function image($imageCd = null)
    {
        $rec = $this->hasOne('App\Models\Image', 'menu_id', 'id');

        if (!is_null($imageCd)) {
            $rec->where('image_cd', $imageCd);
        }

        return $rec->first();
    }

    public function store()
    {
        return $this->hasOne('App\Models\Store', 'id', 'store_id');
    }

    public function anotherStaff(): belongsTo
    {
        return $this->belongsTo('App\Models\Staff', 'staff_id', 'id');
    }

    public function menuPrices()
    {
        return $this->hasMany('App\Models\Price', 'menu_id', 'id');
    }

    public function orderInterval()
    {
        return $this->hasMany('App\Models\OrderInterval', 'menu_id', 'id');
    }

    public function reviews()
    {
        return $this->hasMany('App\Models\Review', 'menu_id', 'id');
    }

    public function reviews_published()
    {
        return $this->hasMany('App\Models\Review', 'menu_id', 'id')->where('published', 1);
    }

    public function menuPrice($pickUpDate = null)
    {
        $rec = $this->hasOne('App\Models\Price', 'menu_id', 'id');

        if (is_null($pickUpDate)) {
            $pickUpDate = Carbon::now()->format('Y-m-d');
        }

        return $rec->where('start_date', '<=', $pickUpDate)->where('end_date', '>=', $pickUpDate)->first();
    }

    public function stocks()
    {
        return $this->hasMany('App\Models\Stock', 'menu_id', 'id');
    }

    public function options()
    {
        return $this->hasMany('App\Models\Option', 'menu_id', 'id');
    }

    public function stock($pickUpDate = null)
    {
        if (is_null($pickUpDate)) {
            $pickUpDate = Carbon::now()->format('Y-m-d');
        }

        return $this->hasOne('App\Models\Stock', 'menu_id', 'id')->where('date', '=', $pickUpDate)->first();
    }

    public function genres()
    {
        return $this->hasManyThrough(
            'App\Models\Genre',
            'App\Models\GenreGroup',
            'menu_id',
            'id',
            'id',
            'genre_id'
        );
    }

    public function latestReviews($take)
    {
        $rec = $this->hasMany('App\Models\Review', 'menu_id', 'id');

        return $rec->latest()->take($take)->get();
    }

    /**
     * おすすめテイクアウト取得.
     *
     * @param $params
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRecommendation($params)
    {
        $count = null;
        $list = $this->_getNewRecommendation($params);
        //$list = $this->_getRecommendation($params, config('takeout.batch.cacheRecommend.cache.nameSearchApi'), $count);
        foreach ($list as $menu) {
            $menu->image = $menu->image(config('code.imageCd.menuMain'));
            $menu->store;
            if (isset($menu->store->genre_delegate->genre_id)) {
                $menu->store->genres = Genre::find($menu->store->genre_delegate->genre_id);
            }

            $pickDate = isset($params['pickUpDate']) ? $params['pickUpDate'] : null;
            $menu->menuPrice = $menu->menuPrice($pickDate);
        }

        return $list;
    }

    /**
     * メニュー取得.
     *
     * @param $params
     * @param array $genres
     *
     * @return array
     */
    public function search($params, &$genres = [])
    {
        $store = new Store();
        $station = new Station();

        $result = [
            'count' => 0,
            'pageMax' => 0,
            'list' => collect([]),
        ];

        $isRecommend = ((count($params) === 1 && isset($params['page'])) || count($params) === 0) ? true : false;

        // 拾う時間指定がない場合は現在時間で絞る
        $params['pickUpTime'] = empty($params['pickUpTime']) ? Carbon::now()->format('H:i') : $params['pickUpTime'];
        // 拾う日付指定がない場合は現在日付で絞る
        $params['pickUpDate'] = empty($params['pickUpDate']) ? Carbon::now()->format('Y-m-d') : $params['pickUpDate'];

        if (isset($params['suggestCd'])) {
            // 現在地検索-半径km以内のstore_idを取得
            if ($params['suggestCd'] === config('code.suggestCd.current')) {
                $params['storeIds'] = $store->searchByRadius($params['latitude'], $params['longitude']);
            // 駅検索-駅から半径km以内のstore_idを取得
            } elseif ($params['suggestCd'] === config('code.suggestCd.station')) {
                $q = Station::where('name', $params['suggestText']);
                $q->whereHas('prefecture', function ($q) {
                    // リリース時は東京固定
                    $q->where('prefectures.name', '東京');
                });
                $station = $q->first();
                if (!$station) {
                    return $result;
                }
                $params['storeIds'] = $store->searchByRadius($station->latitude, $station->longitude);
            }
            if (count($params['storeIds']) === 0) {
                return $result;
            }
        }
        $params['cookingGenreCd'] = isset($params['cookingGenreCd']) ? $params['cookingGenreCd'] : null;
        $params['menuGenreCd'] = isset($params['menuGenreCd']) ? $params['menuGenreCd'] : null;
        $params['count'] = ($isRecommend) ? config('takeout.menu.recommend.perPage') : config('takeout.menu.search.perPage');
        $count = null;
        $list = ($isRecommend) ? $this->_getRecommendation($params, config('takeout.batch.cacheRecommend.cache.nameSearchApi'), $count, $genres) : $this->_search($params, $count, $genres);
        if (count($list) === 0) {
            return $result;
        }

        // images取得
        $menuIds = $list->pluck('id')->all();
        $tmpQuery = Image::query();
        $tmpQuery->whereIn('menu_id', $menuIds);
        $tmpImages = $tmpQuery->get()->keyBy('menu_id');

        // stores取得
        $storeIds = $list->pluck('store_id')->all();
        $tmpStores = Store::whereIn('id', $storeIds)->get();

        // stations
        //$stationIds = $tmpStores->pluck('station_id')->all();
        //$tmpStations = Station::whereIn('id', $stationIds)->get();

        // menu_prices取得
        $pricePickUpDate = isset($params['pickUpDate']) ? $params['pickUpDate'] : Carbon::now()->format('Y-m-d');
        $tmpQuery = Price::query();
        $tmpPrices = $tmpQuery->whereIn('menu_id', $menuIds)
            ->where('start_date', '<=', $pricePickUpDate)->where('end_date', '>=', $pricePickUpDate)
            ->get()->keyBy('menu_id');

        // ジャンル取得
        /*$tmpQuery = Genre::query();
        $tmpQuery->whereHas('genreGroups', function ($tmpQuery) use ($menuIds) {
            $tmpQuery->whereIn('menu_id', $menuIds);
        });
        $tmpGenres = $tmpQuery->get();*/

        $storesById = $tmpStores->keyBy('id');
        //$stationsById = $tmpStations->keyBy('id');
        foreach ($list as $key => $menu) {
            if (isset($tmpImages[$menu->id])) {
                $menu->image = $tmpImages[$menu->id];
            }

            if (isset($storesById[$menu->store_id])) {
                $menu->store = $storesById[$menu->store_id];
                //if (isset($stationsById[$storesById[$menu->store_id]->station_id])) {
                //    $menu->store->stations = $stationsById[$storesById[$menu->store_id]->station_id];
                //}
            }

            if (isset($tmpPrices[$menu->id])) {
                $menu->menuPrice = $tmpPrices[$menu->id];
            }

            // ジャンル取得
            /*if (empty($params['cookingGenreCd'] && empty($params['menuGenreCd']))) {
                foreach ($menu->genres as $tmpGenre) {
                    //親ジャンルを取得
                    if (in_array($tmpGenre->level, [3, 4])) {
                        $tmp = explode('/', $tmpGenre->path);
                        \Log::debug($tmp[2]);
                        if (isset($tmp[2])) {
                            $genres[] = Genre::where('genre_cd', $tmp[2])->where('level', 2)->first();
                        }
                    } elseif (in_array($tmpGenre->level, [2])) {
                        $genres[] = $tmpGenre;
                    }
                }
            }*/

            /*if (empty($params['cookingGenreCd'] && empty($params['menuGenreCd']))) {
                foreach ($tmpGenres as $tmpGenre) {
                    //親ジャンルを取得
                    if (in_array($tmpGenre->level, [3, 4])) {
                        $tmp = explode('/', $tmpGenre->path);
                        if (isset($tmp[2])) {
                            $genres[] = Genre::where('genre_cd', $tmp[2])->where('level', 2)->first();
                        }
                    } elseif (in_array($tmpGenre->level, [2])) {
                        $genres[] = $tmpGenre;
                    }
                }
            }*/

            // 距離取得
            if (isset($params['suggestCd'])) {
                $menu->store->storeDistance = isset($params['storeIds'][$menu->store_id]) ? $params['storeIds'][$menu->store_id] : 0;
                //$menu->store->storeDistance = Search::getDistance($params['latitude'], $params['longitude'], $menu, 1);
                //$menu->store->stations->stationDistance = Search::getDistance($params['latitude'], $params['longitude'], $menu, 2);
            }
        }
        $result['count'] = $count;
        $result['pageMax'] = ceil($count / $params['count']);
        $result['list'] = $list;

        return $result;
    }

    private function _getRecommendation($params, $name, &$count = 0, &$genres = [])
    {
        $recommendList = [];

        $recommendation = json_decode(Redis::get($name));

        if (empty($recommendation)) {
            return [];
        }
        foreach ($recommendation as $key => $rec) {
            $menu = Menu::hydrate($rec);
            $recommendList[] = $menu;
        }

        $merged = collect();

        for ($i = 0; $i < count($recommendList); ++$i) {
            $merged = $merged->mergeRecursive($recommendList[$i]);
        }

        $count = count($merged);
        if ($count === 0) {
            return collect();
        }

        $merged = $merged->sortByDesc('updated_at');

        $stock = new Stock();
        foreach ($merged as $key => $menu) {
            if (!isset($params['pickUpDate'])) {
                continue;
            }
            // 在庫チェック
            if (!$stock->hasStock($params['pickUpDate'], $menu->id, 1)) {
                $merged->forget($key);
                continue;
            }
        }

        // ページング処理
        $page = (!empty($params['page'])) ? $params['page'] : 1;
        $count = count($merged);
        $result = $merged->forPage($page, config('takeout.menu.recommend.perPage'));

        return $result->values();
    }

    private function _getNewRecommendation($params)
    {
        $query = Menu::Query();
        $query->whereHas('store', function ($q) use ($params) {
            if (!empty($params['areaCd'])) {
                $q->where('stores.area_id', Area::where('area_cd', $params['areaCd'])->first()->id);
            }
            $q->where('stores.published', 1);
        });
        $query->groupBy('store_id');

        $menus = $query->where('published', 1)
            ->where('app_cd', key(config('code.appCd.to')))
            ->inRandomOrder()->take(10)->get();

        return $menus;
    }

    private function _search($params, &$count = 0, &$genres = [])
    {
        $queryParams = [
            'date' => $params['pickUpDate'],
            'pick_up_date' => $params['pickUpDate'],
            'app_cd' => key(config('code.appCd.to')),
        ];

        $query = 'select * from menus
        left join
        (
        select menu_id,stock_number from stocks where date = :date
        ) as t2
        on menus.id = t2.menu_id
        left join
        (
        select SUM(reservation_menus.count) as ordered, reservations.pick_up_datetime, reservation_menus.menu_id from reservations
        inner join reservation_menus on reservation_menus.reservation_id = reservations.id
        where DATE(reservations.pick_up_datetime) = :pick_up_date
        group by reservation_menus.menu_id
        ) as t3
        on menus.id = t3.menu_id
        left join
        (
        select stores.id as store_id from stores
        ) as t4
        on menus.store_id = t4.store_id
        left join
        (
        select genres.genre_cd, genre_groups.menu_id, genres.path, genres.level from genre_groups inner join genres on genre_groups.genre_id = genres.id
        ) as t5
        on menus.id = t5.menu_id
        where (ifnull(ordered,0) < stock_number)
        and menus.published = 1
        and menus.deleted_at IS NULL
        and menus.app_cd = :app_cd';

        // ジャンルコード(料理ジャンル)が指定されている場合
        if (!empty($params['cookingGenreCd'])) {
            // level=3,4に紐づくメニュー
            $query .= ' AND (path LIKE :gpath OR level = 2)';
            $queryParams['gpath'] = '/b-cooking/'.$params['cookingGenreCd'].'%';
        }

        // ジャンルコード(メニュージャンル)が指定されている場合
        if (!empty($params['menuGenreCd'])) {
            $query .= ' AND genre_cd = :menu_genre_cd';
            $queryParams['menu_genre_cd'] = $params['menuGenreCd'];
        }
        // storeIdsが指定されている場合(座標検索結果)
        $ids = null;
        if (isset($params['suggestCd'])) {
            $ids = array_keys($params['storeIds']);
            $query .= ' AND menus.store_id IN ('.implode(', ', $ids).')';
            $query .= ' ORDER BY FIELD(menus.store_id,'.implode(', ', $ids).')';
        }
        // メニュー取得
        $menus = \DB::select($query, $queryParams);
        $query = Menu::Query();
        $query->whereIn('id', array_column($menus, 'id'));
        if (!is_null($ids)) {
            $query->orderBy(\DB::raw('FIELD(store_id, '.implode(', ', $ids).')'));
        }
        // 全件数を取得
        $count = $query->count();

        // ジャンル取得
        $tmpMenus = $query->get();

        $tmpQuery = Genre::query();
        $tmpQuery->whereHas('genreGroups', function ($tmpQuery) use ($tmpMenus) {
            $tmpQuery->whereIn('menu_id', $tmpMenus->pluck('id'));
        });
        $ggs = $tmpQuery->get();

        // cookingGenreCd検索の場合
        if (!empty($params['cookingGenreCd'])) {
            foreach ($ggs as $gg) {
                if (strpos($gg->path, $params['cookingGenreCd'])) {
                    $genres[] = $gg;
                }
            }
        }
        // ジャンル取得
        if (empty($params['cookingGenreCd'] && empty($params['menuGenreCd']))) {
            foreach ($ggs as $gg) {
                //親ジャンルを取得
                if (in_array($gg->level, [3, 4])) {
                    $tmp = explode('/', $gg->path);

                    if (isset($tmp[2])) {
                        $genres[] = Genre::where('genre_cd', $tmp[2])->where('level', 2)->first();
                    }
                } elseif (in_array($gg->level, [2])) {
                    $genres[] = $gg;
                }
            }
        }

        // ページングで取得
        $skip = !empty($params['page']) ? config('takeout.menu.search.perPage') * ($params['page'] - 1) : 0;
        $query->skip($skip)->take(config('takeout.menu.search.perPage'));

        return $query->get();
    }

    /**
     * お気に入りメニュー取得.
     *
     * @return Illuminate\Database\Eloquent\Collection
     *
     * @throws Illuminate\Database\QueryException
     */
    public function get(array $ids, string $pickUpDate = null, string $pickUpTime = null)
    {
        $query = Menu::whereIn('id', $ids);

        if (!is_null($pickUpTime)) {
            $query->where(function ($query) use ($pickUpTime) {
                $query->where(function ($query) use ($pickUpTime) {
                    $query->where('sales_lunch_start_time', '<=', $pickUpTime)
                    ->where('sales_lunch_end_time', '>', $pickUpTime);
                });

                $query->orWhere(function ($query) use ($pickUpTime) {
                    $query->where('sales_dinner_start_time', '<=', $pickUpTime)
                ->where('sales_dinner_end_time', '>', $pickUpTime);
                });
            });
        }

        if (!is_null($pickUpDate)) {
            $query->whereHas('menuPrices', function ($query) use ($pickUpDate) {
                $query->where('start_date', '<=', $pickUpDate)
                     ->where('end_date', '>=', $pickUpDate);
            });
        }

        $list = $query->get();

        if (count($list) === 0) {
            return $list;
        }

        foreach ($list as $menu) {
            $menu->image = $menu->image(config('code.imageCd.menuMain'));
            $menu->store;
            $menu->store->stations;
            $menu->menuPrice = $menu->menuPrice($pickUpDate);
        }

        return $list;
    }

    /**
     * メニュー詳細.
     *
     * @param int id
     * @param string pickUpDate
     *
     * @return Menu
     *
     * @throws Illuminate\Database\QueryException
     */
    public function detail(int $id, string $pickUpDate)
    {
        $menu = Menu::findOrFail($id);
        $menu->image = $menu->image(config('code.imageCd.menuMain'));
        $menu->store->stations;
        $menu->store->genres;
        $menu->menuPrice = $menu->menuPrice($pickUpDate);
        $menu->stockOne = $menu->stock($pickUpDate);
        $menu->options;

        return $menu;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed array                           $valid
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function scopeAdminSearchFilter($query, $valid)
    {
        if ( !\Gate::check('inHouseGeneral-higher') && !\Gate::check('inAndOutHouseGeneral-only')) {
            $query->where('store_id', (\Auth::user())->store_id);
        }
        if (isset($valid['id']) && !empty($valid['id'])) {
            $query->where('id', '=', $valid['id']);
        }
        if (isset($valid['app_cd']) && !empty($valid['app_cd'])) {
            $query->where('app_cd', '=', $valid['app_cd']);
        }
        if (isset($valid['name']) && !empty($valid['name'])) {
            $query->where('name', 'like', '%'.$valid['name'].'%');
        }
        if (isset($valid['store_name']) && !empty($valid['store_name'])) {
            $query->whereHas('store', function ($query) use ($valid) {
                $query->where('name', 'like', '%'.$valid['store_name'].'%');
            });
        }

        return $query;
    }

    /**
     * テイクアウト完了処理メニュー取得.
     *
     * @param array info
     *
     * @return Illuminate\Database\Eloquent\Collection
     *
     * @throws Illuminate\Database\QueryException
     */
    public function getMenuFromInfo($info)
    {
        $infoMenus = array_column($info['application']['menus'], 'menu');
        $ids = array_column($infoMenus, 'id');
        $menus = Menu::whereIn('id', $ids)->get();

        $dt = new Carbon($info['application']['pickUpDate'].' '.$info['application']['pickUpTime']);
        $msg = null;
        foreach ($menus as $menu) {
            $menuObj = Menu::find($menu->id);
            // opening_hours store menuの曜日と祝日のチェック
            if (!$this->canSale($menuObj->id, $menuObj->store_id, $dt, $msg)) {
                throw new \Exception($msg);
            }
            $menu->menuPrice = $menu->menuPrice($info['application']['pickUpDate']);
            $menu->store;
            $menu->genres;
        }

        return $menus;
    }

    /**
     * レストラン完了処理メニュー取得.
     *
     * @param array info
     *
     * @return Illuminate\Database\Eloquent\Collection
     *
     * @throws Illuminate\Database\QueryException
     */
    public function getRestaurantMenuFromInfo($info)
    {
        $infoMenus = array_column($info['application']['menus'], 'menu');
        $ids = array_column($infoMenus, 'id');
        $menus = Menu::whereIn('id', $ids)->get();

        $dt = new Carbon($info['application']['visitDate'].' '.$info['application']['visitTime']);
        $msg = null;
        foreach ($menus as $menu) {
            $menuObj = Menu::find($menu->id);
            // opening_hours store menuの曜日と祝日のチェック
            if (!$this->canSale($menuObj->id, $menuObj->store_id, $dt, $msg)) {
                throw new \Exception($msg);
            }
            $menu->menuPrice = $menu->menuPrice($info['application']['visitDate']);
            $menu->store;
            $menu->genres;
        }
        return $menus;
    }

    /**
     * 売れるかどうか.
     *
     * @param int menuId
     * @param string date 日付(テスト以外では普通は省略)
     * @param string time 時間(テスト以外では普通は省略)
     *
     * @return bool true:ラストオーダー前 false:ラストオーダー過ぎ
     */
    public function canSale($menuId, $storeId, $now = null, &$msg = null)
    {
        $now = is_null($now) ? Carbon::now() : new Carbon($now);
        // 店舗の定休日を確認
        $store = Store::find($storeId);
        if (!$this->checkFromWeek($store->regular_holiday, $now, 1, $msg)) {
            return false;
        }

        // メニュー提供可能日か確認
        $menu = Menu::find($menuId);
        if (!$this->checkFromWeek($menu->provided_day_of_week, $now, 2, $msg)) {
            return false;
        }

        // 営業時間 営業曜日を確認
        if (!$this->checkOpeningHours($store->id, $now, $msg)) {
            return false;
        }

        return true;
    }

    public function checkOpeningHours($id, Carbon $now, &$msg)
    {
        $result = false;
        $openingHours = OpeningHour::where('store_id', $id)->get();
        [$week, $weekName] = $this->getWeek($now);

        foreach ($openingHours as $openingHour) {
            if ($openingHour->week[$week] !== '1') {
                continue;
            }
            if (
                !(strtotime($now->copy()->format('H:i:s')) >= strtotime($openingHour->start_at) && strtotime($now->copy()->format('H:i:s')) < strtotime($openingHour->end_at))
            ) {
                continue;
            }
            // 祝日休みの場合は今日が祝日かどうかチェック
            if ($openingHour->week[7] !== '1') {
                $holiday = Holiday::where('date', $now->format('Y-m-d'))->first();
                if (!is_null($holiday)) {
                    // 祝日のため休み
                    continue;
                }
            }
            $result = true;
            break;
        }
        if (!$result) {
            $msg = sprintf(Lang::get('message.weekFailure0'));
        }

        return $result;
    }

    public function checkFromWeek($weeks, Carbon $now, $type, &$msg)
    {
        [$week, $weekName] = $this->getWeek($now);

        // 祝日休みの場合は今日が祝日かどうかチェック
        if ($weeks[7] !== '1') {
            $holiday = Holiday::where('date', $now->format('Y-m-d'))->first();
            if (!is_null($holiday)) {
                $msg = sprintf(Lang::get('message.weekFailure'.$type), $holiday->date);

                return false;
            }
        }

        if ($weeks[$week] !== '1') {
            $msg = sprintf(Lang::get('message.weekFailure'.$type), $weekName);

            return false;
        }

        return true;
    }

    public function getWeek($now)
    {
        $week = null;
        $weekName = '';
        switch (date('w', $now->timestamp)) {
                case 0:
                    $week = 6;
                    $weekName = '日曜日';
                    break;

                case 1:
                    $week = 0;
                    $weekName = '月曜日';
                    break;

                case 2:
                    $week = 1;
                    $weekName = '火曜日';
                    break;

                case 3:
                    $week = 2;
                    $weekName = '水曜日';
                    break;

                case 4:
                    $week = 3;
                    $weekName = '木曜日';
                    break;

                case 5:
                    $week = 4;
                    $weekName = '金曜日';
                    break;

                case 6:
                    $week = 5;
                    $weekName = '土曜日';
                    break;
            }

        return [$week, $weekName];
    }

    public function checkExistsOpeningHours($storeId, Carbon $visitDate, $type, &$msg)
    {
        // 営業時間
        $openingHours = OpeningHour::where('store_id', $storeId)->get();
        $holiday = Holiday::where('date', $visitDate)->first();

        // 月=>0 火=>1 水=>2 木=>3 金=>4 土=>5 日=>6 祝=>7
        // 祝日ではないの場合
        if (is_null($holiday)) {
            $date = (new Carbon($visitDate))->dayOfWeek - 1;
            $date = $date === -1 ? $date = 6 : $date;
        // 祝日の場合
        } else {
            $date = 7;
        }

        // 店舗の基本情報を配列化
        foreach ($openingHours as $key => $openingHour) {
            $weeks[$key]['week'] = str_split($openingHour->week);    // 営業日
            $weeks[$key]['open'] = $openingHour->start_at;           // 開店時間
            $weeks[$key]['end'] = $openingHour->end_at;              // 閉店時間
            $weeks[$key]['last'] = $openingHour->last_order_time;    // ラストオーダー時間

            // ラストオーダー時間の設定がない場合は、閉店時間を取得
            $weeks[$key]['last'] = is_null($weeks[$key]['last']) ? $openingHour->end_at : $weeks[$key]['last'];
        }

        // 営業時間の配列化
        $resultWeeks = [];
        foreach ($weeks as $key => $week) {
            if ($week['week'][$date] === '1') {
                $resultWeeks[$key] = $week;
            }
        }

        // 店舗営業時間設定で設定されている時間がないとき（休日）
        if (empty($resultWeeks)) {
            $msg = sprintf(Lang::get('message.weekFailure0'));
            return false;
        }
        return true;
    }

    public function checkLowerOrderTime($menu, $dt)
    {
        // 現在時刻
        $now = new Carbon();

        // 最低注文時間（未設定の場合は0分として扱う）
        $lowerOrdersTime = !is_null($menu->lower_orders_time) ? $menu->lower_orders_time : 0;
        $now->addMinutes($lowerOrdersTime);
        if ($dt <= $now) {
            return false;
        }

        return true;
    }
}
