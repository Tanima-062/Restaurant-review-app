<?php

namespace App\Services;

use App\Models\Area;
use App\Models\CancelFee;
use App\Models\ExternalApi;
use App\Models\Favorite;
use App\Models\Genre;
use App\Models\GenreGroup;
use App\Models\Holiday;
use App\Models\Image;
use App\Models\Menu;
use App\Models\OpeningHour;
use App\Models\Price;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\Station;
use App\Models\Store;
use App\Models\Vacancy;
use App\Modules\Ebica\EbicaBase;
use App\Modules\Ebica\EbicaStockSave;
use Illuminate\Support\Carbon;
use App\Services\RestaurantReservationService;
use App\Services\RestaurantService;
use App\Modules\UserLogin;
use DateTime;
use Illuminate\Support\Facades\Lang;

class StoreService
{
    // ビュッフェ特集店舗のおすすめ店舗情報
    private $buffetRecommendStores = [
        'develop' => [
            // '店舗ID' => [おすすめ店舗ID,おすすめ店舗ID],
        ],
        'production' => [
            // '店舗ID' => [おすすめ店舗ID,おすすめ店舗ID],
        ]
    ];
    // ビュッフェ特集店舗(ebikaAPI連携店舗）の空席情報
    private $buffetEbicaStocks = [];

    public function __construct(
        Menu $menu,
        Store $store,
        Review $review,
        Image $image,
        GenreGroup $genreGroup,
        CancelFee $cancelFee,
        RestaurantReservationService $restaurantReservationService
    ) {
        $this->menu = $menu;
        $this->store = $store;
        $this->review = $review;
        $this->image = $image;
        $this->genreGroup = $genreGroup;
        $this->cancelFee = $cancelFee;
        $this->restaurantReservationService = $restaurantReservationService;
        $this->buffetRecommendStores['local'] = $this->buffetRecommendStores['develop'];
        $this->buffetRecommendStores['staging'] = $this->buffetRecommendStores['production'];
    }

    /**
     * 1店舗の情報を取得する.
     *
     * @param int storeId ストアID
     *
     * @return array
     */
    public function get(int $storeId)
    {
        $store = store::where('id', $storeId)->where('published', 1)->first();
        if (is_null($store)) {
            return null;
        }
        $res = [];
        $genres = $this->genreGroup->getCookingGenreByStoreId($storeId);
        if (!is_null($genres)) {
            foreach ($genres as $key => $genre) {
                $res['storeGenres'][$key]['id'] = $genre->id;
                $res['storeGenres'][$key]['name'] = $genre->name;
                $res['storeGenres'][$key]['genreCd'] = $genre->genre_cd;
                $res['storeGenres'][$key]['appCd'] = $genre->app_cd;
                $res['storeGenres'][$key]['path'] = $genre->path;
            }
        }

        $images = Image::weightImage($store->id);
        if (!is_null($images)) {
            foreach ($images as $key => $image) {
                $tmp = [];
                $tmp['id'] = $image->id;
                $tmp['imageCd'] = $image->image_cd;
                $tmp['imageUrl'] = $image->url;
                $res['topImages'][] = $tmp;
            }
        }

        $openingHours = $store->openingHours;
        if (!is_null($openingHours)) {
            foreach ($openingHours as $key => $openingHour) {
                $res['openingHours'][$key]['id'] = $openingHour->id;
                $res['openingHours'][$key]['name'] = $openingHour->image_cd;
                $res['openingHours'][$key]['week'] = $openingHour->week;
                $res['openingHours'][$key]['openTime'] = $openingHour->start_at;
                $res['openingHours'][$key]['closeTime'] = $openingHour->end_at;
                $res['openingHours'][$key]['openingHourCd'] = $openingHour->opening_hour_cd;
                $res['openingHours'][$key]['lastOrderTime'] = $openingHour->last_order_time;
            }
        }

        $evaluations = $this->review->getCountGroupedByEvaluationCd($store->id);
        $elemOfTotal = array_pop($evaluations);
        foreach ($evaluations as $evaluation) {
            $arr = [];
            $arr['evaluationCd'] = $evaluation->evaluation_cd;
            $arr['percentage'] = round(($evaluation->count * 100 / $elemOfTotal->count));
            $res['evaluations'][] = $arr;
        }

        $station = $store->stations;
        if (!is_null($station)) {
            $res['station'][$key]['id'] = $station->id;
            $res['station'][$key]['name'] = $station->name;
            $res['station'][$key]['latitude'] = $station->latitude;
            $res['station'][$key]['longitude'] = $station->longitude;
        }

        $res['id'] = $store->id;
        $res['name'] = $store->name;
        $res['aliasName'] = $store->alias_name;
        $res['address'] = $store->address_1 . ' ' . $store->address_2 . ' ' . $store->address_3;
        $res['postalCode'] = $store->postal_code;
        $res['tel'] = $store->tel;
        $res['telOrder'] = $store->tel_order;
        $res['latitude'] = $store->latitude;
        $res['longitude'] = $store->longitude;
        $res['email_1'] = $store->email_1;
        $res['daytimeBudgetLowerLimit'] = $store->daytime_budget_lower_limit;
        $res['daytimeBudgetLimit'] = $store->daytime_budget_limit;
        $res['access'] = $store->access;
        $res['account'] = $store->account;
        $res['remarks'] = $store->remarks;
        $res['description'] = $store->description;
        $res['fax'] = $store->fax;
        $res['useFax'] = $store->use_fax;
        $res['regularHoliday'] = $store->regular_holiday;
        $res['nightBudgetLowerLimit'] = $store->night_budget_lower_limit;
        $res['nightBudgetLimit'] = $store->night_budget_limit;
        $res['canCard'] = $store->can_card;
        $res['cardTypes'] = $store->card_types;
        $res['canDigitalMoney'] = $store->can_digital_money;
        $res['digitalMoneyTypes'] = $store->digital_money_types;
        $res['hasPrivateRoom'] = $store->has_private_room;
        $res['privateRoomTypes'] = $store->private_room_types;
        $res['hasParking'] = $store->has_parking;
        $res['hasCoinParking'] = $store->has_coin_parking;
        $res['numberOfSeats'] = $store->number_of_seats;
        $res['canCharter'] = $store->can_charter;
        $res['charterTypes'] = $store->charter_types;
        $res['smoking'] = $store->smoking;
        $res['smokingTypes'] = $store->smoking_types;

        if (UserLogin::isLogin()) {
            $user = UserLogin::getLoginUser();
            $ids = (new Favorite())->getFavoriteIds($user['userId'], key(config('code.appCd.rs')));
            $res['isFavorite'] = (in_array($store->id, $ids)) ? true : false;
        } else {
            $res['isFavorite'] = false;
        }

        return $res;
    }

    /**
     * 店舗に紐づくテイクアウトメニュー一覧を取得する.
     *
     * @param int storeId ストアID
     * @param string pickUpDate 受取日
     * @param string pickUpTime 受取時間
     *
     * @return array
     */
    public function getStoreTakeoutMenu(int $storeId, $pickUpDate, $pickUpTime)
    {
        $query = Menu::query();
        $query->where('store_id', $storeId)
            ->where('app_cd', key(config('code.appCd.to')))
            ->where('published', 1);

        $query->whereHas('menuPrices', function ($query) use ($pickUpDate) {
            $query->where('start_date', '<=', $pickUpDate)
                ->where('end_date', '>=', $pickUpDate);
        });

        $result = $query->get();

        $res = [];
        foreach ($result as $key => $menu) {
            $res[$key]['id'] = $menu->id;
            $res[$key]['name'] = $menu->name;
            $res[$key]['description'] = $menu->description;
            $stock = $menu->stock($pickUpDate);

            if (!is_null($stock)) {
                $res[$key]['stockNumber'] = $stock->stock_number;
            }
            $image = $menu->image(config('code.imageCd.menuMain'));
            if (!is_null($image)) {
                $res[$key]['thumbImage']['id'] = $image->id;
                $res[$key]['thumbImage']['imageCd'] = $image->image_cd;
                $res[$key]['thumbImage']['imageUrl'] = $image->url;
            }
            $price = $menu->menuPrice();
            if (!is_null($price)) {
                $res[$key]['price']['id'] = $price->id;
                $res[$key]['price']['priceCd'] = $price->price_cd;
                $res[$key]['price']['price'] = $price->price;
            }

            $genres = $menu->genres;
            if (!is_null($genres)) {
                foreach ($genres as $k => $v) {
                    $res[$key]['takeoutGenres'][$k]['id'] = $v->id;
                    $res[$key]['takeoutGenres'][$k]['name'] = $v->name;
                    $res[$key]['takeoutGenres'][$k]['genreCd'] = $v->genre_cd;
                    $res[$key]['takeoutGenres'][$k]['appCd'] = $v->app_cd;
                    $res[$key]['takeoutGenres'][$k]['path'] = $v->path;
                    $res[$key]['takeoutGenres'][$k]['level'] = $v->level;
                }
            }
        }

        return $res;
    }

    /**
     * 店舗に紐づくレストランメニュー一覧を取得する。（プラン一覧取得）
     *
     * ・日付未定の場合のリクエスト
     * $dateUndecided：true
     * $visitDate：指定された日付
     *
     * ・日付未定の場合のレスポンス
     * ①空席情報がない場合
     * ➝resutaurantMenusとstocksは空配列で返す
     *
     * ②空席情報がある場合
     * ➝restaurantMenus：空配列
     * ➝stocks：予約できる時間とその時間に紐付いた来店人数と在庫数をレスポンス
     *
     *
     * ・日付指定の場合のリクエスト
     * $dateUndecided：false
     * $visitDate：指定された日付
     * $visitTime：指定された時間
     * $visitPeople：指定された来店人数
     *
     * ・日付指定の場合のレスポンス
     * ①空席情報がない場合
     * ➝resutaurantMenusとstocksは空配列で返す
     *
     * ②空席情報があり、指定日時＆人数で予約できるメニューがある場合
     * ➝restaurantMenus：指定日時＆人数で予約できるメニュー
     * ➝stocks：予約できる時間とその時間に紐付いた来店人数と在庫数をレスポンス
     *
     * ③空席情報あるが、指定日時＆人数で予約できるメニューがない場合
     * ➝restaurantMenus：空配列（指定日時＆人数で予約できるメニューがないため）
     * ➝stocks：予約できる時間とその時間に紐付いた来店人数と在庫数をレスポンス
     *
     * ・メニューや在庫情報が返ってこない場合
     * ①メニューの価格は設定されているか
     * ②メニューに予約可能人数の下限上限が設定されている場合は、来店人数がその間に入っているか？（NULLの場合は、上限99人・下限1人）
     * ③メニューの予約可能時間のランチとディナーが設定されていないか確認
     * ➝ランチの時間のみ設定されていた場合は、その間の時間のみ販売可能
     * ➝ディナーの時間のみ設定されていた場合は、その間の時間のみ販売可能
     * ➝ランチ、ディナーがそれぞれの時間設定されていた場合は、それぞれの時間の間のみ販売可能
     * ➝ランチ、ディナーの販売可能時間設定が両方ともNULLの場合は、営業時間が販売時間となる
     * ※上記の注意点は、コース提供時間を考慮しなければならない
     * ④メニューの販売可能曜日等は設定されていないか
     * ⑤店舗の営業時間の設定はしてるか
     * ⑥ラストオーダー以降の時間は在庫情報にレスポンスしない（ラストオーダーの時間以降は予約できない）
     *
     *
     * @param int storeId ストアID
     * @param string visitDate 受取日
     *
     * @return array
     */
    public function getStoreRestaurantMenu(int $storeId, $visitDate, $visitTime, $visitPeople, $dateUndecided)
    {
        // 日付未定の場合、$visitTimeと$visitPeopleはNULL（使用しないため送られてきても処理しない）
        if ($dateUndecided == 'true') {
            $visitTime = NULL;
            $visitPeople = NULL;
        }

        $msg = NULL;                                // エラーメッセージ（現在レスポンスとして返してはいない）
        $response = [];                             // レスポンスの順番を整えるための格納用配列
        $res = [];                                  // レスポンス格納用配列
        $today = Carbon::today();                   // 今日
        $now = Carbon::now();                       // 現在日時
        $vDate = new Carbon($visitDate . '00:00:00'); // 来店日
        $vTime = new Carbon();                      // 来店時間（これはCarbon化するだけ。後にaddMinutesする）
        $dt = new Carbon($visitDate);               // 来店日(後に時間を設定)
        $extClosingTime = new Carbon();             // 最終予約可能時間
        $endAt = new Carbon();                      // 店舗の閉店時間
        $salesLunchEndTime = new Carbon();          // ランチ販売終了時間
        $salesDinnerEndTime = new Carbon();         // ディナー販売終了時間
        $resultMenus = [];                          // 予約できるメニュー（レスポンスで返す）
        $minLowerLimit = null;                      // 予約可能下限人数
        $maxUpperLimit = null;                      // 予約可能上限人数

        // 店舗IDと紐付いたEbica用店舗情報を取得
        $externalApi = ExternalApi::where('store_id', $storeId)->first();

        // 店舗情報取得
        $store = Store::find($storeId);

        // 店舗が存在していない場合、メニューも在庫も空で返す
        if (is_null($store)) {
            return $this->returnAllEmpty(7, $visitTime, $visitPeople, $msg);
        }

        // 店舗が公開されていない場合、メニューも在庫も空で返す
        if ($store->published != 1) {
            return $this->returnAllEmpty(6, $visitTime, $visitPeople, $msg);
        }

        // 営業時間
        $openingHours = OpeningHour::where('store_id', $storeId)->get();

        // 営業時間がない場合、メニューも在庫も空で返す
        if (is_null($openingHours)) {
            return $this->returnAllEmpty(10, $visitTime, $visitPeople, $msg);
        }

        // 店舗のメニュー情報取得
        $query = Menu::query();
        $menus = $query->where('store_id', $storeId)
            ->where('app_cd', key(config('code.appCd.rs')))
            ->where('published', 1)
            ->get();

        // メニューが何も登録されていない場合、メニューも在庫も空で返す
        if (is_null($menus)) {
            return $this->returnAllEmpty(11, $visitTime, $visitPeople, $msg);
        }

        // 価格が存在するかの判定を行い、ない場合はメニューをレスポンスしない
        $prices = [];
        foreach ($menus as $menuKey => $menuForPrice) {
            $price = $menuForPrice->menuPrice($visitDate);
            if (!is_null($price)) {
                $prices[$menuKey]['price_id'] = $price->id;
                $prices[$menuKey]['priceCd'] = $price->price_cd;
                $prices[$menuKey]['price'] = $price->price;
                $prices[$menuKey]['menu_id'] = $price->menu_id;
            }
        }
        $prices = array_merge($prices);

        // メニューに価格が何も登録されていない場合、メニューも在庫も空で返す
        if (empty($prices)) {
            return $this->returnAllEmpty(11, $visitTime, $visitPeople, $msg);
        }

        // 価格があるメニューを絞り込む
        $menusExistsPrice = [];
        foreach ($menus as $menuExistsPrice) {
            $resultPrice = null;
            $keyIndex = array_search($menuExistsPrice->id, array_column($prices, 'menu_id'));
            if ($keyIndex !== false) {
                $resultPrice = $prices[$keyIndex];
            }
            if (!is_null($resultPrice)) {
                $menusExistsPrice[] = $menuExistsPrice;
            }
            if (is_null($price)) {
                continue;
            }
        }

        // 価格があるメニューが１つもない場合は、メニューも在庫も空で返す
        if (empty($menusExistsPrice)) {
            return $this->returnAllEmpty(11, $visitTime, $visitPeople, $msg);
            // 価格があるメニューが１つでもある場合は、$menusを上書き
        } else {
            $menus = $menusExistsPrice;
        }

        // 指定した日付の祝日情報を取得
        $holiday = Holiday::where('date', $visitDate)->first();

        // 店舗の営業チェック：boolean
        $restaurantCheck = $this->restaurantCheck($holiday, $today, $vDate, $store, $msg);
        if (!$restaurantCheck) {
            return $this->returnAllEmpty(0, $visitTime, $visitPeople, $msg);
        }

        // リクエストされた日付にて、各メニューが提供可能日かチェック：メニュー情報
        $menuPreCheck = $this->menuPreCheck($dateUndecided, $today, $vDate, $visitDate, $visitPeople, $menus, $holiday, $msg);
        if (empty($menuPreCheck)) {
            return $this->returnAllEmpty(11, $visitTime, $visitPeople, $msg);
        } else {
            $menus = $menuPreCheck;
        }

        // // ここからの処理時間を計算するための処理(計算用に残し)
        // $time_start = microtime(true);

        // Ebica連携あり店舗の空席情報取得
        if (!is_null($externalApi)) {
            $addDay = 3;                                                // 3
            $fmtDate = Carbon::now()->addDay($addDay)->format('Y/m/d'); // 予約する日から$addDay(3)日後の日付
            $fmtVisitDate = new Carbon($visitDate);                     // visitDateをCarbon化
            $fmtVisitDate = $fmtVisitDate->format('Y/m/d');             // $visitDateを$fmtDateと比較するためにフォーマット

            // 来店日が3日以内である場合は、EbicaAPIから在庫取得
            if ($fmtDate > $fmtVisitDate) {
                $ebicaStoreId = $externalApi->api_store_id;                                                      // 指定店舗のEbica API用の店舗ID取得
                $ebicaStockSave = new EbicaStockSave();                                                          // インスタンス化
                $stocks = !is_null($ebicaStoreId) ? $ebicaStockSave->getStock($ebicaStoreId, $visitDate) : null; // Ebicaから在庫情報を取得
                $paramStocks = !is_null($stocks) ? $stocks : null;                                               // 引数で渡すための在庫

                // 在庫が未登録の場合は、メニューと在庫を空でレスポンス
                if (empty($stocks->stocks)) {
                    return $this->returnAllEmpty(13, $visitTime, $visitPeople, $msg);
                }

                // 在庫情報を一時的に入れる配列
                $inserts = [];
                foreach ($stocks->stocks as $key => $stocks) {
                    foreach ($stocks->stock as $stock) {
                        // 在庫が0の時はスキップ
                        if ($stock->sets <= 0) {
                            continue;
                        }

                        $arrayRsvTime = explode(':', $stock->reservation_time);
                        $dt->setTime($arrayRsvTime[0], $arrayRsvTime[1]);
                        $allCheckWithTime = $this->allCheckWithTime($dateUndecided, $visitDate, $visitTime, $visitPeople, $externalApi, $store, $openingHours, $paramStocks, $menus, $stocks, $stock, $holiday, $now, $dt, $extClosingTime, $endAt, $vTime, $salesLunchEndTime, $salesDinnerEndTime, $msg);

                        // 注文可能メニューの予約可能上限人数と予約可能下限人数を取得する(同じ処理が各Ebica連携あり３日以内＆４日以上＆スカチケ在庫店舗の判定の中にあるので編集する時は注意　※関数化もあり)
                        if (is_null($minLowerLimit) && is_null($maxUpperLimit) && $allCheckWithTime) {
                            // $this->getMinMaxLimit($allCheckWithTime);
                            foreach ($allCheckWithTime as $allCheck) {
                                // 予約可能メニューの中の予約可能下限人数がNULLのときは、予約可能下限人数を1人にする
                                if (is_null($allCheck->available_number_of_lower_limit)) {
                                    $minLowerLimit = 1;
                                }
                                // 予約可能メニューの中の予約可能上限人数がNULLのときは、予約可能上限人数を99人にする
                                if (is_null($allCheck->available_number_of_upper_limit)) {
                                    $maxUpperLimit = 99;
                                }
                                // 予約可能下限人数がNULLのときは、予約可能下限人数をすべての予約可能メニューの予約可能下限人数分にする
                                if (is_null($minLowerLimit)) {
                                    $minLowerLimit = min(array_column($allCheckWithTime, 'available_number_of_lower_limit'));
                                }
                                // 予約可能上限人数がNULLのときは、予約可能上限人数をすべての予約可能メニューの予約可能上限人数分にする
                                if (is_null($maxUpperLimit)) {
                                    $maxUpperLimit = max(array_column($allCheckWithTime, 'available_number_of_upper_limit'));
                                }
                            }
                        }

                        // 予約可能下限人数か予約可能上限人数がNULLの場合は、在庫を返さない
                        if (is_null($minLowerLimit) || is_null($maxUpperLimit)) {
                            continue;
                        }

                        // 来店人数が予約可能下限人数より少ないor予約可能上限人数より多い場合は、在庫を返さない
                        if ($minLowerLimit > $stocks->headcount || $maxUpperLimit < $stocks->headcount) {
                            continue;
                        }

                        // 日付指定＆在庫情報の時間と来店時間がイコール＆在庫情報の人数と来店人数がイコールの場合にメニュー情報を返すための処理
                        if ($dateUndecided === 'false' && $stock->reservation_time == $visitTime && $stocks->headcount == $visitPeople) {
                            $resultMenus = $allCheckWithTime;

                            if (!empty($resultMenus)) {
                                foreach ($resultMenus as $key => $resultMenu) {
                                    // メニューに対する価格の取得
                                    $keyIndex = array_search($resultMenu->id, array_column($prices, 'menu_id'));
                                    if ($keyIndex !== false) {
                                        $price = $prices[$keyIndex];
                                    }

                                    $response['restaurantMenu'][$key]['id'] = $resultMenu->id;
                                    $response['restaurantMenu'][$key]['name'] = $resultMenu->name;
                                    $response['restaurantMenu'][$key]['description'] = $resultMenu->description;
                                    $response['restaurantMenu'][$key]['providedTime'] = $resultMenu->provided_time;

                                    // 画像情報取得
                                    $image = $resultMenu->image(config('code.imageCd.menuMain'));
                                    // 画像がある場合はレスポンスに含める
                                    if (!is_null($image)) {
                                        $response['restaurantMenu'][$key]['thumbImage']['id'] = $image->id;
                                        $response['restaurantMenu'][$key]['thumbImage']['imageCd'] = $image->image_cd;
                                        $response['restaurantMenu'][$key]['thumbImage']['imageUrl'] = $image->url;
                                    }

                                    // 日付指定の場合の価格（日付に見合った価格をレスポンスする）
                                    if ($dateUndecided == 'false') {
                                        $response['restaurantMenu'][$key]['price']['id'] = $price['price_id'];
                                        $response['restaurantMenu'][$key]['price']['priceCd'] = $price['priceCd'];
                                        $response['restaurantMenu'][$key]['price']['price'] = $price['price'];
                                        // 日付未定の場合の価格
                                    } else {
                                        // すべての価格を取得
                                        $prices = $resultMenu->menuPrices()->get();

                                        // 価格がない場合NULLでレスポンス
                                        if (is_null($prices)) {
                                            $response['restaurantMenu'][$key]['price'] = NULL;
                                            // 価格が1つでもある場合は、今日or未来の価格の最安値をレスポンス（今日or未来の価格がない場合は、NULLでレスポンス）
                                        } else {
                                            $prices = $prices->toArray();   // 価格情報を配列化
                                            $priceStartDate = new Carbon(); // 価格が適用される日を先にcarbon化（後にsetDateTimeで入れる）
                                            $priceEndDate = new Carbon();   // 価格の適用が終わる日を先にcarbon化（後にsetDateTimeで入れる）

                                            foreach ($prices as $k => $value) {
                                                // 価格が適用される日
                                                $arrayPriceStartDate = explode('-', $value['start_date']);
                                                $priceStartDate->setDateTime($arrayPriceStartDate[0], $arrayPriceStartDate[1], $arrayPriceStartDate[2], 00, 00, 00);

                                                // 価格の適用が終わる日
                                                $arrayPriceEndDate = explode('-', $value['end_date']);
                                                $priceEndDate->setDateTime($arrayPriceEndDate[0], $arrayPriceEndDate[1], $arrayPriceEndDate[2], 00, 00, 00);

                                                // 価格終了日が今日より未来の場合、価格の配列に入れる
                                                if ($today <= $priceEndDate) {
                                                    $prices[$k]['id'] = $value['id'];
                                                    $prices[$k]['price_cd'] = $value['price_cd'];
                                                    $prices[$k]['price'] = $value['price'];
                                                    $prices[$k]['start_date'] = $value['start_date'];
                                                    $prices[$k]['end_date'] = $value['end_date'];
                                                    $prices[$k]['menu_id'] = $value['menu_id'];
                                                    // 価格終了日が今日より過去の場合は、価格の配列から削除
                                                } else {
                                                    array_splice($prices, $k);
                                                }
                                            }

                                            // 未来の価格がない場合は、価格をNULLでレスポンス
                                            if (empty($prices)) {
                                                $response['restaurantMenu'][$key]['price'] = NULL;
                                                // 未来の価格がある場合は、未来の価格の最安値をレスポンス
                                            } else {
                                                $prices = array_merge($prices);
                                                $minPrice = min(array_column($prices, 'price'));
                                                $price = [];
                                                foreach ($prices as $value) {
                                                    if ($value['price'] == $minPrice) {
                                                        $price["id"] = $value['id'];
                                                        $price["price_cd"] = $value['price_cd'];
                                                        $price["price"] = $value['price'];
                                                        $price["start_date"] = $value['start_date'];
                                                        $price["end_date"] = $value['end_date'];
                                                        $price["menu_id"] = $value['menu_id'];
                                                        break;
                                                    }
                                                }

                                                // オブジェクト型へキャスト（visitDateがある場合と処理を同じにするため）
                                                $price = (object) $price;

                                                $response['restaurantMenu'][$key]['price']['id'] = $price->id;
                                                $response['restaurantMenu'][$key]['price']['priceCd'] = $price->price_cd;
                                                $response['restaurantMenu'][$key]['price']['price'] = $price->price;
                                            }
                                        }
                                    }

                                    $response['restaurantMenu'][$key]['numberOfCourse'] = $resultMenu->number_of_course;
                                    $response['restaurantMenu'][$key]['availableNumberOfLowerLimit'] = $resultMenu->available_number_of_lower_limit;
                                    $response['restaurantMenu'][$key]['availableNumberOfUpperLimit'] = $resultMenu->available_number_of_upper_limit;

                                    // 飲み放題　あり`true`　なし`false`
                                    $response['restaurantMenu'][$key]['freeDrinks'] = $resultMenu->free_drinks === 1 ? true : false;

                                    // 席のみ予約可能か？　メニューの価格が0円の場合はtrue、1円以上ならfalse
                                    // 価格がない場合は、席のみ予約可能フラグはfalse
                                    if (!isset($price)) {
                                        $response['restaurantMenu'][$key]['onlySeat'] = false;
                                        // 価格の情報があり、0円の場合は、席のみ予約可能フラグはtrue
                                    } else {
                                        $response['restaurantMenu'][$key]['onlySeat'] = intval($price['price']) === 0 && !is_null($price['price']) ? true : false;
                                    }
                                }
                            }
                        }

                        // レスポンスに返すメニューがある場合、歯抜けになった配列番号を振り直す
                        if (isset($response['restaurantMenu'])) {
                            $response['restaurantMenu'] = array_merge($response['restaurantMenu']);
                        }

                        // 在庫レスポンス
                        if (!empty($allCheckWithTime)) {
                            $insert['vacancyTime'] = $stock->reservation_time;
                            $insert['people'] = $stocks->headcount;
                            $insert['sets'] = $stock->sets;

                            $inserts[] = $insert;
                        }
                    }
                    $response['stocks'] = $inserts;
                }
                $res['restaurantMenu'] = isset($response['restaurantMenu']) ? $response['restaurantMenu'] : [];
                $res['stocks'] = isset($response['stocks']) ? $response['stocks'] : [];
                // return $res;

                // 来店日が4日以降である場合、Vacanciesを見に行く
            } else {
                // Vacanciesテーブルから空席情報を取得
                $stocks = Vacancy::where('store_id', $storeId)
                    ->where('date', $visitDate)
                    ->get();

                // 在庫がない場合は、メニューと在庫情報を空でレスポンス
                if ($stocks->isEmpty()) {
                    return $this->returnAllEmpty(13, $visitTime, $visitPeople, $msg);
                }

                $paramStocks = null;

                $inserts = [];
                foreach ($stocks as $key => $stock) {
                    // 在庫が0の時はスキップ
                    if ($stock->stock <= 0) {
                        continue;
                    }

                    $arrayRsvTime = explode(':', $stock->time);
                    $dt->setTime($arrayRsvTime[0], $arrayRsvTime[1]);
                    $allCheckWithTime = $this->allCheckWithTime($dateUndecided, $visitDate, $visitTime, $visitPeople, $externalApi, $store, $openingHours, $paramStocks, $menus, $stocks, $stock, $holiday, $now, $dt, $extClosingTime, $endAt, $vTime, $salesLunchEndTime, $salesDinnerEndTime, $msg);

                    // 注文可能メニューの予約可能上限人数と予約可能下限人数を取得する(同じ処理が各Ebica連携あり３日以内＆４日以上＆スカチケ在庫店舗の判定の中にあるので編集する時は注意　※関数化もあり)
                    if (is_null($minLowerLimit) && is_null($maxUpperLimit) && $allCheckWithTime) {
                        foreach ($allCheckWithTime as $allCheck) {
                            // 予約可能メニューの中の予約可能下限人数がNULLのときは、予約可能下限人数を1人にする
                            if (is_null($allCheck->available_number_of_lower_limit)) {
                                $minLowerLimit = 1;
                            }
                            // 予約可能メニューの中の予約可能上限人数がNULLのときは、予約可能上限人数を99人にする
                            if (is_null($allCheck->available_number_of_upper_limit)) {
                                $maxUpperLimit = 99;
                            }
                            // 予約可能下限人数がNULLのときは、予約可能下限人数をすべての予約可能メニューの予約可能下限人数分にする
                            if (is_null($minLowerLimit)) {
                                $minLowerLimit = min(array_column($allCheckWithTime, 'available_number_of_lower_limit'));
                            }
                            // 予約可能上限人数がNULLのときは、予約可能上限人数をすべての予約可能メニューの予約可能上限人数分にする
                            if (is_null($maxUpperLimit)) {
                                $maxUpperLimit = max(array_column($allCheckWithTime, 'available_number_of_upper_limit'));
                            }
                        }
                    }

                    // 予約可能下限人数か予約可能上限人数がNULLの場合は、在庫を返さない
                    if (is_null($minLowerLimit) || is_null($maxUpperLimit)) {
                        continue;
                    }

                    // 来店人数が予約可能下限人数より少ないor予約可能上限人数より多い場合は、在庫を返さない
                    if ($minLowerLimit > $stock->headcount || $maxUpperLimit < $stock->headcount) {
                        continue;
                    }

                    // 日付指定＆在庫情報の時間と来店時間がイコール＆在庫情報の人数と来店人数がイコールの場合にメニュー情報を返すための処理
                    if ($dateUndecided === 'false' && $stock->time == $visitTime . ':00' && $stock->headcount == $visitPeople) {
                        $resultMenus = $allCheckWithTime;

                        if (!empty($resultMenus)) {
                            foreach ($resultMenus as $key => $resultMenu) {
                                // メニューに対する価格の取得
                                $keyIndex = array_search($resultMenu->id, array_column($prices, 'menu_id'));
                                if ($keyIndex !== false) {
                                    $price = $prices[$keyIndex];
                                }

                                $response['restaurantMenu'][$key]['id'] = $resultMenu->id;
                                $response['restaurantMenu'][$key]['name'] = $resultMenu->name;
                                $response['restaurantMenu'][$key]['description'] = $resultMenu->description;
                                $response['restaurantMenu'][$key]['providedTime'] = $resultMenu->provided_time;

                                // 画像情報取得
                                $image = $resultMenu->image(config('code.imageCd.menuMain'));
                                // 画像がある場合はレスポンスに含める
                                if (!is_null($image)) {
                                    $response['restaurantMenu'][$key]['thumbImage']['id'] = $image->id;
                                    $response['restaurantMenu'][$key]['thumbImage']['imageCd'] = $image->image_cd;
                                    $response['restaurantMenu'][$key]['thumbImage']['imageUrl'] = $image->url;
                                }

                                // 日付指定の場合の価格（日付に見合った価格をレスポンスする）
                                if ($dateUndecided == 'false') {
                                    $response['restaurantMenu'][$key]['price']['id'] = $price['price_id'];
                                    $response['restaurantMenu'][$key]['price']['priceCd'] = $price['priceCd'];
                                    $response['restaurantMenu'][$key]['price']['price'] = $price['price'];
                                    // 日付未定の場合の価格
                                } else {
                                    // すべての価格を取得
                                    $prices = $resultMenu->menuPrices()->get();

                                    // 価格がない場合NULLでレスポンス
                                    if (is_null($prices)) {
                                        $response['restaurantMenu'][$key]['price'] = NULL;
                                        // 価格が1つでもある場合は、今日or未来の価格の最安値をレスポンス（今日or未来の価格がない場合は、NULLでレスポンス）
                                    } else {
                                        $prices = $prices->toArray();   // 価格情報を配列化
                                        $priceStartDate = new Carbon(); // 価格が適用される日を先にcarbon化（後にsetDateTimeで入れる）
                                        $priceEndDate = new Carbon();   // 価格の適用が終わる日を先にcarbon化（後にsetDateTimeで入れる）

                                        foreach ($prices as $k => $value) {
                                            // 価格が適用される日
                                            $arrayPriceStartDate = explode('-', $value['start_date']);
                                            $priceStartDate->setDateTime($arrayPriceStartDate[0], $arrayPriceStartDate[1], $arrayPriceStartDate[2], 00, 00, 00);

                                            // 価格の適用が終わる日
                                            $arrayPriceEndDate = explode('-', $value['end_date']);
                                            $priceEndDate->setDateTime($arrayPriceEndDate[0], $arrayPriceEndDate[1], $arrayPriceEndDate[2], 00, 00, 00);

                                            // 価格終了日が今日より未来の場合、価格の配列に入れる
                                            if ($today <= $priceEndDate) {
                                                $prices[$k]['id'] = $value['id'];
                                                $prices[$k]['price_cd'] = $value['price_cd'];
                                                $prices[$k]['price'] = $value['price'];
                                                $prices[$k]['start_date'] = $value['start_date'];
                                                $prices[$k]['end_date'] = $value['end_date'];
                                                $prices[$k]['menu_id'] = $value['menu_id'];
                                                // 価格終了日が今日より過去の場合は、価格の配列から削除
                                            } else {
                                                array_splice($prices, $k);
                                            }
                                        }

                                        // 未来の価格がない場合は、価格をNULLでレスポンス
                                        if (empty($prices)) {
                                            $response['restaurantMenu'][$key]['price'] = NULL;
                                            // 未来の価格がある場合は、未来の価格の最安値をレスポンス
                                        } else {
                                            $prices = array_merge($prices);
                                            $minPrice = min(array_column($prices, 'price'));
                                            $price = [];
                                            foreach ($prices as $value) {
                                                if ($value['price'] == $minPrice) {
                                                    $price["id"] = $value['id'];
                                                    $price["price_cd"] = $value['price_cd'];
                                                    $price["price"] = $value['price'];
                                                    $price["start_date"] = $value['start_date'];
                                                    $price["end_date"] = $value['end_date'];
                                                    $price["menu_id"] = $value['menu_id'];
                                                    break;
                                                }
                                            }

                                            // オブジェクト型へキャスト（visitDateがある場合と処理を同じにするため）
                                            $price = (object) $price;

                                            $response['restaurantMenu'][$key]['price']['id'] = $price->id;
                                            $response['restaurantMenu'][$key]['price']['priceCd'] = $price->price_cd;
                                            $response['restaurantMenu'][$key]['price']['price'] = $price->price;
                                        }
                                    }
                                }

                                $response['restaurantMenu'][$key]['numberOfCourse'] = $resultMenu->number_of_course;
                                $response['restaurantMenu'][$key]['availableNumberOfLowerLimit'] = $resultMenu->available_number_of_lower_limit;
                                $response['restaurantMenu'][$key]['availableNumberOfUpperLimit'] = $resultMenu->available_number_of_upper_limit;

                                // 飲み放題　あり`true`　なし`false`
                                $response['restaurantMenu'][$key]['freeDrinks'] = $resultMenu->free_drinks === 1 ? true : false;

                                // 席のみ予約可能か？　メニューの価格が0円の場合はtrue、1円以上ならfalse
                                // 価格がない場合は、席のみ予約可能フラグはfalse
                                if (!isset($price)) {
                                    $response['restaurantMenu'][$key]['onlySeat'] = false;
                                    // 価格の情報があり、0円の場合は、席のみ予約可能フラグはtrue
                                } else {
                                    $response['restaurantMenu'][$key]['onlySeat'] = intval($price['price']) === 0 && !is_null($price['price']) ? true : false;
                                }
                            }
                        }
                    }

                    // レスポンスに返すメニューがある場合、歯抜けになった配列番号を振り直す
                    if (isset($response['restaurantMenu'])) {
                        $response['restaurantMenu'] = array_merge($response['restaurantMenu']);
                    }

                    // 在庫レスポンス
                    if (!empty($allCheckWithTime)) {
                        $insert['vacancyTime'] = mb_strlen($stock->time) === 8 ? mb_substr($stock->time, 0, 5) : $stock->time;
                        $insert['people'] = $stock->headcount;
                        $insert['sets'] = $stock->stock;

                        $inserts[] = $insert;
                    }
                }
                $response['stocks'] = $inserts;

                $res['restaurantMenu'] = isset($response['restaurantMenu']) ? $response['restaurantMenu'] : [];
                $res['stocks'] = isset($response['stocks']) ? $response['stocks'] : [];
                // return $res;
            }
            // Ebica連携なし店舗の場合
        } else {
            // Vacanciesテーブルから空席情報を取得
            $stocks = Vacancy::getVacancies($storeId, $visitDate)->get();

            // 在庫がない場合は、メニューと在庫情報を空でレスポンス
            if ($stocks->isEmpty()) {
                return $this->returnAllEmpty(13, $visitTime, $visitPeople, $msg);
            }

            $paramStocks = null;

            foreach ($stocks as $key => $stock) {
                // 在庫が0の時はスキップ
                if ($stock->stock <= 0) {
                    continue;
                }

                $arrayRsvTime = explode(':', $stock->time);
                $dt->setTime($arrayRsvTime[0], $arrayRsvTime[1]);
                $allCheckWithTime = $this->allCheckWithTime($dateUndecided, $visitDate, $visitTime, $visitPeople, $externalApi, $store, $openingHours, $paramStocks, $menus, $stocks, $stock, $holiday, $now, $dt, $extClosingTime, $endAt, $vTime, $salesLunchEndTime, $salesDinnerEndTime, $msg);

                // 注文可能メニューの予約可能上限人数と予約可能下限人数を取得する(同じ処理が各Ebica連携あり３日以内＆４日以上＆スカチケ在庫店舗の判定の中にあるので編集する時は注意　※関数化もあり)
                if (is_null($minLowerLimit) && is_null($maxUpperLimit) && $allCheckWithTime) {
                    foreach ($allCheckWithTime as $allCheck) {
                        // 予約可能メニューの中の予約可能下限人数がNULLのときは、予約可能下限人数を1人にする
                        if (is_null($allCheck->available_number_of_lower_limit)) {
                            $minLowerLimit = 1;
                        }
                        // 予約可能メニューの中の予約可能上限人数がNULLのときは、予約可能上限人数を99人にする
                        if (is_null($allCheck->available_number_of_upper_limit)) {
                            $maxUpperLimit = 99;
                        }
                        // 予約可能下限人数がNULLのときは、予約可能下限人数をすべての予約可能メニューの予約可能下限人数分にする
                        if (is_null($minLowerLimit)) {
                            $minLowerLimit = min(array_column($allCheckWithTime, 'available_number_of_lower_limit'));
                        }
                        // 予約可能上限人数がNULLのときは、予約可能上限人数をすべての予約可能メニューの予約可能上限人数分にする
                        if (is_null($maxUpperLimit)) {
                            $maxUpperLimit = max(array_column($allCheckWithTime, 'available_number_of_upper_limit'));
                        }
                    }
                }

                // 予約可能下限人数か予約可能上限人数がNULLの場合は、在庫を返さない
                if (is_null($minLowerLimit) || is_null($maxUpperLimit)) {
                    continue;
                }

                // 来店人数が予約可能下限人数より少ないor予約可能上限人数より多い場合は、在庫を返さない
                if ($minLowerLimit > $stock->headcount || $maxUpperLimit < $stock->headcount) {
                    continue;
                }

                // 日付指定＆在庫情報の時間と来店時間がイコール＆在庫情報の人数と来店人数がイコールの場合にメニュー情報を返すための処理
                if ($dateUndecided === 'false' && $stock->time == $visitTime . ':00' && $stock->headcount == $visitPeople) {
                    $resultMenus = $allCheckWithTime;
                    if (!empty($resultMenus)) {
                        foreach ($resultMenus as $key => $resultMenu) {
                            // メニューに対する価格の取得
                            $keyIndex = array_search($resultMenu->id, array_column($prices, 'menu_id'));
                            if ($keyIndex !== false) {
                                $price = $prices[$keyIndex];
                            }

                            $response['restaurantMenu'][$key]['id'] = $resultMenu->id;
                            $response['restaurantMenu'][$key]['name'] = $resultMenu->name;
                            $response['restaurantMenu'][$key]['description'] = $resultMenu->description;
                            $response['restaurantMenu'][$key]['providedTime'] = $resultMenu->provided_time;

                            // 画像情報取得
                            $image = $resultMenu->image(config('code.imageCd.menuMain'));
                            // 画像がある場合はレスポンスに含める
                            if (!is_null($image)) {
                                $response['restaurantMenu'][$key]['thumbImage']['id'] = $image->id;
                                $response['restaurantMenu'][$key]['thumbImage']['imageCd'] = $image->image_cd;
                                $response['restaurantMenu'][$key]['thumbImage']['imageUrl'] = $image->url;
                            }

                            // 日付指定の場合の価格（日付に見合った価格をレスポンスする）
                            if ($dateUndecided == 'false') {
                                $response['restaurantMenu'][$key]['price']['id'] = $price['price_id'];
                                $response['restaurantMenu'][$key]['price']['priceCd'] = $price['priceCd'];
                                $response['restaurantMenu'][$key]['price']['price'] = $price['price'];
                                // 日付未定の場合の価格
                            } else {
                                // すべての価格を取得
                                $prices = $resultMenu->menuPrices()->get();

                                // 価格がない場合NULLでレスポンス
                                if (is_null($prices)) {
                                    $response['restaurantMenu'][$key]['price'] = NULL;
                                    // 価格が1つでもある場合は、今日or未来の価格の最安値をレスポンス（今日or未来の価格がない場合は、NULLでレスポンス）
                                } else {
                                    $prices = $prices->toArray();   // 価格情報を配列化
                                    $priceStartDate = new Carbon(); // 価格が適用される日を先にcarbon化（後にsetDateTimeで入れる）
                                    $priceEndDate = new Carbon();   // 価格の適用が終わる日を先にcarbon化（後にsetDateTimeで入れる）

                                    foreach ($prices as $k => $value) {
                                        // 価格が適用される日
                                        $arrayPriceStartDate = explode('-', $value['start_date']);
                                        $priceStartDate->setDateTime($arrayPriceStartDate[0], $arrayPriceStartDate[1], $arrayPriceStartDate[2], 00, 00, 00);

                                        // 価格の適用が終わる日
                                        $arrayPriceEndDate = explode('-', $value['end_date']);
                                        $priceEndDate->setDateTime($arrayPriceEndDate[0], $arrayPriceEndDate[1], $arrayPriceEndDate[2], 00, 00, 00);

                                        // 価格終了日が今日より未来の場合、価格の配列に入れる
                                        if ($today <= $priceEndDate) {
                                            $prices[$k]['id'] = $value['id'];
                                            $prices[$k]['price_cd'] = $value['price_cd'];
                                            $prices[$k]['price'] = $value['price'];
                                            $prices[$k]['start_date'] = $value['start_date'];
                                            $prices[$k]['end_date'] = $value['end_date'];
                                            $prices[$k]['menu_id'] = $value['menu_id'];
                                            // 価格終了日が今日より過去の場合は、価格の配列から削除
                                        } else {
                                            array_splice($prices, $k);
                                        }
                                    }

                                    // 未来の価格がない場合は、価格をNULLでレスポンス
                                    if (empty($prices)) {
                                        $response['restaurantMenu'][$key]['price'] = NULL;
                                        // 未来の価格がある場合は、未来の価格の最安値をレスポンス
                                    } else {
                                        $prices = array_merge($prices);
                                        $minPrice = min(array_column($prices, 'price'));
                                        $price = [];
                                        foreach ($prices as $value) {
                                            if ($value['price'] == $minPrice) {
                                                $price["id"] = $value['id'];
                                                $price["price_cd"] = $value['price_cd'];
                                                $price["price"] = $value['price'];
                                                $price["start_date"] = $value['start_date'];
                                                $price["end_date"] = $value['end_date'];
                                                $price["menu_id"] = $value['menu_id'];
                                                break;
                                            }
                                        }

                                        // オブジェクト型へキャスト（visitDateがある場合と処理を同じにするため）
                                        $price = (object) $price;

                                        $response['restaurantMenu'][$key]['price']['id'] = $price->id;
                                        $response['restaurantMenu'][$key]['price']['priceCd'] = $price->price_cd;
                                        $response['restaurantMenu'][$key]['price']['price'] = $price->price;
                                    }
                                }
                            }

                            $response['restaurantMenu'][$key]['numberOfCourse'] = $resultMenu->number_of_course;
                            $response['restaurantMenu'][$key]['availableNumberOfLowerLimit'] = $resultMenu->available_number_of_lower_limit;
                            $response['restaurantMenu'][$key]['availableNumberOfUpperLimit'] = $resultMenu->available_number_of_upper_limit;

                            // 飲み放題　あり`true`　なし`false`
                            $response['restaurantMenu'][$key]['freeDrinks'] = $resultMenu->free_drinks === 1 ? true : false;

                            // 席のみ予約可能か？　メニューの価格が0円の場合はtrue、1円以上ならfalse
                            // 価格がない場合は、席のみ予約可能フラグはfalse
                            if (!isset($price)) {
                                $response['restaurantMenu'][$key]['onlySeat'] = false;
                                // 価格の情報があり、0円の場合は、席のみ予約可能フラグはtrue
                            } else {
                                $response['restaurantMenu'][$key]['onlySeat'] = intval($price['price']) === 0 && !is_null($price['price']) ? true : false;
                            }
                        }
                    }
                }

                // レスポンスに返すメニューがある場合、歯抜けになった配列番号を振り直す
                if (isset($response['restaurantMenu'])) {
                    $response['restaurantMenu'] = array_merge($response['restaurantMenu']);
                }

                // 在庫レスポンス
                if (!empty($allCheckWithTime)) {
                    $insert['vacancyTime'] = mb_strlen($stock->time) === 8 ? mb_substr($stock->time, 0, 5) : $stock->time;
                    $insert['people'] = $stock->headcount;
                    $insert['sets'] = $stock->stock;

                    $inserts[] = $insert;

                    $response['stocks'] = $inserts;
                }
                // $response['stocks'] = $inserts;
            }
            $res['restaurantMenu'] = isset($response['restaurantMenu']) ? $response['restaurantMenu'] : [];
            $res['stocks'] = isset($response['stocks']) ? $response['stocks'] : [];
        }

        // リクエストで送られてきた来店日時と来店人数に在庫がなかった場合は、リクエストで送られてきた来店時間と来店人数＆在庫0を在庫レスポンスする配列の中に入れる
        $isExistStock = array_filter($res['stocks'], function ($piece) use ($visitTime, $visitPeople) {
            if ($piece['vacancyTime'] === $visitTime && $piece['people'] === (int) $visitPeople) {
                return true;
            }
            return false;
        });
        if (empty($isExistStock) && !is_null($visitTime) && !is_null($visitPeople)) {
            $pushStock = [
                'vacancyTime' => $visitTime,
                'people' => (int) $visitPeople,
                'sets' => 0,
            ];
            array_unshift($res['stocks'], $pushStock);
        }

        // // 速度確認用（計算用に残し）
        // $time = microtime(true) - $time_start;
        // dd($time);

        return $res;
    }

    /**
     * 店舗に紐づく口コミ一覧を取得する.
     *
     * @param int storeId ストアID
     *
     * @return array
     */
    public function getStoreReview(int $storeId)
    {
        $res = [];

        $reviews = $this->review->getReviewsByStoreId($storeId);
        foreach ($reviews as $review) {
            $tmpReview = [];
            $tmpReview['id'] = $review->id;
            $tmpReview['userId'] = $review->user_id;
            $tmpReview['username'] = $review->user_name;
            $tmpReview['body'] = $review->body;
            $tmpReview['evaluationCd'] = $review->evaluation_cd;
            if (!is_null($review->images_id)) {
                $tmpReview['image']['id'] = $review->images_id;
                $tmpReview['image']['imageCd'] = $review->image_cd;
                $tmpReview['image']['imageUrl'] = $review->url;
            }
            $tmpReview['createdAt'] = $review->created_at;
            $res[] = $tmpReview;
        }

        return $res;
    }

    /**
     * 店舗に紐づく画像一覧を取得する.
     *
     * @param int storeId ストアID
     *
     * @return array
     */
    public function getStoreImage(int $storeId)
    {
        $res = [];
        $today = Carbon::now()->format('Y-m-d');

        // 全店舗画像取得(foodLogoとrestaurantLogo以外の店舗イメージ取得)
        $storeImages = Image::where('store_id', $storeId)
            ->where('image_cd', '<>', config('code.imageCd.foodLogo'))
            ->where('image_cd', '<>', config('code.imageCd.restaurantLogo'))
            ->get(['id', 'image_cd', 'url']);

        // 店舗画像のレスポンス整形
        foreach ($storeImages as $storeImage) {
            $tmpRes = [];
            $tmpRes['image']['id'] = $storeImage->id;
            $tmpRes['image']['imageCd'] = $storeImage->image_cd;
            $tmpRes['image']['imageUrl'] = $storeImage->url;
            $tmpRes['isPost'] = false;
            $res[] = $tmpRes;
        }

        // 全公開メニュー取得
        $menus = Menu::with('menuPrices')
            ->where('store_id', $storeId)
            ->where('published', 1)
            ->get();

        // 全メニューに紐づく画像取得
        $menuImages = Image::where('image_cd', config('code.imageCd.menuMain'))->whereIn('menu_id', $menus->pluck('id')->all())->get();

        // レビュー取得
        $reviews = Review::where('store_id', $storeId)->where('published', 1)->where('body', '!=', '')->get();

        // メニュー画像のレスポンス整形
        if ($menuImages) {
            foreach ($menuImages as $menuImage) {
                $tmpRes = [];
                $tmpRes['image']['id'] = $menuImage->id;
                $tmpRes['image']['imageCd'] = $menuImage->image_cd;
                $tmpRes['image']['imageUrl'] = $menuImage->url;
                $tmpRes['isPost'] = false;
                $menu = $menus->firstWhere('id', $menuImage->menu_id);
                if (!is_null($menu)) {
                    $menuPrice = $menu->menuPrices
                        ->where('start_date', '<=', $today)
                        ->where('end_date', '>=', $today)
                        ->firstWhere('menu_id', $menuImage->menu_id);
                    $tmpRes['menu']['id'] = $menu->id;
                    $tmpRes['menu']['name'] = $menu->name;
                    $tmpRes['menu']['appCd'] = $menu->app_cd;
                    if (!is_null($menuPrice)) {
                        $tmpRes['menu']['price']['id'] = $menuPrice->id;
                        $tmpRes['menu']['price']['priceCd'] = $menuPrice->price_cd;
                        $tmpRes['menu']['price']['price'] = $menuPrice->price;
                    }
                }
                $menuReviews = $reviews->where('menu_id', $menu->id);

                // レビューが1つでもあれば、レビュー情報をレスポンス
                if (!$menuReviews->isEmpty()) {
                    $tmpReviews = [];
                    foreach ($menuReviews as $menuReview) {
                        $tmpReview['id'] = $menuReview->id;
                        $tmpReview['userId'] = $menuReview->user_id;
                        $tmpReview['username'] = $menuReview->user_name;
                        $tmpReview['body'] = $menuReview->body;
                        $tmpReview['evaluationCd'] = $menuReview->evaluation_cd;
                        $tmpReview['createdAt'] = $menuReview->created_at;
                        $tmpReviews[] = $tmpReview;
                    }
                    $tmpRes['reviews'] = $tmpReviews;
                }
                $res[] = $tmpRes;
            }
        }

        $arrayReviewImages = $reviews->whereNotNull('image_id');                   // レビューの中で画像があるものを取得
        $reviewImageIds = array_column($arrayReviewImages->toArray(), 'image_id'); // 画像のあるレビューのIDを取得
        $reviewImages = Image::whereIn('id', $reviewImageIds)->get();              // レビューの画像を取得

        // レビュー画像のレスポンス整形
        foreach ($reviewImages as $reviewImage) {
            $tmpRes = [];
            $tmpRes['image']['id'] = $reviewImage->id;
            $tmpRes['image']['imageCd'] = $reviewImage->image_cd;
            $tmpRes['image']['imageUrl'] = $reviewImage->url;
            $tmpRes['isPost'] = true;
            $menu = $menus->firstWhere('id', $reviewImage->menu_id);
            if (!is_null($menu)) {
                $menuPrice = $menu->menuPrices
                    ->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today)
                    ->firstWhere('menu_id', $reviewImage->menu_id);
                $tmpRes['menu']['id'] = $menu->id;
                $tmpRes['menu']['name'] = $menu->name;
                $tmpRes['menu']['appCd'] = $menu->app_cd;
                if (!is_null($menuPrice)) {
                    $tmpRes['menu']['price']['id'] = $menuPrice->id;
                    $tmpRes['menu']['price']['priceCd'] = $menuPrice->price_cd;
                    $tmpRes['menu']['price']['price'] = $menuPrice->price;
                }
            }

            $review = $reviews->firstWhere('image_id', $reviewImage->id);
            $tmpRes['reviews']['id'] = $review->id;
            $tmpRes['reviews']['userId'] = $review->user_id;
            $tmpRes['reviews']['username'] = $review->user_name;
            $tmpRes['reviews']['body'] = $review->body;
            $tmpRes['reviews']['evaluationCd'] = $review->evaluation_cd;
            $tmpRes['reviews']['createdAt'] = $review->created_at;

            $res[] = $tmpRes;
        }
        return $res;
    }

    /**
     * ぱんクズ取得.
     *
     * @param int storeId Routeパラメータ：ストアID
     * @param array params GETパラメータ
     *
     * @return array
     */
    public function getBreadcrumb($storeId, $params)
    {
        $res = [];
        $res['elements'] = [];

        $query = Store::query();
        $query->where('id', $storeId);
        $store = $query->first();

        if (is_null($store)) {
            return $res;
        }

        // 店舗用パンクズ
        if (isset($params['isStore']) && filter_var($params['isStore'], FILTER_VALIDATE_BOOLEAN)) {
            // メインジャンル取得
            $query = Genre::query();
            $query->where('published', 1);
            $query->whereHas('genreGroups', function ($query) use ($storeId) {
                $query->where('store_id', $storeId)->where('is_delegate', 1);
            });
            $genre = $query->first();

            // 親ジャンル取得
            $tmpPathArray = explode('/', $genre->path);
            $query = Genre::query();
            $parentGenre = $query->where('published', 1)->where('genre_cd', $tmpPathArray[2])->first();

            // 店舗の大エリアを中エリアから取得
            $tmpPathArray = explode('/', $store->areas->path);
            $parentArea = Area::where('area_cd', $tmpPathArray[1])->where('published', 1)->first();

            // メイン親ジャンル
            $breadcrumb[0]['text'] = $parentGenre->name;
            $breadcrumb[0]['url'] = 'genre/b-cooking/' . $parentGenre->genre_cd;
            // メイン親ジャンル/大エリア
            $breadcrumb[1]['text'] = $parentArea->name . '/' . $parentGenre->name;
            $breadcrumb[1]['url'] = 'genre/' . $genre->genre_cd . '/am-' . $parentArea->area_cd;
            // メインジャンル/中エリア
            $breadcrumb[2]['text'] = $store->areas->name . '/' . $genre->name;
            $breadcrumb[2]['url'] = 'genre/' . $genre->genre_cd . '/am-' . $store->areas->area_cd;
            // メインジャンル/店名
            $breadcrumb[3]['text'] = $genre->name . '/' . $store->name;
            $breadcrumb[3]['url'] = 'sh-' . $store->code;
        }

        // 検索用パンクズ
        if (isset($params['isSearch']) && filter_var($params['isSearch'], FILTER_VALIDATE_BOOLEAN)) {
            $parentGenre = null;
            // cookingGenreCdで検索したら 和食で検索 = 和食
            if (!empty($params['cookingGenreCd'])) {
                $parentGenre = Genre::where('genre_cd', $params['cookingGenreCd'])->first();
                $genre = Genre::where('genre_cd', $params['cookingGenreCd'])->first();
                $breadcrumb[0]['text'] = $genre->name;
                $breadcrumb[0]['url'] = 'genre/b-cooking/' . $parentGenre->genre_cd;
            }
            if (!empty($params['suggestCd']) && !empty($params['suggenstText'])) {
                switch ($params['suggestCd']) {
                    case config('code.suggestCd.area'):
                        // エリアのみの検索
                        if (empty($params['cookingGenreCd']) && empty($params['menuGenreCd'])) {
                            $area = Area::where('name', $params['suggenstText'])->first();
                            $breadcrumb[0]['text'] = $area->name;
                            $breadcrumb[0]['url'] = 'area/ab-cooking/' . $area->area_cd;
                        }

                        // cookingGenreCdとsuggestCdとsuggenstTextで検索したら 和食と東京で検索 = 和食 > 東京 和食
                        if (!empty($params['cookingGenreCd'])) {
                            $area = Area::where('name', $params['suggenstText'])->first();
                            $breadcrumb[0]['text'] = $area->name;
                            $breadcrumb[0]['url'] = 'area/ab-cooking/' . $area->area_cd;
                            $breadcrumb[2]['text'] = $area->name . ' ' . $parentGenre->name;
                            $breadcrumb[2]['url'] = 'genre/' . $genre->genre_cd . '/am-' . $area->area_cd;
                        }

                        // menuGenreCdとsuggestCdとsuggenstTextで検索したら 寿司と東京で検索したら = 和食 > 寿司・魚 > 寿司 > 東京 寿司
                        if (!empty($params['menuGenreCd'])) {
                            $area = Area::where('name', $params['suggenstText'])->first();
                            $genre = Genre::where('genre_cd', $params['menuGenreCd'])->first();
                            $tmpPathArray = explode('/', $genre->path);
                            $parentGenre = Genre::where('genre_cd', $tmpPathArray[2])->first();

                            $breadcrumb[0]['text'] = $genre->name;
                            $breadcrumb[0]['url'] = 'genre/' . $genre->genre_cd;
                            $breadcrumb[1]['text'] = $genre->name;
                            $breadcrumb[1]['url'] = 'genre/b-cooking/' . $parentGenre->genre_cd . '/' . $genre->genre_cd;
                            $breadcrumb[2]['text'] = $parentGenre->name;
                            $breadcrumb[2]['url'] = 'genre/b-cooking/' . $parentGenre->genre_cd;
                            $breadcrumb[3]['text'] = $area->name . ' ' . $genre->name;
                            $breadcrumb[3]['url'] = 'genre/' . $genre->genre_cd . '/am-' . $area->area_cd;
                        }
                        break;

                        /*
                        case config('code.suggestCd.station'):
                            // cookingGenreCdとsuggestCdとsuggenstTextで検索したら 和食と恵比寿駅で検索 = 和食 > 東京 和食
                            if (!empty($params['cookingGenreCd'])) {
                                $station = Station::where('name', $params['suggenstText'])->first();
                                $breadcrumb[1]['text'] = $station->name.' '.$parentGenre->name;
                                $breadcrumb[1]['url'] = 'genre/'.$genre->genre_cd.'/st-'.$station->name;
                            }

                            // menuGenreCdとsuggestCdとsuggenstTextで検索したら 寿司と恵比寿駅で検索したら = 和食 > 寿司・魚 > 寿司 > 東京 寿司
                            if (!empty($params['menuGenreCd'])) {
                                $genre = Genre::where('genre_cd', $params['menuGenreCd'])->first();
                                $tmpPathArray = explode('/', $genre->path);
                                $parentGenre = Genre::where('genre_cd', $tmpPathArray[2])->first();
                                $station = Station::where('name', $params['suggenstText'])->first();

                                $breadcrumb[0]['text'] = $parentGenre->name;
                                $breadcrumb[0]['url'] = 'genre/b-cooking/'.$parentGenre->genre_cd;
                                $breadcrumb[1]['text'] = $genre->name;
                                $breadcrumb[1]['url'] = 'genre/b-cooking/'.$parentGenre->genre_cd.'/'.$genre->genre_cd;
                                $breadcrumb[2]['text'] = $station->name.' '.$genre->name;
                                $breadcrumb[2]['url'] = 'genre/'.$genre->genre_cd.'/am-'.$station->name;
                            }
                            break;
                        */
                    default:
                        return $res;
                }
            }
        }

        // ジャンル用パンクズ
        /*if (!empty($params['menuGenreCd'])) {
            $genre = Genre::where('genre_cd', $params['menuGenreCd'])->first();
            $tmpPathArray = explode('/', $genre->path);
            $parentGenre = Genre::where('genre_cd', $tmpPathArray[2])->first();
            $breadcrumb[0]['text'] = $genre->name;
            $breadcrumb[0]['url'] = 'genre/b-cooking/'.$parentGenre->genre_cd.'/'.$genre->genre_cd;
        } elseif (!empty($params['cookingGenreCd'])) {
            $genre = Genre::where('genre_cd', $params['cookingGenreCd'])->first();
            $breadcrumb[0]['text'] = $genre->name;
            $breadcrumb[0]['url'] = 'genre/b-cooking/'.$genre->genre_cd;
        }
        */
        // エリア用パンクズ

        foreach ($breadcrumb as $element) {
            $tmp['text'] = $element['text'];
            $tmp['url'] = $element['url'];
            $res['elements'][] = $tmp;
        }

        return $res;
    }

    /*
     * テイクアウト-店舗一覧取得.
     *
     * @param array params 検索パラメータ配列
     *
     * @return array
     */
    public function storeSearch($params)
    {
        \Log::debug('request params : ' . print_r($params, true));
        $storeList = $this->store->storeSearch($params);
        \Log::debug('storeList count : ' . $storeList['storeList']->count());

        $res = [];

        // 店舗一覧設定
        foreach ($storeList['storeList']->toArray() as $key => $store) {
            $tmp = [];
            $tmp['id'] = $store['id'];
            $tmp['name'] = $store['name'];
            $tmp['access'] = $store['access'];
            $tmp['daytimeBudgetLowerLimit'] = $store['daytime_budget_lower_limit'];
            $tmp['nightBudgetLowerLimit'] = $store['night_budget_lower_limit'];

            // // 仕様変更になったときのために残し
            // // メインジャンルのみ取得の場合
            // if (!empty($store['genre'])) {
            //     $tmp['storeGenre'] = [
            //         'id' => $store['genre']->genre->id,
            //         'name' => $store['genre']->genre->name,
            //         'genreCd' => $store['genre']->genre->genre_cd,
            //         'appCd' => $store['genre']->genre->app_cd,
            //         'path' => $store['genre']->genre->path,
            //     ];
            // }

            // 店舗IDと結びついたすべてのジャンルを取得する場合（複数ジャンル）
            if (!empty($store['genre'])) {
                $insert = [];
                foreach ($store['genre'] as $gKey => $genre) {
                    $insert[$gKey]['id'] = $genre->genre->id;
                    $insert[$gKey]['name'] = $genre->genre->name;
                    $insert[$gKey]['genreCd'] = $genre->genre->genre_cd;
                    $insert[$gKey]['appCd'] = $genre->genre->app_cd;
                    $insert[$gKey]['path'] = $genre->genre->path;
                    $insert[$gKey]['isDelegate'] = $genre->is_delegate;
                }
                $tmp['storeGenres'] = $insert;
            }

            // メインジャンルがない場合は空の配列を返す
            if (empty($store['genre'])) {
                $tmp['storeGenres'] = [];
            }

            // 画像がある時
            if (!empty($store['image'])) {
                $tmp['storeImage'] = [
                    'id' => $store['image']['id'],
                    'imageCd' => $store['image']['image_cd'],
                    'imageUrl' => $store['image']['url'],
                ];
            }

            // 画像がない場合は空の配列を返す
            if (empty($store['image'])) {
                $tmp['storeImage'] = [];
            }

            // おすすめメニュー（店舗app_cdがレストランのときのみ）
            // レストランメニューがある場合
            if ($params['appCd'] === config('code.gmServiceCd.rs')) {
                if (!empty($storeList['recommendMenu'][$store['id']])) {
                    $recommendMenu = $storeList['recommendMenu'][$store['id']];
                    $tmp['recommendMenu']['id'] = $recommendMenu['id'];
                    $tmp['recommendMenu']['name'] = $recommendMenu['name'];
                    $tmp['recommendMenu']['price']['id'] = $recommendMenu['price']['id'];
                    $tmp['recommendMenu']['price']['priceCd'] = $recommendMenu['price']['priceCd'];
                    $tmp['recommendMenu']['price']['price'] = $recommendMenu['price']['price'];
                }
                // レストランメニューがない場合
                if (is_null($storeList['recommendMenu'][$store['id']])) {
                    $tmp['recommendMenu'] = null;
                }
            }

            // 店舗までの距離
            $tmp['distance'] = isset($store['distance']) ? $store['distance'] : null;

            // 営業時間がある場合
            if (!empty($store['openingHours'])) {
                $insert = [];
                foreach ($store['openingHours'] as $ohKey => $openingHour) {
                    $insert[$ohKey]['id'] = $openingHour->id;
                    $insert[$ohKey]['openingTime'] = $openingHour->start_at;
                    $insert[$ohKey]['closeTime'] = $openingHour->end_at;
                    $insert[$ohKey]['code'] = $openingHour->opening_hour_cd;
                    $insert[$ohKey]['lastOrderTime'] = $openingHour->last_order_time;
                    $insert[$ohKey]['week'] = $openingHour->week;
                }
                $tmp['openinghours'] = $insert;
            }

            // 営業時間がない場合は空の配列を返す
            if (empty($store['openingHours'])) {
                $tmp['openingHours'] = [];
            }

            // 緯度・経度
            $tmp['latitude'] = $store['latitude'];
            $tmp['longitude'] = $store['longitude'];

            $tmp['appCd'] = $store['app_cd'];
            $tmp['lowerOrdersTime'] = $store['lower_orders_time'];
            $tmp['priceLevel'] = $store['price_level'];

            $res['stores'][] = $tmp;
        }

        // ジャンル系は仕様変更のため、レスポンスの実装なし。後に仕様変更された場合アンコメントして使用
        // // クッキングジャンル取得
        // if (!empty($storeList['cookingGenre'])) {
        //     foreach ($storeList['cookingGenre'] as $cookingGenre) {
        //         $tmp = [];
        //         $tmp['id'] = $cookingGenre->id;
        //         $tmp['name'] = $cookingGenre->name;
        //         $tmp['genreCd'] = $cookingGenre->genre_cd;
        //         $tmp['app_cd'] = $cookingGenre->app_cd;
        //         $tmp['path'] = $cookingGenre->path;

        //         $res['cookingGenres'][] = $tmp;
        //     }
        // }

        // // メニュージャンル取得
        // if (!empty($storeList['menuGenre'])) {
        //     foreach ($storeList['menuGenre'] as $menuGenre) {
        //         $tmp = [];
        //         $tmp['id'] = $menuGenre->genre->id;
        //         $tmp['name'] = $menuGenre->genre->name;
        //         $tmp['genreCd'] = $menuGenre->genre->genre_cd;
        //         $tmp['app_cd'] = $menuGenre->genre->app_cd;
        //         $tmp['path'] = $menuGenre->genre->path;

        //         $res['menuGenres'][] = $tmp;
        //     }
        // }

        // // こだわりジャンル取得
        // if (!empty($storeList['detailGenre']) && !empty($storeList['detailGenreChild'])) {
        //     // こだわりジャンル
        //     foreach ($storeList['detailGenre'] as $detailGenre) {
        //         $tmp = [];
        //         $tmp['id'] = $detailGenre->id;
        //         $tmp['name'] = $detailGenre->name;
        //         $tmp['genreCd'] = $detailGenre->genre_cd;
        //         $tmp['appCd'] = $detailGenre->app_cd;
        //         $tmp['path'] = $detailGenre->path;
        //         // こだわりジャンル(child)
        //         foreach ($storeList['detailGenreChild'] as $detailGenreChild) {
        //             $split = explode('/', $detailGenreChild['path']);
        //             if ($detailGenre['genre_cd'] === $split[2]) {
        //                 $tmpChild = [];
        //                 $tmpChild['id'] = $detailGenreChild->id;
        //                 $tmpChild['name'] = $detailGenreChild->name;
        //                 $tmpChild['genreCd'] = $detailGenreChild->genre_cd;
        //                 $tmpChild['appCd'] = $detailGenreChild->app_cd;
        //                 $tmpChild['path'] = $detailGenreChild->path;
        //                 $tmp['detailGenres']['detailGenresChild'][] = $tmpChild;
        //             }
        //         }
        //         $res['detailGenres'][] = $tmp;
        //     }
        // }

        $res['sumCount'] = $storeList['count'];
        $res['page'] = empty($params['page']) ? 1 : $params['page'];
        $res['pageMax'] = $storeList['pageMax'];

        return $res;
    }

    /*
     * キャンセルポリシー取得.
     *
     * @param int storeId 店舗ID
     * @param string appCd 利用サービス
     *
     * @return array
     */
    public function getCancelPolicy($storeId, $appCd)
    {
        $res = [];
        $publishedCancelFees = $this->cancelFee->getCancelPolicy($storeId, $appCd);
        if (!is_null($publishedCancelFees)) {
            foreach ($publishedCancelFees as $cancelFee) {
                $tmpCancelFee = [];
                if ($cancelFee->cancel_limit_unit === config('code.cancelPolicy.cancel_limit_unit.day')) {
                    $tmpCancelFee['beforeDay'] = $cancelFee->cancel_limit;
                } else {
                    $tmpCancelFee['beforeTime'] = $cancelFee->cancel_limit;
                }
                $tmpCancelFee['isAfter'] = ($cancelFee->visit === config('code.cancelPolicy.visit.after')) ? 1 : 0;
                $tmpCancelFee['cancelFee'] = $cancelFee->cancel_fee;
                $tmpCancelFee['cancelFeeUnit'] = $cancelFee->cancel_fee_unit;
                $res[] = $tmpCancelFee;
            }
        }

        return $res;
    }

    /**
     * 在庫状況からメニューが予約できるかを確認する
     */
    public function filterMenu($res, $visitDate, $visitTime, $visitPeople, $storeId, &$msg)
    {
        $restaurantMenus = [];
        $restaurantStocks = [];

        $visitAt = new Carbon($visitDate . ' ' . $visitTime); // 来店日時
        foreach ($res['restaurantMenu'] as $restaurantMenu) {
            $pushMenu = true;                                        // 来店日時と来店人数でメニューが予約できるかどうかの真偽値
            $menuEndAt = new Carbon($visitDate . ' ' . $visitTime);      // 来店日時
            $menuEndAt->addMinutes($restaurantMenu['providedTime']); // 来店日時＋メニュー提供時間（コース終了時間）

            foreach ($res['stocks'] as $stock) {
                // 在庫の来店人数とリクエストの来店人数が等しい場合＆在庫が1つ以上ある場合＆時間がメニュー開始時間とメニュー終了時間の間である
                if ($stock['people'] == $visitPeople && $stock['sets'] <= 0 && $stock['vacancyTime'] >= $visitAt->format('H:i:s') && $stock['vacancyTime'] <= $menuEndAt->format('H:i:s')) {
                    $pushMenu = false;
                }
            }
            if ($pushMenu) {
                $restaurantMenus[] = $restaurantMenu;
            }
        }

        if (empty($restaurantMenus)) {
            $msg = sprintf(Lang::get('message.weekFailure5'));
            $res['restaurantMenu'] = [];
            $res['stocks'] = [];
            return $res;
        }

        $carbonVisitDate = new Carbon($visitDate);

        $openingHours = OpeningHour::where('store_id', $storeId)->get(); // 開店時間

        // 予約時間からメニュー提供時間分の在庫チェック
        foreach ($res['stocks'] as $stock) {
            $openingHourResult = false; // 開店時間と閉店時間の間かの結果（デフォルトはfalse）
            foreach ($openingHours as $openingHour) {
                // 開店時間と閉店時間の間か判定
                if (strtotime($openingHour->start_at) <= strtotime($stock['vacancyTime']) && strtotime($stock['vacancyTime']) <= strtotime($openingHour->end_at)) {
                    $openingHourResult = true;
                    break;
                }
            }
            // 開店時間と閉店時間の間だった場合
            if ($openingHourResult) {
                // ラストオーダーよりあとの時間か判定し、あとだった場合、在庫に含めない
                if (strtotime($stock['vacancyTime']) > strtotime($openingHour->last_order_time)) {
                    continue;
                }
            }

            $result = true;
            $arrayStockVacancyTime = explode(':', $stock['vacancyTime']);
            $carbonVisitDate->setTime($arrayStockVacancyTime[0], $arrayStockVacancyTime[1], $arrayStockVacancyTime[2]);
            $carbonVisitDate->addMinutes(min(array_column($restaurantMenus, 'providedTime')));
            foreach ($res['stocks'] as $subStock) {
                // 来店人数が違う場合
                if ($stock['people'] != $subStock['people']) {
                    continue;
                }

                if ($stock['vacancyTime'] <= $subStock['vacancyTime'] && $carbonVisitDate->format('H:i:s') >= $subStock['vacancyTime'] && $subStock['sets'] <= 0) {
                    $result = false;
                }
            }
            if ($result) {
                $restaurantStocks[] = $stock;
            }
        }

        $res['restaurantMenu'] = $restaurantMenus;
        $res['stocks'] = $restaurantStocks;

        return $res;
    }

    /**
     * 在庫とメニューが空の場合の処理
     * 日付指定の場合、リクエストで送られてきた来店時間＆来店人数と在庫0で返す
     * エラーメッセージの更新
     */
    public function returnAllEmpty($type, $visitTime, $visitPeople, &$msg)
    {
        if (is_null($msg)) {
            $msg = sprintf(Lang::get('message.weekFailure' . $type));
        }

        // 日付未定の場合
        if (!is_null($visitTime) && !is_null($visitPeople)) {
            $res['restaurantMenu'] = [];
            $res['stocks'] = [
                0 => [
                    'vacancyTime' => $visitTime,
                    'people' => (int) $visitPeople,
                    'sets' => 0,
                ],
            ];
        }
        return $res;
    }

    /**
     * 店舗チェック
     * ➝指定日が今日より過去の日付でないかの確認
     * ➝指定日が店舗の定休日でないかを確認
     * ➝指定された日の曜日に営業時間があるかどうか判定(祝日の場合も判定可能)
     */
    public function restaurantCheck($holiday, $today, $vDate, $store, &$msg)
    {
        // 指定日が今日より過去の日付でないかの確認（メニューも在庫も空で返す）
        $checkTodayOrFuture = $this->checkTodayOrFuture($today, $vDate, $msg);

        // 指定日が店舗の定休日でないかを確認（メニューも在庫も空で返す）
        $storeHoliday = $this->checkFromWeek($holiday, $store->regular_holiday, $vDate, 1, $msg);

        // 指定された日の曜日に営業時間があるかどうか判定(祝日の場合も判定可能)（メニューも在庫も空で返す）
        $checkExistsOpeningHours = $this->menu->checkExistsOpeningHours($store->id, $vDate, 1, $msg);

        // すべてtrueであるならば店舗が営業している
        if ($checkTodayOrFuture && $storeHoliday && $checkExistsOpeningHours) {
            return true;
        }

        return false;
    }

    /**
     * メニューチェック
     * ➝1つでも指定日に提供可能なメニューがあるかの確認
     * ➝1つでも指定日の価格があるメニューがあるかの確認
     */
    public function menuPreCheck($dateUndecided, $today, $vDate, $visitDate, $visitPeople, $menus, $holiday, &$msg)
    {
        $result = [];
        // メニュー情報をループ
        foreach ($menus as $key => $menu) {
            // メニュー提供可能日か確認
            $checkFormWeek = $this->checkFromWeek($holiday, $menu->provided_day_of_week, $vDate, 2, $msg);
            if (!$checkFormWeek) {
                continue;
            }

            // 価格が存在するかの判定を行い、ない場合はメニューをレスポンスしない
            $price = $menu->menuPrice($visitDate);
            if (is_null($price)) {
                continue;
            }

            $result[] = $menu;
        }

        if (empty($result)) {
            return $result;
        }
        $msg = null;
        return $result;
    }

    /**
     * 店舗チェックとメニューチェックの中で、時間が必要だったものはこちらでチェック
     * 在庫で取得できたすべての時間をこのチェックに通す
     * ➝営業時間の確認
     * ➝現在の時刻にメニュー最低注文時間を足した時間と予約時間を比較し、メニュー一覧に含むかの確認
     * ➝メニュー提供時間か確認
     *
     * ※注意
     * この中でクエリ発行をすると速度が遅くなるので、DBに接続が必要な場合は、
     * このアクションを実行する前に接続し、データを取得したあと
     * 引数として渡す。
     * new Carbonなどのインスタンス化もしない方が良い。
     *
     * @param $dateUndecided      日付指定or日付未定（true:日付未定 false：日付指定）
     * @param $visitDate          来店日
     * @param $visitTime          来店時間
     * @param $visitPeople        来店人数
     * @param $externalApi        API接続情報
     * @param $store              店舗情報
     * @param $openingHours       営業時間情報
     * @param $paramStocks        在庫情報(Ebica連携店舗の加工なし在庫)
     * @param $menus              メニュー情報
     * @param $stocks             在庫情報
     * @param @stock              各時間の在庫情報（このアクションを実行する前にforeachでstocksを回している）
     * @param $holiday            祝日情報
     * @param $now                現在時刻
     * @param $dt                 来店日時
     * @param $extClosingTime     最終予約可能時間
     * @param $endAt              店舗の閉店時間
     * @param $vTime              来店時間（元引数の時は今日の日時をCarbon化しただけ。後にaddMinutesする）
     * @param $salesLunchEndTime  ランチ販売終了時間
     * @param $salesDinnerEndTime ディナー販売終了時間
     * @param $msg                エラーメッセージ
     *
     * @return Array $dtの時間で予約できるメニューをレスポンスで返す（ない場合は空配列でレスポンス）
     */
    public function allCheckWithTime($dateUndecided, $visitDate, $visitTime = null, $visitPeople = null, $externalApi, $store, $openingHours, $paramStocks, $menus, $stocks, $stock, $holiday, $now, $dt, $extClosingTime, $endAt, $vTime, $salesLunchEndTime, $salesDinnerEndTime, &$msg)
    {
        // 営業時間の確認
        $checkOpeningHours = $this->checkOpeningHours($holiday, $openingHours, $dt, $msg);

        // 現在の時刻にメニュー最低注文時間を足した時間と予約時間を比較し、注文できるメニューがあるかの確認
        $arrayPreMenus = $this->checkLowerOrderTime($menus, $now, $dt, $stock);

        if (!empty($arrayPreMenus)) {
            // メニュー提供時間か確認＆来店人数と来店可能人数のチェック
            $arrayMenus = $this->checkSalesTime($dateUndecided, $visitDate, $visitTime, $visitPeople, $store, $openingHours, $paramStocks, $stock, $arrayPreMenus, $holiday, $now, $dt, $extClosingTime, $endAt, $salesLunchEndTime, $salesDinnerEndTime, $msg);
        }

        // Ebica連携なし店舗かつ日付指定の場合は、在庫を面で見るためコース提供時間分の在庫があるかチェック
        if (!empty($arrayMenus) && $dateUndecided == 'false' && is_null($externalApi)) {
            $resultMenus = []; // 最後に返すメニューのための空配列

            foreach ($arrayMenus as $arrayMenu) {
                $canRsv = true; // 予約できるかの判定用 true：予約可能 false：予約不可

                foreach ($stocks as $value) {
                    if ($stock->headcount !== $value->headcount) {
                        continue;
                    }
                    $arrayStockTime = explode(':', $stock->time);
                    $stockTimePlusProvidedTime = $vTime->copy()->setTime($arrayStockTime[0], $arrayStockTime[1], $arrayStockTime[2]);
                    $stockTimePlusProvidedTime = $stockTimePlusProvidedTime->addMinutes($arrayMenu->provided_time);

                    if ($stock->time > $value->time) {
                        continue;
                    }

                    if ($stockTimePlusProvidedTime->format('H:i:s') < $value->time) {
                        continue;
                    }

                    // 在庫が0のときは販売できない
                    if ($value->stock <= 0) {
                        $canRsv = false;
                    }

                    // 売止の場合は販売できない
                    if ($value->is_stop_sale === 1) {
                        $canRsv = false;
                    }
                }

                if ($canRsv) {
                    $resultMenus[] = $arrayMenu;
                }
            }
            if (empty($resultMenus)) {
                $resultMenus = [];
            }
            $arrayMenus = $resultMenus;
        }

        if ($checkOpeningHours && !empty($arrayPreMenus) && !empty($arrayMenus)) {
            return $arrayMenus;
        }
        return [];
    }

    public function checkTodayOrFuture($today, $vDate, &$msg)
    {
        if ($today > $vDate) {
            $msg = sprintf(Lang::get('message.weekFailure12'));
            return false;
        }
        return true;
    }

    /**
     * メニュー提供可能日か確認
     * @return Boolean
     */
    public function checkFromWeek($holiday, $weeks, Carbon $now, $type, &$msg)
    {
        [$week, $weekName] = $this->menu->getWeek($now);
        // 祝日の場合
        if (!is_null($holiday)) {
            // 祝日が休業の場合
            if ($weeks[7] !== '1') {
                $msg = sprintf(Lang::get('message.weekFailure' . $type), $holiday->date);
                return false;
            }

            // 祝日が営業日の場合
            if ($weeks[7] === '1') {
                return true;
            }
        }

        if ($weeks[$week] !== '1') {
            $msg = sprintf(Lang::get('message.weekFailure' . $type), $weekName);

            return false;
        }

        return true;
    }

    public function checkOpeningHours($holiday, $openingHours, Carbon $now, &$msg)
    {
        $result = false;
        [$week, $weekName] = $this->menu->getWeek($now);

        foreach ($openingHours as $openingHour) {
            // 祝日の場合
            if (!is_null($holiday)) {
                // 祝日休みの場合
                if ($openingHour->week[7] !== '1') {
                    continue;
                }
                // 通常の日の場合
            } else {
                // 指定日の曜日が休みの場合
                if ($openingHour->week[$week] !== '1') {
                    continue;
                }
            }

            if (
                !(strtotime($now->copy()->format('H:i:s')) >= strtotime($openingHour->start_at) && strtotime($now->copy()->format('H:i:s')) < strtotime($openingHour->end_at))
            ) {
                continue;
            }
            $result = true;
            break;
        }
        if (!$result) {
            $msg = sprintf(Lang::get('message.weekFailure0'));
        }

        return $result;
    }

    /**
     * @param $now 予約可能日時
     * @param $dt  予約時間
     */
    public function checkLowerOrderTime($menus, $now, $dt, $stock)
    {
        $resultMenus = [];
        foreach ($menus as $menu) {
            $lowerOrdersTime = !is_null($menu->lower_orders_time) ? $menu->lower_orders_time : 0;

            $nowTime = $now->copy()->addMinutes($lowerOrdersTime);

            if ($dt >= $nowTime) {
                $resultMenus[] = $menu;
            }
        }

        return $resultMenus;
    }

    public function checkSalesTime($dateUndecided, $visitDate, $visitTime, $visitPeople, $store, $openingHours, $paramStocks, $stock, $menus, $holiday, $now, $dt, $extClosingTime, $endAt, $salesLunchEndTime, $salesDinnerEndTime, &$msg)
    {
        $resultMenus = []; // レスポンスするメニューを格納する空配列
        [$week, $weekName] = $this->menu->getWeek($dt);

        // 該当する営業時間の情報を取得
        foreach ($openingHours as $openingHour) {
            if ($openingHour->week[$week] !== '1') {
                continue;
            }
            if (!(strtotime($dt->format('H:i:s')) >= strtotime($openingHour->start_at)
                && strtotime($dt->format('H:i:s')) < strtotime($openingHour->end_at))) {
                continue;
            }
            // 祝日休みの場合は今日が祝日かどうかチェック
            if ($week === '7' && $openingHour->week[7] !== '1') {
                if (!is_null($holiday)) {
                    // 祝日のため休み
                    continue;
                }
            }
            $todayOpeningHour = $openingHour;
            break;
        }
        if (empty($todayOpeningHour)) {
            return $resultMenus;
        }

        foreach ($menus as $menu) {
            // returnMenuの説明
            // true➝メニューをレスポンスとして返す（$dtの時間に販売可能）
            // false➝メニューをレスポンスとして返さない（$dtの時間に販売不可）
            $returnMenu = true;

            // 日付指定だった場合
            if ($dateUndecided == 'false') {
                // 来店人数がメニューに対する予約可能人数の範囲内か確認
                $availableNumberOfLowerLimit = is_null($menu->available_number_of_lower_limit) || $menu->available_number_of_lower_limit < 0 ? 1 : $menu->available_number_of_lower_limit;
                $availableNumberOfUpperLimit = is_null($menu->available_number_of_upper_limit) || $menu->available_number_of_upper_limit > 99 ? 99 : $menu->available_number_of_upper_limit;
                if ($availableNumberOfLowerLimit > $visitPeople || $availableNumberOfUpperLimit < $visitPeople) {
                    continue;
                }
            }

            // 店舗の閉店時間から出した最終予約可能時間
            $arrayEndAt = explode(':', $openingHour->end_at);
            $endAt->setTime($arrayEndAt[0], $arrayEndAt[1], $arrayEndAt[2]);
            $skyStoreLastReservationTime = $endAt->copy()->subMinutes($menu->provided_time);

            // //ラストオーダーと営業終了時間の間ではないかチェック
            if (
                strtotime($dt->format('H:i:s')) > strtotime($todayOpeningHour->last_order_time)
                && strtotime($dt->format('H:i:s')) <= strtotime($todayOpeningHour->end_at)
            ) {
                $msg = \Lang::get('message.salesTimeCheckFaimure0');

                $returnMenu = false;
            }

            //メニュー最終予約可能時間の取得
            $skyMenuLastReservationTime = '';

            //メニューにランチ販売時間のみ入ってる場合
            if (!empty($menu->sales_lunch_end_time) && empty($menu->sales_dinner_end_time)) {
                //ランチ提供時間内かチェック
                if (!(strtotime($menu->sales_lunch_start_time) <= strtotime($dt->format('H:i:s'))
                    && strtotime($menu->sales_lunch_end_time) > strtotime($dt->format('H:i:s')))) {
                    $msg = \Lang::get('message.lunchTimeCheckFaimure');

                    $returnMenu = false;
                }
                // $salesLunchEndTime = new Carbon($menu->sales_lunch_end_time);
                $arrayLunchEndTime = explode(':', $menu->sales_lunch_end_time);
                $salesLunchEndTime = $salesLunchEndTime->setTime($arrayLunchEndTime[0], $arrayLunchEndTime[1]);
                $skyMenuLastReservationTime = $salesLunchEndTime->copy()->subMinutes($menu->provided_time);

                //メニューにディナー販売時間のみ入っている場合
            } elseif (!empty($menu->sales_dinner_end_time) && empty($menu->sales_lunch_end_time)) {
                //ディナー提供時間内かチェック
                if (!(strtotime($menu->sales_dinner_start_time) <= strtotime($dt->format('H:i:s'))
                    && strtotime($menu->sales_dinner_end_time) > strtotime($dt->format('H:i:s')))) {
                    $msg = \Lang::get('message.dinnerTimeCheckFaimure');

                    $returnMenu = false;
                }
                // $salesDinnerEndTime = new Carbon($menu->sales_dinner_end_time);
                $arrayDinnerEndTime = explode(':', $menu->sales_dinner_end_time);
                $salesDinnerEndTime = $salesDinnerEndTime->setTime($arrayDinnerEndTime[0], $arrayDinnerEndTime[1]);
                $skyMenuLastReservationTime = $salesDinnerEndTime->copy()->subMinutes($menu->provided_time);

                //メニューにランチ/ディナー販売時間の両方が入っている場合
            } elseif (!empty($menu->sales_lunch_end_time) && !empty($menu->sales_dinner_end_time)) {
                //該当する時間の確認

                //予約時間がランチ販売時間に該当した場合
                if (
                    strtotime($dt->format('H:i:s')) >= strtotime($menu->sales_lunch_start_time)
                    && strtotime($dt->format('H:i:s')) < strtotime($menu->sales_lunch_end_time)
                ) {
                    // $salesLunchEndTime = new Carbon($menu->sales_lunch_end_time);
                    $arrayLunchEndTime = explode(':', $menu->sales_lunch_end_time);
                    $salesLunchEndTime = $salesLunchEndTime->setTime($arrayLunchEndTime[0], $arrayLunchEndTime[1]);
                    $skyMenuLastReservationTime = $salesLunchEndTime->copy()->subMinutes($menu->provided_time);

                    //予約時間がディナー販売時間に該当した場合
                } elseif (
                    strtotime($dt->format('H:i:s')) >= strtotime($menu->sales_dinner_start_time)
                    && strtotime($dt->format('H:i:s')) < strtotime($menu->sales_dinner_end_time)
                ) {
                    // $salesDinnerEndTime = new Carbon($menu->sales_dinner_end_time);
                    $arrayDinnerEndTime = explode(':', $menu->sales_dinner_end_time);
                    $salesDinnerEndTime = $salesDinnerEndTime->setTime($arrayDinnerEndTime[0], $arrayDinnerEndTime[1]);
                    $skyMenuLastReservationTime = $salesDinnerEndTime->copy()->subMinutes($menu->provided_time);
                } else {
                    $msg = \Lang::get('message.salesTimeCheckFaimure0');

                    $returnMenu = false;
                }
            }

            //skyMenuLastReservationTimeが入っていた場合
            if (!empty($skyMenuLastReservationTime)) {
                //外部APIの営業終了時間を取得
                if (!empty($store->external_api) && !is_null($paramStocks)) {
                    $existExtClosingTime = true;
                    $closingTime = $this->getClosingTime($paramStocks);
                    $arrayClosingTime = explode(':', $closingTime);
                    $extClosingTime->setTime($arrayClosingTime[0], $arrayClosingTime[1]);
                } else {
                    $existExtClosingTime = false;
                }

                if ($existExtClosingTime) {
                    $extLastReservationTime = $extClosingTime->copy()->subMinutes($menu->provided_time);
                    //skyticketの最終予約可能時間と外部の最終予約可能時間を比較
                    //外部の最終予約可能時間の方が短かった場合
                    if (
                        strtotime($skyMenuLastReservationTime->format('H:i:s')) > strtotime($extLastReservationTime->format('H:i:s'))
                        && strtotime($skyStoreLastReservationTime->format('H:i:s')) > strtotime($extLastReservationTime->format('H:i:s'))
                    ) {
                        // \Log::info(sprintf('lastReservationTime(External) %s', $extLastReservationTime->format('H:i:s')));
                        $lastReservationTime = $extLastReservationTime;

                        //skyticketのメニュー最終予約可能時間の方が短かった場合
                    } elseif (
                        strtotime($extLastReservationTime->format('H:i:s')) > strtotime($skyMenuLastReservationTime->format('H:i:s'))
                        && strtotime($skyStoreLastReservationTime->format('H:i:s')) > strtotime($skyMenuLastReservationTime->format('H:i:s'))
                    ) {
                        // \Log::info(sprintf('lastReservationTime(SkyticketMenu) %s', $skyMenuLastReservationTime->format('H:i:s')));
                        $lastReservationTime = $skyMenuLastReservationTime;

                        //skyticketの店舗最終予約時間の方が短かった場合
                    } elseif (
                        strtotime($skyMenuLastReservationTime->format('H:i:s')) > strtotime($skyStoreLastReservationTime->format('H:i:s'))
                        && strtotime($extLastReservationTime->format('H:i:s')) > strtotime($skyStoreLastReservationTime->format('H:i:s'))
                    ) {
                        // \Log::info(sprintf('lastReservationTime(SkyticketStore) %s', $skyStoreLastReservationTime->format('H:i:s')));
                        $lastReservationTime = $skyStoreLastReservationTime;

                        //どこにも一致しなかった場合はメニューの時間を使う
                    } else {
                        // \Log::info(sprintf('lastReservationTime(SkyticketMenu) %s', $skyMenuLastReservationTime->format('H:i:s')));
                        $lastReservationTime = $skyMenuLastReservationTime;
                    }

                    //外部APIの接続がない場合
                } else {

                    //skyticketの店舗最終予約時間の方が短かった場合
                    if (strtotime($skyMenuLastReservationTime->format('H:i:s')) > strtotime($skyStoreLastReservationTime->format('H:i:s'))) {
                        // \Log::info(sprintf('lastReservationTime(SkyticketStore) %s', $skyStoreLastReservationTime->format('H:i:s')));
                        $lastReservationTime = $skyStoreLastReservationTime;
                        //skyticketのメニュー最終予約可能時間の方が短かった場合
                    } else {
                        // \Log::info(sprintf('lastReservationTime(SkyticketMenu) %s', $skyMenuLastReservationTime->format('H:i:s')));
                        $lastReservationTime = $skyMenuLastReservationTime;
                    }
                }

                //メニューの時間設定がない場合
            } else {
                //外部APIの営業終了時間を取得
                if (!empty($store->external_api) && !is_null($paramStocks)) {
                    $existExtClosingTime = true;
                    $closingTime = $this->getClosingTime($paramStocks);
                    $arrayClosingTime = explode(':', $closingTime);
                    $extClosingTime->setTime($arrayClosingTime[0], $arrayClosingTime[1]);
                } else {
                    $existExtClosingTime = false;
                }

                if ($existExtClosingTime) {
                    $extLastReservationTime = $extClosingTime->copy()->subMinutes($menu->provided_time);

                    // 外部の最終予約可能時間の方が短かった場合
                    if (strtotime($skyStoreLastReservationTime->format('H:i:s')) > strtotime($extLastReservationTime->format('H:i:s'))) {
                        // \Log::info(sprintf('lastReservationTime(External) %s', $extLastReservationTime->format('H:i:s')));
                        $lastReservationTime = $extLastReservationTime;

                        //skyticketの店舗最終予約時間の方が短かった場合
                    } else {
                        // \Log::info(sprintf('lastReservationTime(SkyticketStore) %s', $skyStoreLastReservationTime->format('H:i:s')));
                        $lastReservationTime = $skyStoreLastReservationTime;
                    }

                    //外部APIの接続がない場合
                } else {
                    // \Log::info(sprintf('lastReservationTime(SkyticketStore) %s', $skyStoreLastReservationTime->format('H:i:s')));
                    $lastReservationTime = $skyStoreLastReservationTime;
                }
            }

            //最終予約可能時間前かチェック
            if (!(strtotime($lastReservationTime->format('H:i:s')) >= strtotime($dt->format('H:i:s')))) {
                $msg = \Lang::get('message.salesTimeCheckFaimure0');

                $returnMenu = false;
            }

            //過去の時間ではないかチェック
            if (strtotime($dt->format('Y-m-d H:i:s')) < strtotime($now->format('Y-m-d H:i:s'))) {
                $msg = \Lang::get('message.pastOrderCheckFaimure');

                $returnMenu = false;
            }

            //最低注文時間のチェック
            if (!empty($menu->lower_orders_time)) {
                $lowerOrdersTime = $now->copy()->addMinutes($menu->lower_orders_time);
                if (strtotime($dt->format('Y-m-d H:i:s')) < strtotime($lowerOrdersTime->format('Y-m-d H:i:s'))) {
                    $msg = \Lang::get('message.lowerOrdersTimeCheckFaimure');

                    $returnMenu = false;
                }
            }

            if ($returnMenu) {
                $resultMenus[] = $menu;
            }
        }

        return $resultMenus;
    }

    public function getClosingTime($paramStocks)
    {
        $stocks = json_decode(json_encode($paramStocks), true);
        if (empty($paramStocks->stocks)) {
            return NULL;
        }
        $externalClosingTime = end(end($stocks['stocks'])['stock'])['reservation_time'];

        return $externalClosingTime;
    }

    /**
     * 指定した店舗のビュッフェ情報を取得する
     *
     * @param integer $storeId            店舗ID
     * @param integer $genreId            ジャンルID
     * @return array
     */
    public function getStoreBuffet(int $storeId, int $genreId)
    {
        $res = [];
        $storeInfo = [];
        $menus = [];
        $recommend = [];

        // 店舗情報取得、公開店舗か確認
        $store = Store::where('id', $storeId)->where('published', 1)->first();
        if (empty($store) || $store->published === 0) {
            return null;
        }
        $storeInfo['id'] = $storeId;
        $storeInfo['name'] = $store->name;
        $storeInfo['images'] = $this->getStoreImageForBuffet($storeId);

        // エリア情報を取得
        $areas = $store->areas;
        {
            $tmp = [];
            // 大エリア
            $parentPathArr = [];
            $path = explode('/', $areas->path);
            if (!in_array($path[1], $parentPathArr)) {
                $parentPathArr[] = $path[1];
                $parentAreas = Area::getParentAreas($parentPathArr);
                foreach ($parentAreas as $val) {
                    $tmp['bigAreaId'] = $val->id;
                    $tmp['bigAreaName'] = $val->name;
                    $tmp['bigAreaAreaCd'] = $val->area_cd;
                }
            }
            // 中エリア
            $tmp['middleAreaId'] = $areas->id;
            $tmp['middleAreaName'] = $areas->name;
            $tmp['middleAreaAreaCd'] = $areas->area_cd;
            $storeInfo['area'] = $tmp;
        }

        // 指定店舗の公開メニューを取得
        $StoreMenus = Menu::with([
            'genres',
            'menuPrices' => function ($query) {
                // price列が文字列の為、先頭0埋めの10桁にして並び替えてから一番安い価格データを取得
                $query->orderByRaw("lpad(`price`, 10, '0') asc");
            }
        ])->where('menus.store_id', $storeId)
            ->where('menus.published', 1)
            ->where('menus.buffet_lp_published', 1)
            ->orderBy('menus.id', 'asc')->get();

        if (!empty($StoreMenus) && $StoreMenus->count() > 0) {

            // 対象ジャンル情報を格納
            // 渡されたジャンルIDと配下のジャンルIDを取得
            $genre = Genre::where('id', $genreId)->first();
            $tagetGenres = [$genreId];
            $startGenres = Genre::getStartWithPath($genre->app_cd, $genre->genre_cd, $genre->level)->get();
            if (!empty($startGenres) && $startGenres->count() > 0) {
                $tagetGenres = array_merge($tagetGenres, $startGenres->pluck('id')->toArray());
            }

            foreach ($StoreMenus as $id => $menu) {

                // メニューに対象ジャンルが設定されているか確認
                $targetMenu = false;
                foreach ($menu->genres as $genre) {
                    if (in_array($genre->id, $tagetGenres)) {
                        $targetMenu = true;
                        break;
                    }
                }

                // 指定したジャンルがあればメニューの詳細情報を取得
                if ($targetMenu) {
                    $tmp = [];
                    $tmp['id'] = $menu->id;
                    $tmp['appCd'] = $menu->app_cd;
                    $tmp['name'] = $menu->name;
                    $tmp['description'] = $menu->description;
                    $tmp['plan'] = $menu->plan;
                    $tmp['salesLunchStartTime'] = $menu->sales_lunch_start_time;
                    $tmp['salesDinnerStartTime'] = $menu->sales_dinner_start_time;
                    $tmp['providedDayOfWeek'] = $menu->provided_day_of_week;

                    // 画像情報を取得
                    $menuImage = $menu->image();
                    if (!empty($menuImage)) {
                        $tmp['image'] = [
                            'id' => $menuImage->id,
                            'imageCd' => $menuImage->image_cd,
                            'imageUrl' => $menuImage->url,
                        ];
                    }

                    // 料金情報を取得（登録されている料金で一番安いもの）
                    if (!empty($menu->menuPrices)) {
                        $price = $menu->menuPrices->first();
                        $tmp['price'] = [
                            'id' => $price->id,
                            'priceCd' => $price->price_cd,
                            'price' => $price->price,
                            'startDate' => $price->start_date,
                            'endDate' => $price->end_date,
                        ];
                    }

                    // 最短利用可能日情報を取得
                    $tmp['shortestAvailableDate'] = $this->getShortestAvailableDateForMenu($storeId, $menu->id);

                    $menus[] = $tmp;
                }
            }
        }

        //　 おすすめ店舗情報の取得
        if (array_key_exists($storeId, $this->buffetRecommendStores[env('APP_ENV')])) {
            $recommendStores = $this->buffetRecommendStores[env('APP_ENV')][$storeId];
            if (is_array($recommendStores)) {
                $reccomendStores = Store::whereIn('id', $recommendStores)->where('published', 1)->get();
                foreach ($reccomendStores as $reccomendStore) {
                    if (!empty($reccomendStore)) {
                        $tmp = [];
                        $tmp['id'] = $reccomendStore->id;
                        $tmp['name'] = $reccomendStore->name;
                        $tmp['images'] = $this->getStoreImageForBuffet($reccomendStore->id);
                        $recommend[] = $tmp;
                    }
                }
            }
        }

        $res['store'] = $storeInfo;
        $res['menus'] = $menus;
        $res['recommend'] = $recommend;

        return $res;
    }

    /**
     * ビュッフェ特集-店舗画像情報取得
     *
     * @param integer $storeId  店舗ID
     * @return void
     */
    private function getStoreImageForBuffet(int $storeId) {
        $res = [];

        $storeImages = Image::where('store_id', $storeId)
        ->where('weight', '>', 0)
        ->orderBy('weight', 'desc')
        ->orderBy('id', 'asc')
        ->get(['id', 'image_cd', 'url']);

        // 店舗画像のレスポンス整形
        foreach ($storeImages as $storeImage) {
            $tmpImage = [];
            $tmpImage['id'] = $storeImage->id;
            $tmpImage['imageCd'] = $storeImage->image_cd;
            $tmpImage['imageUrl'] = $storeImage->url;
            $res[] = $tmpImage;
        }

        return $res;
    }

    /**
     * メニューの最短利用可能日を取得
     *
     * @param integer $storeId      店舗ID
     * @param integer $menuId      メニューID
     * @return void
     */
    private function getShortestAvailableDateForMenu(int $storeId, int $menuId)
    {
        $res = [];
        $store = Store::where('id', $storeId)->first();
        $menu = Menu::where('id', $menuId)->first();

        // 本日以降で有効なメニューの料金情報を取得
        $now = new Carbon();
        $prices = Price::MenuId($menuId)->where('end_date', '>=', $now->toDateString())->get();
        if ($prices->count() <= 0) {
            return $res;
        }

        // メニュー販売時間
        $menuSalesLunchStartTime = $menu->sales_lunch_start_time;
        $menuSalesLunchEndTime = $menu->sales_lunch_end_time;
        $menuSalesDinnerStartTime = $menu->sales_dinner_start_time;
        $menuSalesDinnerEndTime = $menu->sales_dinner_end_time;

        //　利用可能下限/上限人数
        $availableNumberOfLowerLimit = $menu->available_number_of_lower_limit;
        $availableNumberOfUpperLimit = $menu->available_number_of_upper_limit;

        // メニュー提供可能日
        $providedDayOfWeek = $menu->provided_day_of_week;
        $targetWeek = [];

        // メニュー注文最低時間
        $lowerOrdersTime = !is_null($menu->lower_orders_time) ? $menu->lower_orders_time : 0;

        // 祝日が営業可能か
        $providedDayHoliday = (substr($providedDayOfWeek, 7, 1) == 1) ? true : false;

        // 店舗IDと紐付いたEbica用店舗情報を取得
        $externalApis = ExternalApi::where('store_id', $storeId)->first();

        // Ebica連携あり店舗の空席情報取得
        if (!is_null($externalApis)) {

            if (substr($providedDayOfWeek, 0, 1) == 1) $targetWeek[] = 1;    //月曜提供可なら投入
            if (substr($providedDayOfWeek, 1, 1) == 1) $targetWeek[] = 2;    //火曜提供可なら投入
            if (substr($providedDayOfWeek, 2, 1) == 1) $targetWeek[] = 3;    //水曜提供可なら投入
            if (substr($providedDayOfWeek, 3, 1) == 1) $targetWeek[] = 4;    //木曜提供可なら投入
            if (substr($providedDayOfWeek, 4, 1) == 1) $targetWeek[] = 5;    //金曜提供可なら投入
            if (substr($providedDayOfWeek, 5, 1) == 1) $targetWeek[] = 6;    //土曜提供可なら投入
            if (substr($providedDayOfWeek, 6, 1) == 1) $targetWeek[] = 0;    //日曜提供可なら投入

            $ebicaStockSave = new EbicaStockSave();
            $apiShopId = Vacancy::getApiShopId($storeId)->first();
            $apiShopId = $apiShopId['api_store_id'];

            // 店舗の空席情報を取得
            $roundStocks = $this->buffetEbicaStocks;
            if (empty($roundStocks)) {
                // ebica連携し、空席日情報を取得
                $startDate = Carbon::now();                               // 検索基準日を今日に設定
                $addDay = 14;                                             // 検索範囲日数
                $since = $startDate->format('Y-m-d');                     // 検索開始日に検索基準日を設定
                $until = $startDate->addDay($addDay)->format('Y-m-d');    // 検索終了日に検索基準日から$addDay日後の日付を設定

                // 検索開始日から$addDay日分の空席情報を取得
                $roundStocks = $ebicaStockSave->getRoundedStock($apiShopId, $since, $until);

                // 使いまわせるようにクラス変数に空席情報を格納
                $this->buffetEbicaStocks = $roundStocks;
            }

            // 空席日からメニューの条件に一致する日時を探す
            if (!empty($roundStocks)) {
                foreach ($roundStocks as $stockDate) {
                    // 祝日かどうか確認
                    $holiday = Holiday::where('date', $stockDate->date)->first();

                    // メニュー提供対象曜日かを確認
                    if (in_array(date('w', strtotime($stockDate->date)), $targetWeek)) {
                        // 提供対象曜日であるが
                        // 祝日は提供不可設定、かつ、$stockDate->dateが祝日であれば、提供不可として判断。
                        if (!$providedDayHoliday && !empty($holiday)) {
                            continue;
                        }
                    } else {
                        // 提供対象曜日でないが、
                        // 祝日は提供可設定、かつ、$stockDate->dateが祝日であれば、提供可能として判断。それ以外は提供不可。
                        if (!($providedDayHoliday && !empty($holiday))) {
                            continue;
                        }
                    }

                    // $stockDate->dateがメニュー提供期間内であれば、提供可能として判断。
                    $targetDate = new Carbon($stockDate->date . '00:00:00');
                    foreach ($prices as $price) {
                        $startDate = new Carbon($price->start_date . '00:00:00');
                        $endDate = new Carbon($price->end_date . '23:59:59');
                        if (!($startDate->lte($targetDate) && $endDate->gte($targetDate))) {
                            continue 2;
                        }
                    }

                    // ebica連携し、空席時間情報を取得
                    $stockDatas = !is_null($apiShopId) ? $ebicaStockSave->getStock($apiShopId, $stockDate->date) : null;
                    if (!empty($stockDatas->stocks)) {
                        foreach ($stockDatas->stocks as $stocks) {
                            /* $stocksのなかみ
                            [
                                [
                                    [headcount] => 1,
                                    [stock] => [
                                        [0] => ['reservation_time' => 09:00, 'sets' => 0, 'duration' => 90],
                                        [1] => ['reservation_time' => 09:30, 'sets' => 0, 'duration' => 90],
                                        :
                                    ],
                                [
                                    [headcount] => 2,
                                    [stock] => [
                                        [0] => ['reservation_time' => 09:00, 'sets' => 0, 'duration' => 90],
                                        :
                                    ],
                                ],
                                :
                            ] */

                            // 利用可能下限人数超えているか確認
                            if (!empty($availableNumberOfLowerLimit)) {
                                if (!($stocks->headcount >= $availableNumberOfLowerLimit)) {
                                    continue;
                                }
                            }

                            // 利用可能上限人数を超えていないか確認
                            if (!empty($availableNumberOfUpperLimit)) {
                                if (!($stocks->headcount <= $availableNumberOfLowerLimit)) {
                                    continue;
                                }
                            }

                            $collectionStock = collect($stocks->stock)
                                ->where('sets', '>', '0')   // 空席0は除く
                                ->first(function ($value, $key)
                                    use ($menuSalesLunchStartTime, $menuSalesLunchEndTime, $menuSalesDinnerStartTime, $menuSalesDinnerEndTime) {

                                    $stockDateTime = new DateTime($value->reservation_time);
                                    $salesLunchStartDateTime = new DateTime($menuSalesLunchStartTime);
                                    $salesLunchEndDateTime = new DateTime($menuSalesLunchEndTime);
                                    $salesDinnerStartDateTime = new DateTime($menuSalesDinnerStartTime);
                                    $salesDinnerEndDateTime = new DateTime($menuSalesDinnerEndTime);
                                    $sales = false;

                                    // 候補時間がメニューの販売時間内か確認していく
                                    // ランチ販売時間内
                                    if (!empty($menuSalesLunchStartTime) &&
                                        ($stockDateTime >=  $salesLunchStartDateTime && $stockDateTime <= $salesLunchEndDateTime)) {
                                        $sales = true;
                                    }

                                    // ディナー販売時間内
                                    if (!$sales && !empty($menuSalesLunchStartTime) &&
                                        ($stockDateTime >=  $salesDinnerStartDateTime && $stockDateTime <= $salesDinnerEndDateTime)) {
                                        $sales = true;
                                    }

                                    return ($sales)? $value : false;
                            });
                            if (!empty($collectionStock)) {
                                    $res['date'] = $stockDate->date;
                                    $res['time'] = $collectionStock->reservation_time;
                                    $res['headcount'] = $stocks->headcount;
                                    break 2;
                            }
                        }
                    }
                }
            }

        } else {

            if (substr($providedDayOfWeek, 0, 1) == 1) $targetWeek[] = 2;    //月曜提供可なら投入
            if (substr($providedDayOfWeek, 1, 1) == 1) $targetWeek[] = 3;    //火曜提供可なら投入
            if (substr($providedDayOfWeek, 2, 1) == 1) $targetWeek[] = 4;    //水曜提供可なら投入
            if (substr($providedDayOfWeek, 3, 1) == 1) $targetWeek[] = 5;    //木曜提供可なら投入
            if (substr($providedDayOfWeek, 4, 1) == 1) $targetWeek[] = 6;    //金曜提供可なら投入
            if (substr($providedDayOfWeek, 5, 1) == 1) $targetWeek[] = 7;    //土曜提供可なら投入
            if (substr($providedDayOfWeek, 6, 1) == 1) $targetWeek[] = 1;    //日曜提供可なら投入

            // EbicaAPI連携なし店舗の空席情報取得
            $vacancies = Vacancy::where('store_id', $storeId)
                ->where('stock', '>', '0')
                ->where('is_stop_sale', '=', '0')
                ->whereRaw("cast(CONCAT(date , ' ', time) as datetime) > cast(DATE_ADD(NOW(), INTERVAL " . $lowerOrdersTime . " * 60 SECOND) as datetime)"); // 現時刻からメニュー注文最低時間以上の空席情報を取得

            // メニューの提供期間を条件に設定
            $vacancies = $vacancies->where(function ($query) use ($prices) {
                foreach ($prices as $price) {
                    $query->orWhere(function ($query1) use ($price) {
                        $query1->where('date', '>=', $price->start_date)
                            ->where('date', '<=', $price->end_date);
                    });
                }
            });

            // 利用可能下限人数を条件に設定
            if (!empty($availableNumberOfLowerLimit)) {
                $vacancies = $vacancies->where('headcount', '>=', $availableNumberOfLowerLimit);
            }

            // 利用可能上限人数を条件に設定
            if (!empty($availableNumberOfUpperLimit)) {
                $vacancies = $vacancies->where('headcount', '<=', $availableNumberOfLowerLimit);
            }

            //　提供時間を条件に設定
            if (!empty($menuSalesLunchStartTime) && !empty($menuSalesDinnerStartTime)) {
                // 提供時間がランチとディナー両方に設定されている
                // where (( date >= ランチ提供開始時間) and date < ランチ提供終了時間)
                //   or   ( date >= ディナー提供開始時間) and date < ディナー提供終了時間 ))
                $vacancies = $vacancies->where(function ($query) use ($menuSalesLunchStartTime, $menuSalesLunchEndTime, $menuSalesDinnerStartTime, $menuSalesDinnerEndTime) {
                    $query->orWhere(function ($query2) use ($menuSalesLunchStartTime, $menuSalesLunchEndTime) {
                        $query2->whereRaw("TIME_TO_SEC(time) >= TIME_TO_SEC('" . $menuSalesLunchStartTime . "')")
                            ->whereRaw("TIME_TO_SEC(time) < TIME_TO_SEC('" . $menuSalesLunchEndTime . "')");
                    })
                        ->orWhere(function ($query2) use ($menuSalesDinnerStartTime, $menuSalesDinnerEndTime) {
                            $query2->whereRaw("TIME_TO_SEC(time) >= TIME_TO_SEC('" . $menuSalesDinnerStartTime . "')")
                                ->whereRaw("TIME_TO_SEC(time) < TIME_TO_SEC('" . $menuSalesDinnerEndTime . "')");
                        });
                });
            } else if (!empty($menuSalesLunchStartTime) || !empty($menuSalesDinnerStartTime)) {
                // 提供時間がランチかディナーいづれかに設定されている
                // where date >= ランチ提供開始時間) and date < ランチ提供終了時間
                $startTime = (!empty($menuSalesLunchStartTime)) ? $menuSalesLunchStartTime : $menuSalesDinnerStartTime;
                $endTime = (!empty($menuSalesLunchEndTime)) ? $menuSalesLunchEndTime : $menuSalesDinnerEndTime;
                $vacancies = $vacancies->whereRaw("TIME_TO_SEC(time) >= TIME_TO_SEC('" . $startTime . "')")
                    ->whereRaw("TIME_TO_SEC(time) < TIME_TO_SEC('" . $endTime . "')");
            } else {
                // ここは来ない
            }

            // 営業日（曜日・祝日）を条件に設定
            if ($providedDayHoliday) {
                // 営業日に祝日を含む
                $vacancies = $vacancies->where(function($query) use ($targetWeek) {
                    $query->whereRaw('DAYOFWEEK(date) in (' . implode(',', $targetWeek) . ')')
                        ->OrWhereRaw('exists(select 1 from holidays where holidays.date = vacancies.date)');
                });
            } else {
                //　営業日に祝日を含まない
                $vacancies = $vacancies->whereRaw('DAYOFWEEK(date) in (' . implode(',', $targetWeek) . ')')
                    ->whereRaw('not exists(select 1 from holidays where holidays.date = vacancies.date)');
            }

            // 候補となる在庫情報を取得する
            // 同日時でもheadcountだけ違うデータが5件づつは取れることが想定さえるため、多めにデータを取得しておく。
            $stocks = $vacancies->orderBy('date', 'asc')->orderBy('time', 'asc')->orderBy('headcount', 'asc')->limit(100)->get();

            // 営業時間
            $openingHours = OpeningHour::where('store_id', $storeId)->get();

            $menus[] = $menu;
            $visitDateForBeforeLoop = null;
            $paramStocks = $stocks;
            $now = Carbon::now();                              // 現在日時
            $extClosingTime = new Carbon();                     // 最終予約可能時間
            $endAt = new Carbon();                              // 店舗の閉店時間
            $vTime = new Carbon();                              // 来店時間（これはCarbon化するだけ。後にaddMinutesする）
            $salesLunchEndTime = new Carbon();                  // ランチ販売終了時間
            $salesDinnerEndTime = new Carbon();                 // ディナー販売終了時間

            foreach ($stocks as $stock) {

                $visitDate = $stock->date;                          // 候補日
                $visitTime = $stock->time;                          // 候補時間
                $visitPeople = $stock->headcount;                   // 候補人数
                $dt = new Carbon($visitDate);                       // 来店日(後に時間を設定)
                $arrayRsvTime = explode(':', $visitTime);
                $dt->setTime($arrayRsvTime[0], $arrayRsvTime[1]);

                // 前回のループで対象日が今回と同じ日だった場合は、使いまわす。
                if (is_null($visitDateForBeforeLoop) || $visitDate != $visitDateForBeforeLoop) {
                    $holiday = Holiday::where('date', $visitDate)->first();     // 指定した日付の祝日情報を取得
                    $visitDateForBeforeLoop = $visitDate;
                }

                // 利用可能か（店舗の営業時間などを加味して）確認する
                $allCheckWithTime = $this->allCheckWithTime(false, $visitDate, $visitTime, $visitPeople, $externalApis, $store, $openingHours, $paramStocks, $menus, $stocks, $stock, $holiday, $now, $dt, $extClosingTime, $endAt, $vTime, $salesLunchEndTime, $salesDinnerEndTime, $msg);

                if (!empty($allCheckWithTime)) {
                    $res['date'] = $visitDate;
                    $res['time'] = $visitTime;
                    $res['headcount'] = $visitPeople;
                    break;
                }
            }
        }

        return $res;
    }
}
