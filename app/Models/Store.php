<?php

namespace App\Models;

use App\Libs\ImageUpload;
use App\Modules\Search;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Kyslik\ColumnSortable\Sortable;
use App\Models\Menu;

class Store extends Model
{
    use Notifiable;
    use Sortable;
    use SoftDeletes;

    protected $guarded = ['id'];

    public function image()
    {
        return $this->hasMany('App\Models\Image', 'store_id', 'id');
    }

    public function images($imageCd = null)
    {
        $rec = $this->hasMany('App\Models\Image', 'store_id', 'id');
        if (!is_null($imageCd)) {
            $rec->where('image_cd', $imageCd);
        }

        return $rec->get();
    }

    public function reviews(): hasMany
    {
        return $this->hasMany('App\Models\Review', 'store_id', 'id');
    }

    public function takeoutMenus(): hasMany
    {
        return $this->hasMany('App\Models\Menu', 'store_id', 'id');
    }

    public function restaurantMenus()
    {
        return $this->hasMany('App\Models\Menu', 'store_id', 'id')->where('app_cd', key(config('code.appCd.rs')));
    }

    public function recommendMenus(): hasMany
    {
        return $this->takeoutMenus()->where('published', '=', 1);
    }

    public function openingHours(): hasMany
    {
        return $this->hasMany('App\Models\OpeningHour', 'store_id', 'id');
    }

    public function settlementCompany(): hasOne
    {
        return $this->hasOne('App\Models\SettlementCompany', 'id', 'settlement_company_id');
    }

    public function stations(): hasOne
    {
        return $this->hasOne('App\Models\Station', 'id', 'station_id');
    }

    public function areas(): hasOne
    {
        return $this->hasOne('App\Models\Area', 'id', 'area_id');
    }

    public function staff(): belongsTo
    {
        return $this->belongsTo('App\Models\Staff', 'id', 'store_id');
    }

    public function anotherStaff(): belongsTo
    {
        return $this->belongsTo('App\Models\Staff', 'staff_id', 'id');
    }

    public function genres()
    {
        return $this->hasManyThrough(
            'App\Models\Genre',
            'App\Models\GenreGroup',
            'store_id',
            'id',
            'id',
            'genre_id'
        );
    }

    public function genre_delegate()
    {
        return $this->hasOne('App\Models\GenreGroup', 'store_id', 'id')->where('is_delegate', 1);
    }

    public function external_api()
    {
        return $this->hasOne('App\Models\ExternalApi', 'store_id', 'id');
    }

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
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function scopeAdminSearchFilter($query, $valid)
    {
        if (isset($valid['id']) && !empty($valid['id'])) {
            $query->where('id', '=', $valid['id']);
        }
        if (isset($valid['name']) && !empty($valid['name'])) {
            $query->where('name', 'like', '%'.$valid['name'].'%');
        }
        if (isset($valid['settlement_company_name']) && !empty($valid['settlement_company_name'])) {
            $query->whereHas('settlementCompany', function($query) use ($valid){
                $query->where('name', 'like', '%'.$valid['settlement_company_name'].'%');
            });
        }
        if (isset($valid['code']) && !empty($valid['code'])) {
            $query->where('code', 'like', '%'.$valid['code'].'%');
        }

        return $query;
    }

    public static function scopePublished($query)
    {
        return $query->where('published', '=', 1);
    }

    /**
     * 半径km以内のスポットを検索しIDと距離(m)を取得する.
     *
     * @param  float originLat
     * @param  float originLon
     */
    public function searchByRadius($originLat, $originLon): array
    {
        // key(m)固定 => value1000(m) 1(km)も可能ですが全部valueの単位は揃える
        $listRadius = ['1000' => 1000];
        // 地球の距離
        $earch = 6378 * $listRadius['1000'];
        $result = [];
        foreach ($listRadius as $key => $radius) {
            $query = 'SELECT id,(? * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude)-radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance FROM stores HAVING distance < ? ORDER BY distance';
            $queryResult[$key] = DB::select($query, [$earch, $originLat, $originLon, $originLat, $radius]);

            foreach ($queryResult[$key] as $val) {
                $result[$val->id] = $val->distance;
            }
        }

        return $result;
    }

    public function getRegularHoliday(int $key)
    {
        // 9桁'111111110'の場合は定休日なし
        if (Str::startsWith($this->regular_holiday, '111111110')) {
            if ($key == 8) {
                return 0;
            } else {
                return 1;
            }
        }

        if ($key == 8) {
            return 1;
        }

        $array = str_split($this->regular_holiday);

        \Log::debug($key);
        if ($key == 9) {
            --$key;

            return ($array[$key] == 1) ? 0 : 1;
        }

        return $array[$key];
    }

    /**
     * 店舗一覧の出し分け.
     *
     * @param $query
     *
     * @return mixed
     */
    public function scopeList($query)
    {
        if (\Gate::check('client-only')) {
            return $query->where('id', \Auth::user()->store_id);
        }

        return null;
    }

    /**
     * 店舗コード変更に伴う、画像ファイルの移動.
     *
     * @param $oldImage
     * @param $storeCode
     * @param $category
     */
    public function moveImage($oldImage, $storeCode, $category): string
    {
        $oldUrl = $oldImage;
        $cutFrom = env('GOOGLE_CLOUD_STORAGE_IMAGE_PATH_PREFIX', 'images/');
        $startStore = strpos($oldUrl, $cutFrom) + strlen($cutFrom);
        // URLからGCSのディレクトリ名を切り出す
        $length = strpos($oldUrl, $category) - $startStore;
        $oldDirName = substr($oldUrl, $startStore, $length);
        $oldGcs = $cutFrom.$oldDirName.$category.'/'.basename($oldUrl);
        $newGcs = $cutFrom.$storeCode.$category.'/'.basename($oldUrl);

        $gcs = \Storage::disk('gcs');
        // ストレージ上のディレクトリ名を変更
        if (!$gcs->exists($newGcs)) {
            $gcs->copy($oldGcs, $newGcs);
            $gcs->delete($oldGcs);
        }
        $stickUrl = strstr($oldUrl, $category);

        return ImageUpload::environment().$cutFrom.$storeCode.$stickUrl;
    }

    public function getFavoriteStores($params, $dateUndecided, $favoriteStoreIds = null)
    {
        if (!filter_var($dateUndecided, FILTER_VALIDATE_BOOLEAN)) {
            $storeList = $this->_storeSearch($params, $count, $recommendMenu, true);
            $storeIds = $storeList->pluck('id')->all();
            $storeIds = array_intersect($storeIds, $favoriteStoreIds);
        } else {
            $storeIds = $favoriteStoreIds;
        }
        $stores = Store::whereIn('id', $storeIds)->get();

        return $stores;
    }

    /**
     * レストラン検索（レストラン-店舗一覧取得）.
     *
     * @return array
     */
    public function storeSearch($params)
    {
        $menu = new Menu();
        $store = new Store();

        $result = [
            'count' => 0,
            'pageMax' => 0,
            'storeList' => collect([]),
        ];

        // サジェストコード（現在地・エリア）（店舗・駅検索は後に対応）
        if (isset($params['suggestCd'])) {
            // $params['suggestCd']に予期しない値が入っていたときのため変数に空配列を代入
            $params['parameter'] = [];
            // 現在地検索 - 半径km以内のstore_idを取得　(半径1km以内) $params['parameter']：店舗IDと距離が配列で格納
            if ($params['suggestCd'] === config('code.suggestCd.current')) {
                $params['parameter'] = $store->searchByRadius($params['latitude'], $params['longitude']);
            }
            // 駅検索 - 半径km以内のstore_idを取得　(半径1km以内) $params['parameter']：店舗IDと距離が配列で格納
            if ($params['suggestCd'] === config('code.suggestCd.station')) {
                $q = Station::where('name', $params['suggestText']);
                $q->whereHas('prefecture', function ($q) {
                    // リリース時は東京固定
                    $q->where('prefectures.name', '東京');
                });
                $station = $q->first();
                if (!$station) {
                    return $result;
                }
                $params['parameter'] = $store->searchByRadius($station->latitude, $station->longitude);
            }
            // エリア検索 - suggestTextにてエリア情報を検索 $params['parameter']：エリア情報が配列で格納
            if (isset($params['suggestText']) && $params['suggestCd'] === config('code.suggestCd.area')) {
                // suggestCdとsuggestTextからエリア情報を取得
                $params['parameter'] = Area::GetAreaIdWithAreaCd($params['suggestText'])->get();
            }
            // suggestCdが予期せぬ値&検索結果が0の場合
            if (count($params['parameter']) === 0) {
                return $result;
            }
        }

        $params['cookingGenreCd'] = isset($params['cookingGenreCd']) ? $params['cookingGenreCd'] : null;
        $params['menuGenreCd'] = isset($params['menuGenreCd']) ? $params['menuGenreCd'] : null;

        // テイクアウトのレストラン情報取得
        // ジャンルを取得する場合（現在は仕様変更によりジャンルはレスポンスに含まなくなったためコメントアウト）
        // $storeList = $this->_storeSearch($params, $count, $recommendMenu, $cookingGenres, $menuGenres, $detailGenres, $detailGenresChild);
        $storeList = $this->_storeSearch($params, $count, $recommendMenu);

        if (count($storeList) === 0) {
            return $result;
        }

        // すべての店舗IDを取得
        $storeIds = $storeList->pluck('id')->all();

        // // 仕様変更になったときのために残し
        // // メインジャンルのみ取得
        // // 店舗のメインジャンルを取得(genre_groupsのis_delegateで判定)
        // $tmpQuery = GenreGroup::query();
        // $tmpQuery->whereIn('store_id', $storeIds)->where('is_delegate', 1);
        // $tmpGenre = $tmpQuery->get()->keyBy('store_id');

        // 店舗IDと結びついたすべてのジャンルを取得（複数ジャンル）
        $tmpQuery = GenreGroup::query();
        $tmpQuery->with('genre')->whereIn('store_id', $storeIds);
        $tmpGenres = $tmpQuery->orderBy('genre_id', 'asc')->get();

        $tmpQuery = Image::query();
        // テイクアウト検索の場合
        if ($params['appCd'] === config('code.gmServiceCd.to')) {
            // images取得 FOOD_LOGOに設定されている画像 ※FOOD_LOGOは原則1枚のみ登録可
            $tmpQuery->whereIn('store_id', $storeIds)->where('image_cd', config('code.imageCd.foodLogo'));
        }

        // レストラン検索の場合
        if ($params['appCd'] === config('code.gmServiceCd.rs')) {
            // images取得 RESTAURANT_LOGOに設定されている画像 ※RESTAURANT_LOGOは原則1枚のみ登録可
            $tmpQuery->whereIn('store_id', $storeIds)->where('image_cd', config('code.imageCd.restaurantLogo'));
        }
        $tmpImages = $tmpQuery->get()->keyBy('store_id');

        // 営業時間取得
        $tmpQuery = OpeningHour::query();
        $tmpQuery->whereIn('store_id', $storeIds);
        $tmpOpeningHour = $tmpQuery->get();

        // // 仕様変更の時のための予備
        // // menus取得
        // if (isset($params['appCd'])) {
        //     $tmpQuery = Menu::query();

        //     // テイクアウトメニュー取得
        //     if ($params['appCd'] === config('code.gmServiceCd.to')){
        //         $tmpQuery = $tmpQuery->whereIn('store_id', $storeIds)->where('app_cd', $params['appCd']);
        //     }

        //     // レストラン
        //     if ($params['appCd'] === config('code.gmServiceCd.rs')){
        //         $tmpQuery = $tmpQuery->whereIn('store_id', $storeIds)->where('app_cd', $params['appCd']);
        //     }

        //     $tmpMenus = $tmpQuery->get()->keyBy('store_id');
        // }

        foreach ($storeList as $store) {
            // ジャンル
            // 店舗IDと結びついたすべてのジャンルを入れる場合（複数ジャンル）
            if (isset($tmpGenres)) {
                $insert = [];
                foreach ($tmpGenres as $genreKey => $genre) {
                    $genre->store_id == $store->id ? $insert[] = $genre : null;
                }
                $store->genre = $insert;
            }

            // // 仕様変更になったときのために残し
            // // 店舗のメインジャンルを入れる場合(genre_groupsのis_delegateで判定)
            // if (isset($tmpGenre[$store->id])) {
            //     $store->genre = $tmpGenre[$store->id];
            // }

            // 画像
            if (isset($tmpImages[$store->id])) {
                $store->image = $tmpImages[$store->id];
            }

            // 営業時間取得
            if (isset($tmpOpeningHour)) {
                $insert = [];
                foreach ($tmpOpeningHour as $genreKey => $openingHour) {
                    $openingHour->store_id == $store->id ? $insert[] = $openingHour : null;
                }
                $store->openingHours = $insert;
            }

            // // 仕様変更の時のための予備
            // // メニュー
            // if (isset($menusById[$store->store_id])) {
            //     $store->menu = $menusById[$store->id];
            // }

            // 距離(テスト用緯度経度　緯度：35.6473253　経度：139.7080963)
            // サジェストコードあり＆サジェストコードがCURRENT_LOCの時のみ距離を算出（サジェストコード駅（STATION）が後から追加になる）
            if (isset($params['suggestCd']) && $params['suggestCd'] === config('code.suggestCd.current')) {
                $store->distance = (int) floor(Search::getStoreDistance($params['latitude'], $params['longitude'], $store->id));
            }
        }

        $result['count'] = $count;                                  // 総件数
        $params['count'] = config('takeout.store.search.perPage');  // １ページにおける表示数
        $result['pageMax'] = ceil($count / $params['count']);       // 総ページ数
        $result['storeList'] = $storeList;                          // 店舗情報
        $result['recommendMenu'] = $recommendMenu;                  // おすすめメニュー情報

        // // ジャンルを取得する場合（現在は仕様変更によりジャンルはレスポンスに含まなくなったためコメントアウト）
        // $result['menuGenre'] = $menuGenres;                         // メニュージャンル　ジャンル（小）
        // $result['cookingGenre'] = $cookingGenres;                   // クッキングジャンル　ジャンル（中）
        // $result['detailGenre'] = $detailGenres;                     // こだわり条件
        // $result['detailGenreChild'] = $detailGenresChild;           // こだわり条件(child)

        return $result;
    }

    /**
     * 店舗検索サブメソッド.
     *
     * ジャンルを取得する場合はアンコメントで下記参照渡し用コードを活性化（現在は仕様変更によりジャンルはレスポンスに含まなくなったためコメントアウト）
     * private function _storeSearch($params, &$count = 0, &$recommendMenu, &$cookingGenres = [], &$menuGenres = [], &$detailGenres = [], &$detailGenresChild = [])
     */
    private function _storeSearch($params, &$count = 0, &$recommendMenu, $isFavorite = false)
    {
        $isCurrentLoc = false;

        // 来店日、来店時間、来店人数設定がない場合のため
        $vacancyStoreIds = 'none';

        $queryParams = [];

        $masterQuery =
        '
        select *, stores.id from stores
        left join
        (
        select genres.genre_cd, genre_groups.store_id, genre_groups.is_delegate, genres.path, genres.level from genre_groups inner join genres on genre_groups.genre_id = genres.id
        ) as T2 on stores.id = T2.store_id
        inner join (
            select menus.app_cd, menus.store_id, menus.published from menus
        ) as T3 on stores.id = T3.store_id
        left join
        external_apis
        on stores.id = external_apis.store_id
        where stores.deleted_at IS NULL
        AND stores.published = 1
        AND T3.published = 1
        ';

        $query = $masterQuery;

        // app_cd(サービス利用コード)が指定されている場合
        if (!empty($params['appCd'])) {
            switch ($params['appCd']) {
                // テイクアウトが選択された時　TO・TORSを取得
                case config('code.gmServiceCd.to'):
                    $query .= ' AND (stores.app_cd = :app_cd_to OR stores.app_cd = :app_cd_tors)';
                    $queryParams['app_cd_to'] = config('code.gmServiceCd.to');
                    $queryParams['app_cd_tors'] = config('code.gmServiceCd.tors');
                    $query .= ' AND T3.app_cd = "TO"';
                    break;
                // レストランが選択された時　RS・TORSを取得
                case config('code.gmServiceCd.rs'):
                    $query .= ' AND (stores.app_cd = :app_cd_rs OR stores.app_cd = :app_cd_tors)';
                    $queryParams['app_cd_rs'] = config('code.gmServiceCd.rs');
                    $queryParams['app_cd_tors'] = config('code.gmServiceCd.tors');
                    $query .= ' AND T3.app_cd = "RS"';
                    break;
                // app_cdが指定されていない時　TO・RS・TORSを取得
                default:
                    $query .= ' AND (stores.app_cd = :app_cd_to OR stores.app_cd = :app_cd_rs OR stores.app_cd = :app_cd_tors)';
                    $queryParams['app_cd_to'] = config('code.gmServiceCd.to');
                    $queryParams['app_cd_rs'] = config('code.gmServiceCd.rs');
                    $queryParams['app_cd_tors'] = config('code.gmServiceCd.tors');
            }
            // app_cd(サービス利用コード)が指定されていない時は、app_cdがTORS、TO、RSのものだけを検索
        } else {
            $query .= ' AND (stores.app_cd = :app_cd_to OR stores.app_cd = :app_cd_rs OR stores.app_cd = :app_cd_tors)';
            $queryParams['app_cd_to'] = config('code.gmServiceCd.to');
            $queryParams['app_cd_rs'] = config('code.gmServiceCd.rs');
            $queryParams['app_cd_tors'] = config('code.gmServiceCd.tors');
        }

        // cookingGenreCd(料理ジャンル)が指定されている場合
        if (!empty($params['cookingGenreCd'])) {
            // level=3に紐づく店舗中ジャンル
            $query .= ' AND (T2.path LIKE :gpath AND T2.level = 3 AND T2.is_delegate = 1)';
            $queryParams['gpath'] = '/b-cooking/'.$params['cookingGenreCd'].'%';
        }

        // menuGenreCd(メニュージャンル)が指定されている場合
        if (!empty($params['menuGenreCd'])) {
            $query .= ' AND T2.genre_cd = :menu_genre_cd AND T2.is_delegate = 1';
            $queryParams['menu_genre_cd'] = $params['menuGenreCd'];
        }

        // 現在地検索 - $params['parameter']が指定されている場合(座標検索結果)
        $ids = null;
        if (isset($params['suggestCd']) && $params['suggestCd'] === config('code.suggestCd.current')) {
            $ids = array_keys($params['parameter']);
            $query .= ' AND stores.id IN ('.implode(', ', $ids).')';
            $isCurrentLoc = true;
        }

        // 駅検索 - $params['parameter']が指定されている場合(座標検索結果)
        $ids = null;
        if (isset($params['suggestCd']) && $params['suggestCd'] === config('code.suggestCd.station')) {
            $ids = array_keys($params['parameter']);
            $query .= ' AND stores.id IN ('.implode(', ', $ids).')';
        }

        // エリア検索
        if (isset($params['suggestCd']) && $params['suggestCd'] === config('code.suggestCd.area')) {
            // suggestCdとsuggestTextからエリア情報を取得
            $area_id = Area::GetAreaIdWithAreaCd($params['suggestText'])->first();
            $query .= ' AND area_id = :area_id';
            $queryParams['area_id'] = $area_id->id;
        }

        // 下限上限予算検索実装
        if (isset($params['zone'])) {
            // 昼予算
            if ($params['zone'] == 1) {
                // 下限予算
                if (isset($params['lowerPrice'])) {
                    $query .= ' AND daytime_budget_lower_limit >= :lowerPrice';
                    $queryParams['lowerPrice'] = $params['lowerPrice'];
                }
                // 上限予算
                if (isset($params['upperPrice'])) {
                    $query .= ' AND daytime_budget_limit <= :upperPrice';
                    $queryParams['upperPrice'] = $params['upperPrice'];
                }
            }

            // 夜予算
            if ($params['zone'] == 0) {
                // 下限予算
                if (isset($params['lowerPrice'])) {
                    $query .= ' AND night_budget_lower_limit >= :lowerPrice';
                    $queryParams['lowerPrice'] = $params['lowerPrice'];
                }
                // 上限予算
                if (isset($params['upperPrice'])) {
                    $query .= ' AND night_budget_limit <= :upperPrice';
                    $queryParams['upperPrice'] = $params['upperPrice'];
                }
            }
        }

        $query .= ' GROUP BY stores.id';

        \Log::debug($query);
        \Log::debug(print_r($queryParams, true));
        // 他の検索結果の店舗IDを取得
        $stores = \DB::select($query, $queryParams);

        /**
         * 在庫登録時に定休日を見ている場合はこの処理がいらなくなる ここから
         * 検索は処理が重くなるので、在庫登録時に定休日を見る処理を実装後削ってもOK。
         */
        // 店舗の定休日・営業時間＆営業曜日を確認
        if (isset($params['visitDate'])) { // 来店日が指定されていた場合
            $carbonVD = new Carbon($params['visitDate']); // 来店日
            $menuModel = new Menu();                           // Menuモデル
            $msg = null;                                  // メッセージ（必要無いけど店舗チェック用メソッドの引数として必要）
            $tmpStores = [];                              // 営業している店舗を一時的に格納する用
            foreach ($stores as $store) {
                // 店舗定休日を確認
                if (!$menuModel->checkFromWeek($store->regular_holiday, $carbonVD, 1, $msg)) {
                    continue;
                }

                if (isset($params['visitTime'])) { // 来店時間が指定されていた場合
                    $carbonVD = Carbon::parse($carbonVD->format('Y-m-d').' '.$params['visitTime']);

                    // 店舗営業時間＆営業曜日を確認
                    if (!$menuModel->checkOpeningHours($store->id, $carbonVD, $msg)) {
                        continue;
                    }
                }

                $tmpStores[] = $store;
            }
            $stores = $tmpStores;
        }
        // 在庫登録時に定休日を見ている場合はこの処理がいらなくなる ここまで

        // 検索結果に応じた店舗の中で、Ebica連携あり店舗のIDを絞り込む
        $ebicaStores = array_filter($stores, function ($row) {
            if ($row->api_store_id !== NULL) {
                return true;
            } else {
                return false;
            }
        });

        // Ebica連携あり店舗IDのみをユニークで配列化
        $ebicaStoreIds = array_unique(array_column($ebicaStores, 'id'));

        // 検索結果に応じた店舗の中で、Ebica連携なし店舗のIDを絞り込む
        $skyticketStores = array_filter($stores, function ($row) {
            if (is_null($row->api_store_id)) {
                return true;
            } else {
                return false;
            }
        });

        // Ebica連携なし店舗IDのみをユニークで配列化
        $skyticketStoreIds = array_unique(array_column($skyticketStores, 'id'));

        // すべての店舗IDのみをユニークで配列化
        $storeIds = array_unique(array_column($stores, 'id'));

        // 在庫確認検索(日付、時間、人数)
        if (isset($params['visitDate']) || isset($params['visitTime']) || isset($params['visitPeople'])) {
            $vacancyStoreIds = [];            // 検索で返す店舗IDを格納するための空配列
            $storeQuery = $query;             // 元のqueryを保存するために別変数に代入しておく
            $storeQueryParams = $queryParams; // 元の$queryParamsを保存するために別変数に代入しておく

            // // 来店時間が指定している場合は、前後30分の時間を取得（後の検索用クエリで使用）前後30分に空きがある場合は表示させる用
            // if (isset($params['visitTime'])) {
            //     $vTime = new Carbon($params['visitTime']);
            //     $vTimeMinusThirty = $vTime->copy()->subMinutes(30);
            //     $vTimePlusThirty = $vTime->copy()->addMinutes(30);
            // }

            // Ebica連携ありの店舗の場合（来店日が今日から3日以内、4日以降であってもEbicaから在庫を取得せず、Vacanciesテーブルから在庫を取得する） ここから
            if (!empty($ebicaStoreIds)) {
                $stockQuery = Vacancy::query(); // query準備

                // vacanciesテーブルのstockが1以上とstore_idで縛る
                $stockQuery->where('stock', '>=', 1)
                    ->whereIn('store_id', $ebicaStoreIds)
                    ->distinct()
                    ->select('store_id');

                // 来店日が指定されている場合は、来店日チェックを追加
                if (isset($params['visitDate'])) {
                    $stockQuery->where('date', $params['visitDate']);
                }

                // 来店時間が設定されている場合は、来店時間チェックを追加
                if (isset($params['visitTime'])) {
                    // // 前後30分に空きがある場合は表示させる用
                    // $stockQuery->whereBetween('time', [$vTimeMinusThirty->format('H:i'), $vTimePlusThirty->format('H:i')]);

                    // // 来店時間ピッタリしか在庫を表示させないパターン
                    $stockQuery->where('time', $params['visitTime']);
                }

                // 来店人数が指定されている場合は、来店人数チェックを追加
                if (isset($params['visitPeople'])) {
                    $stockQuery->where('headcount', $params['visitPeople']);
                }

                $vacancies = $stockQuery->get(); // 在庫取得

                // // 在庫がある場合は店舗IDを配列へ
                foreach ($vacancies as $vacancy) {
                    $vacancyStoreIds[] = $vacancy['store_id'];
                }
            }
            // Ebica連携ありの店舗の場合（来店日が今日から3日以内、4日以降であってもEbicaから在庫を取得せず、Vacanciesテーブルから在庫を取得する） ここまで

            // Ebica連携なし店舗の場合（Vacanciesテーブルから在庫を取得）ここから
            if (!empty($skyticketStoreIds)) {
                $stockQueryParams = [];
                $stockQuery = '
                    SELECT DISTINCT v.store_id
                    FROM vacancies v
                    WHERE v.is_stop_sale = 0
                    AND api_store_id is NULL
                    AND v.stock > 0
                ';

                // 来店日が指定されている場合は、来店日チェックを追加
                if (isset($params['visitDate'])) {
                    $stockQuery .= ' AND v.date = :visitDate';
                    $stockQueryParams['visitDate'] = $params['visitDate'];
                }

                // 来店時間が設定されている場合は、来店時間チェックを追加
                if (isset($params['visitTime'])) {
                    // // 前後30分に空きがある場合は表示させる用
                    // $stockQuery .= ' AND v.time BETWEEN :vTimeMinusThirty AND :vTimePlusThirty';
                    // $stockQueryParams['vTimeMinusThirty'] = $vTimeMinusThirty->format('H:i');
                    // $stockQueryParams['vTimePlusThirty'] = $vTimePlusThirty->format('H:i');

                    // 来店時間ピッタリしか在庫を表示させないパターン
                    $stockQuery .= ' AND v.time = :visitTime';
                    $stockQueryParams['visitTime'] = $params['visitTime'];
                }

                // 来店人数が指定されている場合、来店人数チェックを追加
                if (isset($params['visitPeople'])) {
                    $stockQuery .= ' AND v.headcount = :visitPeople';
                    $stockQueryParams['visitPeople'] = $params['visitPeople'];
                }

                // vacanciesテーブルからデータ取得
                $stockQuery .= ' AND v.store_id IN ('.implode(', ', $skyticketStoreIds).')';
                $stocks = \DB::select($stockQuery, $stockQueryParams);

                foreach ($stocks as $stock) {
                    $vacancyStoreIds[] = $stock->store_id;
                }
            }

            $vacancyStoreIds = array_unique($vacancyStoreIds); // 重複するstore_idを削除
            // Ebica連携なし店舗の場合（Vacanciesテーブルから在庫を取得）ここまで

            $query = $storeQuery;             // $queryを元の状態に戻す
            $queryParams = $storeQueryParams; // $queryParamsを元の状態に戻す
        }

        // 全検索結果の店舗IDを取得
        $storeIds = $vacancyStoreIds === 'none' ? $storeIds : $vacancyStoreIds;

        // こだわりジャンルが指定されている場合(※注意：こだわりジャンルの検索は最後に行うこと)
        if (!empty($params['details'])) {
            // 店舗IDのみをユニークで配列化
            $whereInStoreIds = array_unique(array_column($stores, 'id'));

            // クエリ準備
            $queryParams = [];
            $query = $masterQuery;

            // 生クエリだと「where in」はプレースホルダーにできないため、そのまま記述
            if (!empty($whereInStoreIds)) {
                $query .= ' AND T2.store_id IN ('.implode(', ', $whereInStoreIds).')';
            }

            // こだわりジャンルのgetパラメーターを配列化
            $arrayDetails = explode(',', $params['details']);
            $detailGenreInfos = [];

            // idからこだわりジャンル情報を取得
            foreach ($arrayDetails as $key => $arrayDetail) {
                $genreQuery = Genre::query();
                $detailGenreInfos[$key] = $genreQuery->find($arrayDetail);
            }

            // こだわり条件の複数条件検索は、クエリで検索することが不可能だったため、PHP側で実装
            $query .= ' AND (';
            foreach ($detailGenreInfos as $key => $info) {
                $query .= ' T2.genre_cd = :detail_genre_cd'.$key;
                $queryParams['detail_genre_cd'.$key] = $info['genre_cd'];
                // 最後のこだわりジャンルの場合以外ORを付ける
                if ($key !== count($detailGenreInfos) - 1) {
                    $query .= ' OR';
                }
            }
            $query .= ')';

            // レストラン再取得
            $stores = \DB::select($query, $queryParams);

            // 店舗IDを配列化
            $arrayStoreIds = array_column($stores, 'id');

            // 店舗IDの重複をカウント
            $arrayStoreIds = array_count_values($arrayStoreIds);

            $resultStoreIds = [];
            foreach ($arrayStoreIds as $store_id => $count) {
                // こだわり条件パラメーターの数と店舗IDのの重複が同じだった場合
                if ($count === count($detailGenreInfos)) {
                    // 店舗IDをwhereInの検索条件に入れる
                    $resultStoreIds[] = $store_id;
                }
            }
            $query = Store::Query();
            $query->whereIn('id', $resultStoreIds);
        } else {
            // レストラン取得
            $stores = \DB::select($query, $queryParams);
            $query = Store::Query();
            $query->whereIn('id', array_column($stores, 'id'));
        }

        // // 全件数を取得
        $count = $query->whereIn('id', $storeIds)->count();

        // 最終検索結果
        $finalResults = $query->orderBy('id', 'asc')->get();
        $storeIds = [];
        foreach ($finalResults as $finalResult) {
            $storeIds[] = $finalResult->id;
        }

        // レストランのみおすすめメニュー取得
        // 検索結果の店舗数が多い際に、おすすめメニューをレスポンスで返すためのforeach分でパフォーマンスの低下が顕著に現れる。
        // リリース前までにはチューニング必要かも。
        // 検索結果がある程度絞られている場合はそれほどスピード低下は見られない。
        if ($params['appCd'] === config('code.gmServiceCd.rs')) {
            $insert = [];
            foreach ($storeIds as $storeId) {
                // 来店日or翌日の日付で価格を決定
                $referenceDate = isset($params['visitDate']) ? $params['visitDate'] : (Carbon::tomorrow())->format('Y-m-d');
                $tmpRecommendMenu = [];

                // Menusテーブル＆Pricesテーブルの外部結合
                $tmpRecommendMenu = Menu::leftJoin('prices', 'menus.id', '=', 'prices.menu_id')
                    ->where('menus.store_id', $storeId)
                    ->where('menus.app_cd', config('code.gmServiceCd.rs'))
                    ->where('menus.published', 1)
                    ->whereNull('menus.deleted_at')
                    ->where('prices.start_date', '<=', $referenceDate)
                    ->where('prices.end_date', '>=', $referenceDate)
                    ->get();

                // レストランメニューがある場合
                if (!$tmpRecommendMenu->isEmpty()) {
                    // 店舗に紐づく全てのレストランメニュー内からランダムにおすすめを出すため、min&max間でランダムな整数を生成
                    $min = 0;
                    $max = $tmpRecommendMenu->count() === 0 ? 0 : $tmpRecommendMenu->count() - 1;
                    $randomInteger = mt_rand($min, $max);

                    // 取得情報をinsertに挿入
                    $tmpRecommendMenu = $tmpRecommendMenu->toArray();
                    $tmpRecommendMenu = $tmpRecommendMenu[$randomInteger];
                    $insert['id'] = $tmpRecommendMenu['menu_id'];
                    $insert['name'] = $tmpRecommendMenu['name'];
                    $insert['price']['id'] = $tmpRecommendMenu['id'];
                    $insert['price']['priceCd'] = $tmpRecommendMenu['price_cd'];
                    $insert['price']['price'] = $tmpRecommendMenu['price'];
                    $recommendMenu[$storeId] = $insert;

                // レストランメニューがない場合
                } else {
                    $recommendMenu[$storeId] = null;
                }
            }
        }

        // ジャンル系は仕様変更のため、レスポンスの実装なし。後に仕様変更された場合アンコメントして使用
        // // 検索結果に紐づくメニュージャンル取得
        // $tmpMenuGenres = GenreGroup::query();
        // $tmpMenuGenres = $tmpMenuGenres
        //     ->leftJoin('genres', 'genre_groups.genre_id', '=', 'genres.id')
        //     ->where('genres.path', 'like', '/'.config('const.genre.bigGenre.b-cooking.word').'%')
        //     ->whereIn('genre_groups.store_id', $storeIds)
        //     ->where('genre_groups.is_delegate', 1)
        //     ->groupBy('genre_groups.genre_id');
        // $menuGenres = $tmpMenuGenres->get();

        // // 検索結果に紐づくクッキングジャンル取得
        // $tmpCookingGenres = Genre::query();
        // $menuGenreIds = $tmpMenuGenres->pluck('genre_id');
        // $menuGenreCds = $tmpCookingGenres
        //     ->whereIn('id', $menuGenreIds)
        //     ->groupBy('path')
        //     ->pluck('path');
        // $arrayMenuGenres = [];
        // // pathを分解して、levelが2のgenre_cdを取得する
        // foreach ($menuGenreCds as $menuGenreCd) {
        //     $splitMenuGenre = explode('/', $menuGenreCd);
        //     $arrayMenuGenres[] = $splitMenuGenre[2];
        // }
        // $tmpCookingGenres = Genre::query();
        // $cookingGenres = $tmpCookingGenres
        //     ->whereIn('genre_cd', $arrayMenuGenres)
        //     ->orderBy('id')
        //     ->get();

        // // 利用サービスが選択されており、レストランorレストラン・テイクアウトの場合
        // if (!empty($params['appCd']) && ($params['appCd'] === config('code.gmServiceCd.rs') or $params['appCd'] === config('code.gmServiceCd.tors'))) {
        //     // 検索結果に紐づくこだわりジャンル(child)取得
        //     $tmpGenresChild = GenreGroup::query();
        //     $tmpGenresChild = $tmpGenresChild
        //         ->whereIn('store_id', $storeIds)
        //         ->groupBy('genre_id');
        //     $tmpDetailGenresChild = Genre::query();
        //     $detailGenreChildIds = $tmpGenresChild->pluck('genre_id');
        //     $detailGenresChild = $tmpDetailGenresChild
        //         ->whereIn('id', $detailGenreChildIds)
        //         ->where('path', 'like', '/'.config('const.genre.bigGenre.b-detailed.word').'%')
        //         ->get();

        //     // 全ての親こだわりジャンル取得
        //     $tmpDetailGenres = Genre::query();
        //     $detailGenres = $tmpDetailGenres
        //         ->where('path', '/'.config('const.genre.bigGenre.b-detailed.word'))
        //         ->get();
        //     $arrayDetailGenres = [];
        //     // pathを分解して、levelが2のgenre_cd(こだわりジャンル限定)を取得する
        //     foreach ($detailGenresChild as $detailGenreChild) {
        //         $splitDetailGenre = explode('/', $detailGenreChild['path']);
        //         $arrayDetailGenres[] = $splitDetailGenre[2];
        //     }
        //     // 検索結果に基づく親こだわりジャンル取得
        //     $tmpDetailGenres = Genre::query();
        //     $detailGenres = $tmpDetailGenres
        //         ->whereIn('genre_cd', $arrayDetailGenres)
        //         ->get();
        // }

        // お気に入り検索時はページングが不要なので飛ばす
        if(!$isFavorite){
            // 現在地検索時はページングが不要なので飛ばす
            if(!$isCurrentLoc){
                // ページングで取得
                $skip = !empty($params['page']) ? config('takeout.store.search.perPage') * ($params['page'] - 1) : 0;
                $query->skip($skip)->take(config('takeout.store.search.perPage'));
            }
        }

        return $query->get();
    }

    /**
     * おすすめレストラン取得.
     *
     * @param $params
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRecommendation($params)
    {
        $list = $this->_getRecommendation($params);
        foreach ($list as $store) {
            $store->image = $store->image()->where('image_cd', config('code.imageCd.restaurantLogo'))->first();
        }

        return $list;
    }

    public function _getRecommendation($params)
    {
        $query = Store::query();

        if (!empty($params['areaCd'])) {
            $query->where('area_id', Area::where('area_cd', $params['areaCd'])->first()->id);
        }
        if (!empty($params['stationCd'])) {
            $query->where('station_id', Station::where('station_cd', $params['stationCd'])->first()->id);
        }

        $stores = $query->where('published', 1)
            ->where(function ($q) {
                $q->where('app_cd', key(config('code.appCd.rs')))
                    ->orWhere('app_cd', key(config('code.appCd.tors')));
            })
            ->inRandomOrder()->take(10)->get();

        return $stores;
    }

    /**
     * 最低注文時間をDB保存のために時間と分から分のみにする
     */
    public function calcHourToMin(?Int $hours, ?Int $minutes): ?Int
    {
        $result = is_null($hours) ? null : (int) $hours * 60;
        $result = is_null($result) && is_null($minutes) ? null : $result + (int) $minutes;
        return $result;
    }

    /**
     * 最低注文時間をDB保存値から表示用に分から時間と分に分ける
     */
    public function calcMinToHour(?Int $param): Array
    {
        if (is_null($param)) {
            return [null, null];
        }
        $hours = (int) floor($param / 60);
        $hours = $hours === 0 ? null : $hours;
        $minutes = $param % 60;
        $minutes = is_null($minutes) ? null : $minutes;

        return [$hours, $minutes];

    }
}
