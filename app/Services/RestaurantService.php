<?php

namespace App\Services;

use App\Models\Area;
use App\Models\ExternalApi;
use App\Models\Genre;
use App\Models\Holiday;
use App\Models\Maintenance;
use App\Models\Menu;
use App\Models\OpeningHour;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationStore;
use App\Models\Review;
use App\Models\Stock;
use App\Models\Store;
use App\Models\Story;
use App\Models\Vacancy;
use App\Modules\Ebica\EbicaStockSave;
use App\Modules\Reservation\IFStock;
use Illuminate\Support\Carbon;

class RestaurantService
{
    public function __construct(
        Menu $menu,
        Genre $genre,
        Story $story,
        Store $store,
        Review $review,
        Vacancy $vacancy,
        RestaurantReservationService $restaurantReservationService,
        IFStock $restaurantStock
    ) {
        $this->menu = $menu;
        $this->genre = $genre;
        $this->story = $story;
        $this->store = $store;
        $this->review = $review;
        $this->vacancy = $vacancy;
        $this->restaurantReservationService = $restaurantReservationService;
        $this->restaurantStock = $restaurantStock;
    }

    /**
     * レストラン - メニュー詳細.
     *
     * @param int id メニューid
     * @param array params APIパラメータ
     *
     * @return array
     */
    public function detailMenu($id, $params)
    {
        $menuObj = $this->menu->detail($id, Carbon::now()->format('Y-m-d'));

        $res['result'] = [
            'status' => true,
            'message' => 'ok',
        ];

        // 在庫チェック
        $msg = null;
        $dt = new Carbon($params['visitDate'].' '.$params['visitTime']);
        //空席,在庫チェック
        if (!empty($menuObj->store->external_api)) {
            //外部接続があれば外部の空席確認
            if (!$this->restaurantStock->isVacancy($dt, $params['visitPeople'], $menuObj->store_id, $msg)) {
                $res['result']['message'] = $msg;
                $res['result']['status'] = false;
            }
        } else {
            //外部接続がなければskyの在庫確認
            if (!$this->restaurantReservationService->hasRestaurantStock($menuObj, $dt, $params['visitPeople'], $msg)) {
                $res['result']['message'] = $msg;
                $res['result']['status'] = false;
            }
        }

        $menu = $menuObj->toArray();
        $maintenanceMsg = null;
        Maintenance::isInMaintenance(config('code.maintenances.type.stopSale'), $maintenanceMsg);
        $res['id'] = $menu['id'];
        $res['name'] = $menu['name'];
        $res['description'] = $menu['description'];
        if (isset($menu['image'])) {
            $res['image'] = [
                    'id' => $menu['image']['id'],
                    'imageCd' => $menu['image']['image_cd'],
                    'imageUrl' => $menu['image']['url'],
                ];
        }
        // 訪問日から価格情報を取得する
        // 取得できなければ、menuオブジェクトから取得する
        $menuPrice = $this->menu::find($menu['id'])->menuPrice($params['visitDate']);
        if (!empty($menuPrice)) {
            $res['price'] = [
                'id' => $menuPrice['id'],
                'priceCd' => $menuPrice['price_cd'],
                'price' => $menuPrice['price'],
            ];
        } elseif (isset($menu['menuPrice'])) {
            $res['price'] = [
                    'id' => $menu['menuPrice']['id'],
                    'priceCd' => $menu['menuPrice']['price_cd'],
                    'price' => $menu['menuPrice']['price'],
                ];
        }

        $res['numberOfCource'] = $menu['number_of_course'];
        $res['availableNumberOfLowerLimit'] = $menu['available_number_of_lower_limit'];
        $res['availableNumberOfUpperLimit'] = $menu['available_number_of_upper_limit'];
        // 飲み放題　あり`true`　なし`false`
        $res['freeDrinks'] = $menu['free_drinks'] === 1 ? true : false;
        if (isset($menu['options'])) {
            // レストランはオプションのお好みの重複を削除する
            $deleteTarget = [];
            $uniqueOptions = [];
            foreach ($menu['options'] as $option) {
                // keyword_idが重複しておらず、オプション（お好み）の場合 （トッピングはレストランでは未実装 トッピングの場合、keyword_idがNULLになる）
                if (!in_array($option['keyword_id'], $deleteTarget) && !is_null($option['keyword_id'])) {
                    $deleteTarget[] = $option['keyword_id'];
                    $uniqueOptions[] = $option;
                }
            }
            // 重複分を削除したもので上書き
            $menu['options'] = $uniqueOptions;

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
        $res['plan'] = $menu['plan'];
        $reviews = $this->review->getReviewsByMenuId($id);
        foreach ($reviews as $review) {
            $infoReview = [];
            $infoReview['id'] = $review->id;
            $infoReview['userId'] = $review->user_id;
            $infoReview['username'] = $review->user_name;
            $infoReview['body'] = $review->body;
            $infoReview['evaluationCd'] = $review->evaluation_cd;
            if (!is_null($review->images_id)) {
                $tmpReview['image']['id'] = $review->images_id;
                $tmpReview['image']['imageCd'] = $review->image_cd;
                $tmpReview['image']['imageUrl'] = $review->url;
            }
            $infoReview['createdAt'] = $review->created_at;
            $res['reviews'][] = $infoReview;
        }
        $res['providedTime'] = intval($menu['provided_time']);
        if (isset($menu['menuPrice'])) {
            // 席のみ予約可能か？　メニューの価格が0円の場合はtrue、1円以上ならfalse
            $res['onlySeat'] = intval($menu['menuPrice']['price']) === 0 ? true : false;
        }
        $res['notes'] = $menu['notes'];
        $res['salesLunchStartTime'] = $menu['sales_lunch_start_time'];
        $res['salesLunchEndTime'] = $menu['sales_lunch_end_time'];
        $res['salesDinnerStartTime'] = $menu['sales_dinner_start_time'];
        $res['salesDinnerEndTime'] = $menu['sales_dinner_end_time'];
        $res['lowerOrdersTime'] = $menu['lower_orders_time'];

        // 店舗情報がほしい時は下記をアンコメントしてください。
        // -- ここから --
        // $res['store']['id'] = $menu['store']['id'];
        // $res['store']['name'] = $menu['store']['name'];
        // $res['store']['latitude'] = $menu['store']['latitude'];
        // $res['store']['longitude'] = $menu['store']['longitude'];
        // $res['store']['address'] =
        //     sprintf('%s %s %s',
        //         (!empty($menu['store']['address_1'])) ? $menu['store']['address_1'] : '',
        //         (!empty($menu['store']['address_2'])) ? $menu['store']['address_2'] : '',
        //         (!empty($menu['store']['address_3'])) ? $menu['store']['address_3'] : ''
        //     );

        // if (isset($menu['store']['stations'])) {
        //     $res['store']['station'] = [
        //             'id' => $menu['store']['stations']['id'],
        //             'name' => $menu['store']['stations']['name'],
        //             'latitude' => $menu['store']['stations']['latitude'],
        //             'longitude' => $menu['store']['stations']['longitude'],
        //             'distance' => 0,
        //         ];
        // }

        // if (isset($menu['store']['genres'])) {
        //     foreach ($menu['store']['genres'] as $val) {
        //         $res['store']['storeGenres'][] = [
        //             'id' => $val['id'],
        //             'name' => $val['name'],
        //             'genreCd' => $val['genre_cd'],
        //             'appCd' => $val['app_cd'],
        //             'path' => $val['path'],
        //         ];
        //     }
        // }
        // -- ここまで --

        return $res;
    }

    /**
     * ストーリー取得.
     *
     * @return array
     */
    public function getStory($request)
    {
        $list = $this->story->getStory(config('code.gmServiceCd.rs'), $request->page);

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
     * レストラン-検索ボックス取得.
     *
     * @return array
     */
    public function searchBox()
    {
        $res = [];

        $stores = Store::leftjoin('areas', 'areas.id', '=', 'stores.area_id')
            ->select(
                'stores.area_id',
                'areas.name as area_name',
                'areas.area_cd',
                'areas.path',
                'areas.level',
                'areas.weight', )
            ->where('stores.published', 1)
            ->where(function ($query) {
                $query->where('stores.app_cd', key(config('code.appCd.rs')))
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
     *おすすめレストラン取得.
     *
     * @param $params
     *
     * @return array
     */
    public function getRecommendation($params)
    {
        $res = [];
        $list = $this->store->getRecommendation($params);
        if (!$list || $list->count() == 0) {
            return $res;
        }

        foreach ($list->toArray() as $key => $store) {
            $res['result'][$key]['id'] = $store['id'];
            $res['result'][$key]['name'] = $store['name'];
            $res['result'][$key]['description'] = $store['description'];

            $res['result'][$key]['thumbImage'] = [
                'id' => (!empty($store['image']['id'])) ? $store['image']['id'] : 0,
                'imageCd' => (!empty($store['image']['image_cd'])) ? $store['image']['image_cd'] : '',
                'imageUrl' => (!empty($store['image']['url'])) ? $store['image']['url'] : '',
            ];
        }

        return $res;
    }

    /**
     * レストラン｜メニュー単位の在庫数.
     *
     * @param $params
     *
     * @return array
     */
    public function menuVacancy($params)
    {
        $res = [];

        // menuIdの場合（メニュー情報取得、メニューIDを取得、店舗IDを取得）
        if (!empty($params['menuId'])) {
            $menu = Menu::find($params['menuId']);
            $menuId = $menu->id;
            $storeId = isset($menu->store_id) ? $menu->store_id : null;
        }

        // reservationIdの場合（予約情報を取得、メニューIDを取得、店舗IDを取得）
        if (!empty($params['reservationId'])) {
            $reservation = ReservationStore::where('reservation_id', $params['reservationId'])->first();
            $menuId = ReservationMenu::where('reservation_id', $params['reservationId'])->first();
            $menuId = $menuId->menu_id;
            $storeId = isset($reservation->store_id) ? $reservation->store_id : null;
        }

        // 店舗情報取得
        $store = Store::find($storeId);
        // 店舗IDと紐付いたEbica用店舗情報を取得
        $externalApis = ExternalApi::where('store_id', $store->id)->first();
        // メニュー情報を取得していない場合は取得
        $menu = isset($menu) ? $menu : Menu::find($menuId);

        // メニュー最低注文時間のみで判断するパターン（現在はこちらを使用）
        $lowerOrdersTime = is_null($menu->lower_orders_time) ? 0 : $menu->lower_orders_time;  // メニュー最低注文時間

        // // メニュー最低注文時間と店舗最低注文時間の2つを比べて、長い方を最低注文時間とするパターン（予約導線と実装方法が違うためコメントアウト）
        // $storeLowerOrdersTime = is_null($store->lower_orders_time) ? 0: $store->lower_orders_time;;  // 店舗最低注文時間
        // $menuLowerOrdersTime = is_null($menu->lower_orders_time) ? 0: $menu->lower_orders_time;  // メニュー最低注文時間
        // $lowerOrdersTime = $storeLowerOrdersTime >= $menuLowerOrdersTime ? $storeLowerOrdersTime : $menuLowerOrdersTime;

        $visitDate = $params['visitDate'];                    // 来店日
        $arrayVisitDate = explode('-', $visitDate);           // 来店日をsetDateTimeするために配列化
        $now = Carbon::now();                                 // 現在時刻取得
        $vDate = new Carbon($visitDate.'00:00:00');           // 来店日
        $now = $now->addMinutes($lowerOrdersTime);            // 現在時刻＋最低注文時間
        $today = Carbon::today();                             // 今日の日付
        $msg = [];                                            // エラーメッセージ（現在レスポンスとして返してはいないが、checkFromWeekを使用するために必要）
        $openAt = new Carbon();                               // 営業開始時間（固定）
        $startAt = new Carbon();                              // 営業開始時間（可変）
        $endAt = new Carbon();                                // 営業終了時間
        $lastAt = new Carbon();                               // ラストオーダー時間
        $openingHourStartAt = new Carbon();                   // 店舗の開店時間（メニュー販売時間が設定されていないメニューの時に使用）
        $openingHourEndAt = new Carbon();                     // 店舗の閉店時間（メニュー販売時間が設定されていないメニューの時に使用）
        $lowerLimit = !is_null($menu->available_number_of_lower_limit) ? $menu->available_number_of_lower_limit : config('const.store.rsvLimit.lower'); // 利用可能下限人数
        $upperLimit = !is_null($menu->available_number_of_upper_limit) ? $menu->available_number_of_upper_limit : config('const.store.rsvLimit.upper'); // 利用可能上限人数

        // 店舗の定休日を確認
        $storeHoliday = $menu->checkFromWeek($store->regular_holiday, $vDate, 1, $msg);
        // メニュー提供可能日か確認
        $menuProvide = $menu->checkFromWeek($menu->provided_day_of_week, $vDate, 2, $msg);
        // 来店日が予約しようとしている日（今日）より過去でないかの確認
        $checkPast = $this->checkPast($today, $vDate);
        // 店舗定休日orメニュー提供可能日以外or来店日が予約しようとしている日（今日）より過去でない場合
        if ($storeHoliday === false || $menuProvide === false || $checkPast === false) {
            $res['stocks'] = [];

            return $res;
        }

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
            $res['stocks'] = [];

            return $res;
        }

        // Ebica連携あり店舗の空席情報取得
        if (!is_null($externalApis)) {
            // ランチ販売開始時間が設定されている場合（ランチ販売開始時間をCarbonで返す）
            if (!is_null($menu->sales_lunch_start_time)) {
                $lunchStartAt = $this->lunchStartAt($menu, $arrayVisitDate);
            }

            // ランチ販売終了時間が設定されている場合（ランチ販売終了時間をCarbonで返す）
            if (!is_null($menu->sales_lunch_end_time)) {
                $lunchEndAt = $this->lunchEndAt($menu, $arrayVisitDate);
            }

            // ディナー販売開始時間が設定されている場合（ディナー販売開始時間をCarbonで返す）
            if (!is_null($menu->sales_dinner_start_time)) {
                $dinnerStartAt = $this->dinnerStartAt($menu, $arrayVisitDate);
            }

            // ディナー販売終了時間が設定されている場合（ディナー販売終了時間をCarbonで返す）
            if (!is_null($menu->sales_dinner_end_time)) {
                $dinnerEndAt = $this->dinnerEndAt($menu, $arrayVisitDate);
            }

            $addDay = 3;
            // 予約する日から$addDay日後
            $fmtDate = Carbon::now()->addDay($addDay)->format('Y/m/d');
            $fmtVisitDate = new Carbon($visitDate);
            // $visitDateを$fmtDateと比較するためにフォーマット
            $fmtVisitDate = $fmtVisitDate->format('Y/m/d');

            // Ebicaを直叩きするパターン（予約予定日($visitDate)が予約する日から$addDay日以内ならEbicaAPIを呼び出す。）
            if ($fmtDate > $fmtVisitDate) {
                // Ebica API用のstore_id(api_store_id)をVacaciesより取得
                $apiShopId = Vacancy::getApiShopId($storeId)->first();
                $apiShopId = $apiShopId['api_store_id'];

                // Ebica APIから空席情報を取得
                $stock = new EbicaStockSave();

                // Ebica API用の店舗IDがない場合はNULL指定
                $stockDatas = !is_null($apiShopId) ? $stock->getStock($apiShopId, $visitDate) : null;

                // Ebicaから店舗の休日等で在庫が取得できない場合は、在庫を空で返す
                if (empty($stockDatas->stocks)) {
                    $res['stocks'] = [];

                    return $res;
                }

                $arrange = [];
                if ($stockDatas) {
                    // 閉店時間をsetDateTimeするために配列化
                    $arrayEnd = explode(':', end($stockDatas->stocks[0]->stock)->reservation_time.':00');
                    // 閉店時間をCarbon化
                    $endAt = $endAt->setDateTime($arrayVisitDate[0], $arrayVisitDate[1], $arrayVisitDate[2], $arrayEnd[0], $arrayEnd[1], $arrayEnd[2]);
                    // 閉店時間からメニュー提供時間を引く
                    $endAt->subMinutes($menu->provided_time);

                    foreach ($stockDatas->stocks as $stocks) {
                        foreach ($stocks->stock as $stock) {
                            // 利用可能下限人数以上かつ利用可能上限人数かを判断（100人以上の場合もcontinue）
                            $checkHeadcount = $this->checkHeadcount($stocks->headcount, $lowerLimit, $upperLimit);
                            if (!$checkHeadcount) {
                                continue;
                            }

                            // 現在時刻＋最低注文時間より過去の時間の在庫は返さない
                            $visitDatetime = $params['visitDate'].' '.$stock->reservation_time.':00';
                            if ($visitDatetime <= $now->format('Y-m-d H:i:s')) {
                                continue;
                            }

                            $insert['vacancyTime'] = $stock->reservation_time; // 時間
                            $insert['people'] = $stocks->headcount;            // 人数

                            // 取得した時間をsetDateTimeするために配列化
                            $arrayOpen = explode(':', $stock->reservation_time.':00');
                            // 取得した時間のCarbon化
                            $startAt = $startAt->setDateTime($arrayVisitDate[0], $arrayVisitDate[1], $arrayVisitDate[2], $arrayOpen[0], $arrayOpen[1], $arrayOpen[2]);

                            // ランチ販売開始時間とランチ販売終了時間、ディナー販売開始時間、ディナー販売終了時間の全てが設定されている場合
                            if (!is_null($menu->sales_lunch_start_time) && !is_null($menu->sales_lunch_end_time) && !is_null($menu->sales_dinner_start_time) && !is_null($menu->sales_dinner_end_time)) {
                                // 閉店時間（閉店時間 - メニュー提供時間）or ランチ販売終了時間　どっちか短い方
                                $lunchEndAt = $endAt->lte($lunchEndAt) ? $endAt : $lunchEndAt;
                                // 閉店時間（閉店時間 - メニュー提供時間）or ディナー販売終了時間　どっちか短い方
                                $dinnerEndAt = $endAt->lte($dinnerEndAt) ? $endAt : $dinnerEndAt;
                                if (($lunchStartAt->lte($startAt) && $startAt->lte($lunchEndAt)) || ($dinnerStartAt->lte($startAt) && $startAt->lte($dinnerEndAt))) {
                                    if ($stock->sets == 0) {
                                        continue;
                                    }
                                    $insert['sets'] = $stock->sets;
                                } else {
                                    continue;
                                    // $insert['sets'] = 0;
                                }
                                // ランチ販売開始時間とランチ販売終了時間が設定されている場合
                            } elseif (!is_null($menu->sales_lunch_start_time) && !is_null($menu->sales_lunch_end_time)) {
                                $lunchEndAt = $endAt->lte($lunchEndAt) ? $endAt : $lunchEndAt;
                                // ランチ販売開始時間 < 販売時間　かつ　販売時間 < ランチ販売終了時間 - メニュー提供時間
                                if ($lunchStartAt->lte($startAt) && $startAt->lte($lunchEndAt)) {
                                    if ($stock->sets == 0) {
                                        continue;
                                    }
                                    $insert['sets'] = $stock->sets;
                                } else {
                                    continue;
                                    // $insert['sets'] = 0;
                                }
                                // ディナー販売開始時間とディナー販売終了時間が設定されている場合
                            } elseif (!is_null($menu->sales_dinner_start_time) && !is_null($menu->sales_dinner_end_time)) {
                                $dinnerEndAt = $endAt->lte($dinnerEndAt) ? $endAt : $dinnerEndAt;

                                // ディナー販売開始時間 < 販売時間　かつ　販売時間 < ディナー販売終了時間 - メニュー提供時間
                                if ($dinnerStartAt->lte($startAt) && $startAt->lte($dinnerEndAt)) {
                                    if ($stock->sets == 0) {
                                        continue;
                                    }
                                    $insert['sets'] = $stock->sets;
                                } else {
                                    continue;
                                    // $insert['sets'] = 0;
                                }
                                // 販売時間が入っていない場合
                            } else {
                                $noSalesTimeCanSale = $this->noSalesTimeCanSale($openingHours, $arrayVisitDate, $menu, $startAt, $openingHourStartAt, $openingHourEndAt, $stock->sets);
                                $insert['sets'] = !is_null($noSalesTimeCanSale) ? $noSalesTimeCanSale : 0;
                                // foreach ($resultWeeks as $resultWeek) {
                                //     $openingHourStartAt = new Carbon($visitDate.' '.$resultWeek['open']);
                                //     $openingHourEndAt = new Carbon($visitDate.' '.$resultWeek['end']);
                                //     $openingHourEndAt->subMinutes($menu->provided_time);
                                //     if ($openingHourStartAt <= $startAt && $openingHourEndAt >= $startAt) {
                                //         $insert['sets'] = $stock->sets;
                                //         break;
                                //     } else {
                                //         $insert['sets'] = 0;
                                //         break;
                                //     }
                                // }
                                if ($insert['sets'] == 0) {
                                    continue;
                                }

                            }
                            $arrange[] = $insert;
                        }
                        $res['stocks'] = $arrange;

                        // Vacanciesテーブルにレコードはあるが、すべてのstockが0の場合、$res['stocks]が定義されないため、空配列を入れる。
                        $res['stocks'] = isset($res['stocks']) ? $res['stocks'] : [] ;

                        $res['stocks'] = !empty($res['stocks']) ? array_merge($res['stocks']) : $res['stocks'];
                    }
                }
                // DBを見に行くパターン（予約予定日($visitDate)が予約する日から$addDay + 1日以上ならDatabaseのVacanciesを参照する。）
            } else {
                // Vacanciesテーブルから空席情報を取得
                $vacancies = Vacancy::where('store_id', $storeId)->where('date', $visitDate)->get();

                // Vacanciesテーブルに空席情報がなかった場合
                if ($vacancies->isEmpty()) {
                    $res['stocks'] = [];

                    return $res;
                }

                // 閉店時間をsetDateTimeするために配列化
                $arrayEnd = explode(':', $vacancies->toArray()[count($vacancies->toArray()) - 1]['time'].':00');
                // 閉店時間をCarbon化
                $endAt = $endAt->setDateTime($arrayVisitDate[0], $arrayVisitDate[1], $arrayVisitDate[2], $arrayEnd[0], $arrayEnd[1], $arrayEnd[2]);
                // 閉店時間からメニュー提供時間を引く
                $endAt->subMinutes($menu->provided_time);

                if (!is_null($vacancies)) {
                    foreach ($vacancies as $num => $vacancy) {
                        // 利用可能下限人数以上かつ利用可能上限人数かを判断（100人以上の場合もcontinue）
                        $checkHeadcount = $this->checkHeadcount($vacancy->headcount, $lowerLimit, $upperLimit);
                        if (!$checkHeadcount) {
                            continue;
                        }

                        $visitDatetime = $vacancy->date.' '.$vacancy->time;
                        // 現在時刻＋最低注文時間より過去の時間の在庫は返さない
                        if ($visitDatetime <= $now->format('Y-m-d H:i:s')) {
                            continue;
                        }
                        // $res['stocks'][$num]['vacancyTime'] = substr($vacancy->time, 0, 5); // 時間
                        // $res['stocks'][$num]['people'] = $vacancy->headcount;               // 人数
                        // $res['stocks'][$num]['sets'] = $vacancy->stock;                  // 在庫

                        // 取得した時間をsetDateTimeするために配列化
                        $arrayOpen = explode(':', $vacancy->time);
                        // 取得した時間のCarbon化
                        $startAt = $startAt->setDateTime($arrayVisitDate[0], $arrayVisitDate[1], $arrayVisitDate[2], $arrayOpen[0], $arrayOpen[1], $arrayOpen[2]);

                        // ランチ販売開始時間とランチ販売終了時間、ディナー販売開始時間、ディナー販売終了時間の全てが設定されている場合
                        if (!is_null($menu->sales_lunch_start_time) && !is_null($menu->sales_lunch_end_time) && !is_null($menu->sales_dinner_start_time) && !is_null($menu->sales_dinner_end_time)) {
                            // 閉店時間（閉店時間 - メニュー提供時間）or ランチ販売終了時間　どっちか短い方
                            $lunchEndAt = $endAt->lte($lunchEndAt) ? $endAt : $lunchEndAt;
                            // 閉店時間（閉店時間 - メニュー提供時間）or ディナー販売終了時間　どっちか短い方
                            $dinnerEndAt = $endAt->lte($dinnerEndAt) ? $endAt : $dinnerEndAt;
                            if (($lunchStartAt->lte($startAt) && $startAt->lte($lunchEndAt)) || ($dinnerStartAt->lte($startAt) && $startAt->lte($dinnerEndAt))) {
                                if ($vacancy->stock == 0) {
                                    continue;
                                }
                                $res['stocks'][$num]['vacancyTime'] = substr($vacancy->time, 0, 5);
                                $res['stocks'][$num]['people'] = $vacancy->headcount;
                                $res['stocks'][$num]['sets'] = $vacancy->stock;
                            } else {
                                continue;
                                // $res['stocks'][$num]['sets'] = 0;
                            }
                            // ランチ販売開始時間とランチ販売終了時間が設定されている場合
                        } elseif (!is_null($menu->sales_lunch_start_time) && !is_null($menu->sales_lunch_end_time)) {
                            $lunchEndAt = $endAt->lte($lunchEndAt) ? $endAt : $lunchEndAt;
                            // ランチ販売開始時間 < 販売時間　かつ　販売時間 < ランチ販売終了時間 - メニュー提供時間
                            if ($lunchStartAt->lte($startAt) && $startAt->lte($lunchEndAt)) {
                                if ($vacancy->stock == 0) {
                                    continue;
                                }
                                $res['stocks'][$num]['vacancyTime'] = substr($vacancy->time, 0, 5);
                                $res['stocks'][$num]['people'] = $vacancy->headcount;
                                $res['stocks'][$num]['sets'] = $vacancy->stock;
                            } else {
                                continue;
                                // $res['stocks'][$num]['sets'] = 0;
                            }
                            // ディナー販売開始時間とディナー販売終了時間が設定されている場合
                        } elseif (!is_null($menu->sales_dinner_start_time) && !is_null($menu->sales_dinner_end_time)) {
                            $dinnerEndAt = $endAt->lte($dinnerEndAt) ? $endAt : $dinnerEndAt;
                            // ディナー販売開始時間 < 販売時間　かつ　販売時間 < ディナー販売終了時間 - メニュー提供時間
                            if ($dinnerStartAt->lte($startAt) && $startAt->lte($dinnerEndAt)) {
                                if ($vacancy->stock == 0) {
                                    continue;
                                }
                                $res['stocks'][$num]['vacancyTime'] = substr($vacancy->time, 0, 5);
                                $res['stocks'][$num]['people'] = $vacancy->headcount;
                                $res['stocks'][$num]['sets'] = $vacancy->stock;
                            } else {
                                continue;
                                // $res['stocks'][$num]['sets'] = 0;
                            }
                            // 販売時間が入っていない場合
                        } else {
                            $noSalesTimeCanSale = $this->noSalesTimeCanSale($openingHours, $arrayVisitDate, $menu, $startAt, $openingHourStartAt, $openingHourEndAt, $vacancy->stock);

                            $sets = !is_null($noSalesTimeCanSale) ? $noSalesTimeCanSale : 0;
                            if ($sets == 0) {
                                continue;
                            }
                            $res['stocks'][$num]['vacancyTime'] = substr($vacancy->time, 0, 5);
                            $res['stocks'][$num]['people'] = $vacancy->headcount;
                            $res['stocks'][$num]['sets'] = $sets;
                        }
                    }

                    // Vacanciesテーブルにレコードはあるが、すべてのstockが0の場合、$res['stocks]が定義されないため、空配列を入れる。
                    $res['stocks'] = isset($res['stocks']) ? $res['stocks'] : [] ;

                    $res['stocks'] = !empty($res['stocks']) ? array_merge($res['stocks']) : $res['stocks'];
                }
            }
            // Ebica連携なしの店舗の空席情報取得
        } else {
            // // 店舗に紐付いたメニュー stock見る方(仕様変更のためコメントアウト)
            // $storeMenus = $store->restaurantMenus;

            // // 指定日合計予約数予約数
            // $rsvMenus = 0;

            // // 指定日合計予約数予約数
            // $rsvSeats = 0;

            // // メニュー予約数 $rsvMenus
            // $reservations = Reservation::where('pick_up_datetime', 'LIKE', $visitDate.'%')
            //     ->whereHas('reservationStore', function ($q) use ($menu) {
            //         $q->where('store_id', $menu->store_id);
            //     })
            //     ->whereHas('reservationMenus', function ($q) use ($menu) {
            //         $q->where('menu_id', $menu->id);
            //         // $q->where('menu_id', $menuId);
            //     })
            //     ->whereNull('cancel_datetime')
            //     ->get();

            // // 予約数を合計し$rsvMenusへ代入
            // if ($reservations->count() > 0) {
            //     foreach ($reservations as $reservation) {
            //         $rsvMenus += $reservation->persons;
            //     }
            // }

            // // 予約席数 $rsvSeats
            // foreach ($storeMenus as $storeMenu) {
            //     $reservations = Reservation::where('pick_up_datetime', 'LIKE', $visitDate.'%')
            //         ->whereHas('reservationStore', function ($q) use ($storeMenu) {
            //             $q->where('store_id', $storeMenu->store_id);
            //         })
            //         ->whereHas('reservationMenus', function ($q) use ($storeMenu) {
            //             $q->where('menu_id', $storeMenu->id);
            //         })
            //         ->whereNull('cancel_datetime')
            //         ->get();

            //     if ($reservations->count() > 0) {
            //         foreach ($reservations as $reservation) {
            //             $rsvSeats += $reservation->persons;
            //         }
            //     }
            // }

            // // 店舗総座席数
            // $numberOfSeats = is_null($store->number_of_seats) ? 0 : $store->number_of_seats;
            // // メニュー在庫取得
            // $dbStock = Stock::where('menu_id', $menuId)
            //     ->where('date', $visitDate)
            //     ->first();
            // $dbStockNumber = is_null($dbStock) ? 0 : $dbStock->stock_number;

            // // (メニュー在庫がないor設定されていない) or (座席設定が0or店舗座席数が設定されていない)場合
            // if ($dbStockNumber === 0 || $numberOfSeats === 0) {
            //     $res['stocks'] = [];

            //     return $res;
            // }

            // // メニュー在庫（メニュー在庫 - メニュー予約数）
            // $stocksMinusReservedSeats = $dbStockNumber - $rsvMenus;

            // // 残り座席数（店舗座席数 - 店舗予約数）
            // $seatsMinusReservedSeats = $numberOfSeats - $rsvSeats;

            // // 予約可能席数計算(店舗総座席数 - 当日予約数)
            // $canReservation = $stocksMinusReservedSeats > $seatsMinusReservedSeats ? $seatsMinusReservedSeats : $stocksMinusReservedSeats;

            // // 予約可能席数上限設定（現状：99人 or メニューの利用可能上限人数）
            // $upperLimit = $upperLimit > config('const.store.rsvLimit.upper') ? config('const.store.rsvLimit.upper') : $upperLimit;
            // $loopLimit = $canReservation > $upperLimit ? $upperLimit : $canReservation;

            // if ($vDate->gte($today)) {
            //     // データを配列で持たせるための整数(下記while文下で使用)
            //     $num = 0;

            //     // 予約可能席数をloop：sets計算のため&people・setsセット
            //     for ($i = $menu->available_number_of_lower_limit - 1; $i < $loopLimit; ++$i) {
            //         // 店舗に結びついたopening_hours(休みの場合は除く)の回数loop：vacancyTime計算のため
            //         foreach ($resultWeeks as $resultWeek) {
            //             $arrayOpen = explode(':', $resultWeek['open']);    // 開店時間をsetDateTimeするために配列化
            //             $arrayEnd = explode(':', $resultWeek['end']);      // 閉店時間をsetDateTimeするために配列化
            //             $arrayLast = explode(':', $resultWeek['last']);    // ラストオーダー時間をsetDateTimeするために配列化

            //             // 開店時間と閉店時間、ラストオーダー時間を日付+時間(setDateTime)でCarbonでセット
            //             $startAt = $startAt->setDateTime($arrayVisitDate[0], $arrayVisitDate[1], $arrayVisitDate[2], $arrayOpen[0], $arrayOpen[1], $arrayOpen[2]);
            //             $endAt = $endAt->setDateTime($arrayVisitDate[0], $arrayVisitDate[1], $arrayVisitDate[2], $arrayEnd[0], $arrayEnd[1], $arrayEnd[2]);
            //             $endAt->subMinutes($menu->provided_time);
            //             $lastAt = $lastAt->setDateTime($arrayVisitDate[0], $arrayVisitDate[1], $arrayVisitDate[2], $arrayLast[0], $arrayLast[1], $arrayLast[2]);

            //             // ランチ販売開始時間が設定されている場合
            //             if (!is_null($menu->sales_lunch_start_time)) {
            //                 $lunchStartAt = $this->lunchStartAt($menu, $arrayVisitDate);
            //             }

            //             // ランチ販売終了時間が設定されている場合
            //             if (!is_null($menu->sales_lunch_end_time)) {
            //                 $lunchEndAt = $this->lunchEndAt($menu, $arrayVisitDate);
            //             }

            //             // ディナー販売開始時間が設定されている場合
            //             if (!is_null($menu->sales_dinner_start_time)) {
            //                 $dinnerStartAt = $this->dinnerStartAt($menu, $arrayVisitDate);
            //             }

            //             // ディナー販売終了時間が設定されている場合
            //             if (!is_null($menu->sales_dinner_end_time)) {
            //                 $dinnerEndAt = $this->dinnerEndAt($menu, $arrayVisitDate);
            //             }

            //             // 開店時間がラストオーダーの時間を超えるまでloop：vacancyTimeセットのため
            //             while (!$startAt->gt($lastAt)) {
            //                 // 過去の時間(含む最低注文時間)だった場合(setsを0にする)
            //                 if (!$startAt->gt($now)) {
            //                     $res['stocks'][$num]['vacancyTime'] = $startAt->format('H:i'); // 時間
            //                     $res['stocks'][$num]['people'] = $i + 1;                       // 人数
            //                     $res['stocks'][$num]['sets'] = 0;                              // 在庫（過去の時間のため、0になる）
            //                     $startAt = $startAt->addMinutes(config('const.store.interval.vacancyTime'));
            //                     ++$num;
            //                 // 未来の時間だった場合
            //                 } else {
            //                     $res['stocks'][$num]['vacancyTime'] = $startAt->format('H:i'); // 時間
            //                     $res['stocks'][$num]['people'] = $i + 1;                       // 人数

            //                     // ランチ販売開始時間とランチ販売終了時間、ディナー販売開始時間、ディナー販売終了時間の全てが設定されている場合
            //                     if (!is_null($menu->sales_lunch_start_time) && !is_null($menu->sales_lunch_end_time) && !is_null($menu->sales_dinner_start_time) && !is_null($menu->sales_dinner_end_time)) {
            //                         $lunchEndAt = $endAt->lte($lunchEndAt) ? $endAt : $lunchEndAt;
            //                         $dinnerEndAt = $endAt->lte($dinnerEndAt) ? $endAt : $dinnerEndAt;
            //                         if (($lunchStartAt->lte($startAt) && $startAt->lte($lunchEndAt)) || ($dinnerStartAt->lte($startAt) && $startAt->lte($dinnerEndAt))) {
            //                             $res['stocks'][$num]['sets'] = (int) floor($canReservation / ($i + 1));
            //                         } else {
            //                             $res['stocks'][$num]['sets'] = 0;
            //                         }
            //                         // ランチ販売開始時間とランチ販売終了時間が設定されている場合
            //                     } elseif (!is_null($menu->sales_lunch_start_time) && !is_null($menu->sales_lunch_end_time)) {
            //                         $lunchEndAt = $endAt->lte($lunchEndAt) ? $endAt : $lunchEndAt;
            //                         // ランチ販売開始時間 < 販売時間　かつ　販売時間 < ランチ販売終了時間 - メニュー提供時間
            //                         if ($lunchStartAt->lte($startAt) && $startAt->lte($lunchEndAt)) {
            //                             $res['stocks'][$num]['sets'] = (int) floor($canReservation / ($i + 1));
            //                         } else {
            //                             $res['stocks'][$num]['sets'] = 0;
            //                         }
            //                         // ディナー販売開始時間とディナー販売終了時間が設定されている場合
            //                     } elseif (!is_null($menu->sales_dinner_start_time) && !is_null($menu->sales_dinner_end_time)) {
            //                         $dinnerEndAt = $endAt->lte($dinnerEndAt) ? $endAt : $dinnerEndAt;
            //                         // ディナー販売開始時間 < 販売時間　かつ　販売時間 < ディナー販売終了時間 - メニュー提供時間
            //                         if ($dinnerStartAt->lte($startAt) && $startAt->lte($dinnerEndAt)) {
            //                             $res['stocks'][$num]['sets'] = (int) floor($canReservation / ($i + 1));
            //                         } else {
            //                             $res['stocks'][$num]['sets'] = 0;
            //                         }
            //                         // 販売時間が入っていない場合
            //                     } else {
            //                         $openAt = $openAt->setDateTime($arrayVisitDate[0], $arrayVisitDate[1], $arrayVisitDate[2], $arrayOpen[0], $arrayOpen[1], $arrayOpen[2]);
            //                         // 営業開始時間以内の場合（営業開始時間から最終注文可能時間(営業終了時間 - メニュー提供時間の間)）
            //                         if ($openAt->lte($startAt) && $startAt->lte($endAt)) {
            //                             $res['stocks'][$num]['sets'] = (int) floor($canReservation / ($i + 1));
            //                         } else {
            //                             $res['stocks'][$num]['sets'] = 0;
            //                         }
            //                     }

            //                     $startAt = $startAt->addMinutes(config('const.store.interval.vacancyTime'));
            //                     ++$num;
            //                 }
            //             }
            //         }
            //     }
            // }

            // Vacancyを見る方
            $arrayVacancies = [];
            foreach ($resultWeeks as $resultWeek) {
                $openingHourStartAt = new Carbon($visitDate.' '.$resultWeek['open']);
                $openingHourEndAt = new Carbon($visitDate.' '.$resultWeek['end']);
                $lunchTimeVacancies = null;
                $dinnerTimeVacancies = null;

                // 営業時間内の空席情報を取得
                $openingHourVacancies = Vacancy::where('store_id', $store->id)
                    ->whereDate('date', $visitDate)
                    ->whereTime('time', '>=', $openingHourStartAt->format('H:i:s'))
                    ->whereTime('time', '<=', $openingHourEndAt->format('H:i:s'))
                    ->where('headcount', '<=', $upperLimit)
                    ->where('headcount', '>=', $lowerLimit)
                    ->get();

                // 売り止めがある場合は((売り止め時間-提供時間)~売り止め時間)の間の時間は返さないように
                $stopSaleVacancies = $openingHourVacancies->where('is_stop_sale', 1)->where('headcount', $lowerLimit);
                if ($stopSaleVacancies->count() != 0) {
                    foreach ($stopSaleVacancies as $stopSaleVacancy) {
                        $stopSaleEndAt = new Carbon($visitDate.' '.$stopSaleVacancy->time);
                        $stopSaleStartAt = $stopSaleEndAt->copy()->subMinutes($menu->provided_time);
                        $openingHourVacancies = $openingHourVacancies->whereNotBetween('time', [$stopSaleStartAt->format('H:i:s'), $stopSaleEndAt->format('H:i:s')]);
                    }
                }

                // メニュー提供時間の設定があればさらに絞り込む
                if (!is_null($menu->sales_lunch_start_time) && !is_null($menu->sales_lunch_end_time)) { //ランチが入ってた場合
                $lunchStartAt = $this->lunchStartAt($menu, $arrayVisitDate);
                $lunchEndAt = $this->lunchEndAt($menu, $arrayVisitDate);
                $lunchTimeVacancies = $openingHourVacancies->whereBetween('time', [$lunchStartAt->format('H:i:s'), $lunchEndAt->format('H:i:s')]);
                $lunchTimeVacancies = $lunchTimeVacancies->count() != 0 ? $lunchTimeVacancies : null;
                }

                if (!is_null($menu->sales_dinner_start_time) && !is_null($menu->sales_dinner_end_time)) { //ディナーが入ってた場合
                $dinnerStartAt = $this->dinnerStartAt($menu, $arrayVisitDate);
                $dinnerEndAt = $this->dinnerEndAt($menu, $arrayVisitDate);
                $dinnerTimeVacancies = $openingHourVacancies->whereBetween('time', [$dinnerStartAt->format('H:i:s'), $dinnerEndAt->format('H:i:s')]);
                $dinnerTimeVacancies = $dinnerTimeVacancies->count() != 0 ? $dinnerTimeVacancies : null;
                }

                // 絞り込んだコレクションを配列に一旦入れ込む
                if (!is_null($lunchTimeVacancies) && !is_null($dinnerTimeVacancies)) {

                    $arrayVacancies[] = $lunchTimeVacancies;
                    $arrayVacancies[] = $dinnerTimeVacancies;

                } elseif (!is_null($lunchTimeVacancies) && is_null($dinnerTimeVacancies)) {

                    $arrayVacancies[] = $lunchTimeVacancies;

                } elseif (is_null($lunchTimeVacancies) && !is_null($dinnerTimeVacancies)) {

                    $arrayVacancies[] = $dinnerTimeVacancies;

                } elseif (is_null($lunchTimeVacancies) && is_null($dinnerTimeVacancies)) {

                    $menuEndAt = $openingHourEndAt->subMinutes($menu->provided_time);
                    $allTimeVacancies = $openingHourVacancies->whereBetween('time', [$openingHourStartAt->format('H:i:s'), $menuEndAt->format('H:i:s')]);
                    $arrayVacancies[] = $allTimeVacancies;
                    continue;

                }
            }

            // 絞り込んだ空席情報の結合
            foreach ($arrayVacancies as $key => $vacancies) {
                if ($key == 0) {
                    $mergeVacancies = $vacancies;
                    continue;
                }
                $mergeVacancies = $mergeVacancies->merge($vacancies);
            }

            // headcount,timeでソート
            $mergeVacancies = $mergeVacancies->sort(function($first, $second) {
                if ($first->headcount == $second->headcount) {
                    return $first->time > $second->time ? 1 : -1;
                }
                return $first->headcount > $second->headcount ? 1 : -1;
            });

            // データの整形
            foreach ($mergeVacancies as $key => $vacancy) {
                $visitDatetime = $vacancy->date.' '.$vacancy->time;
                // 現在時刻＋最低注文時間より過去の時間の在庫は返さない
                if ($visitDatetime <= $now->format('Y-m-d H:i:s')) {
                    continue;
                }

                if ($vacancy->stock == 0) {
                    continue;
                }
                $vacancyDate = new Carbon($visitDate.' '.$vacancy->time);
                // $res['stocks'][$key]['vacancyTime'] = $vacancy->time;
                $res['stocks'][$key]['vacancyTime'] = $vacancyDate->format('H:i');
                $res['stocks'][$key]['people'] = $vacancy->headcount;
                $res['stocks'][$key]['sets'] = $vacancy->stock;
            }

        }

        if (isset($res['stocks'])) {
            $res['stocks'] = array_merge($res['stocks']);
        }

        // 在庫がなかった時に$res['stocks']に空の配列を返す
        if (!isset($res['stocks'])) {
            $res['stocks'] = [];
        }

        return $res;
    }

    /**
     * 来店日が予約しようとしている日（今日）より過去でないかの確認.
     *
     * @param Carbon $today
     * @param Carbon $vDate
     *
     * @return bool
     */
    public function checkPast($today, $vDate)
    {
        return $today->lte($vDate);
    }

    /**
     * ランチ販売開始時間.
     *
     * @param object $menu
     * @param array  $arrayVisitDate
     *
     * @return Carbon $lunchStartAt
     */
    public function lunchStartAt($menu, $arrayVisitDate)
    {
        $lunchStartAt = new Carbon();                                       // ランチ販売開始時間のCarbonインスタンス化
        $arrayLunchStart = explode(':', $menu->sales_lunch_start_time);     // 開店時間をsetDateTimeするために配列化
        $lunchStartAt = $lunchStartAt->setDateTime($arrayVisitDate[0], $arrayVisitDate[1], $arrayVisitDate[2], $arrayLunchStart[0], $arrayLunchStart[1], $arrayLunchStart[2]); // ランチ販売開始時間

        return $lunchStartAt;
    }

    /**
     * ランチ販売終了時間.
     *
     * @param object $menu
     * @param array  $arrayVisitDate
     *
     * @return Carbon $lunchEndAt
     */
    public function lunchEndAt($menu, $arrayVisitDate)
    {
        $lunchEndAt = new Carbon();                                         // ランチ販売終了時間のCarbonインスタンス化
        $arrayLunchEnd = explode(':', $menu->sales_lunch_end_time);         // ラストオーダー時間をsetDateTimeするために配列化
        $lunchEndAt = $lunchEndAt->setDateTime($arrayVisitDate[0], $arrayVisitDate[1], $arrayVisitDate[2], $arrayLunchEnd[0], $arrayLunchEnd[1], $arrayLunchEnd[2]); // ランチ販売終了時間
        $lunchEndAt->subMinutes($menu->provided_time); // ランチ販売終了時間からメニュー提供時間を引く（この処理をしないと、販売終了時間ぎりぎりまで在庫が有効になる）

        return $lunchEndAt;
    }

    /**
     * ディナー販売開始時間.
     *
     * @param object $menu
     * @param array  $arrayVisitDate
     *
     * @return Carbon $dinnerStartAt
     */
    public function dinnerStartAt($menu, $arrayVisitDate)
    {
        $dinnerStartAt = new Carbon();                                      // ディナー販売開始時間のCarbonインスタンス化
        $arrayDinnerStart = explode(':', $menu->sales_dinner_start_time);   // 開店時間をsetDateTimeするために配列化
        $dinnerStartAt = $dinnerStartAt->setDateTime($arrayVisitDate[0], $arrayVisitDate[1], $arrayVisitDate[2], $arrayDinnerStart[0], $arrayDinnerStart[1], $arrayDinnerStart[2]);  // ディナー販売開始時間

        return $dinnerStartAt;
    }

    /**
     * ディナー販売終了時間.
     *
     * @param object $menu
     * @param array  $arrayVisitDate
     *
     * @return Carbon $dinnerEndAt
     */
    public function dinnerEndAt($menu, $arrayVisitDate)
    {
        $dinnerEndAt = new Carbon();                                        // ディナー販売終了時間のCarbonインスタンス化
        $arrayDinnerEnd = explode(':', $menu->sales_dinner_end_time);       // ラストオーダー時間をsetDateTimeするために配列化
        $dinnerEndAt = $dinnerEndAt->setDateTime($arrayVisitDate[0], $arrayVisitDate[1], $arrayVisitDate[2], $arrayDinnerEnd[0], $arrayDinnerEnd[1], $arrayDinnerEnd[2]); // ディナー販売終了時間
        $dinnerEndAt->subMinutes($menu->provided_time); // ディナー販売終了時間からメニュー提供時間を引く（この処理をしないと、販売終了時間ぎりぎりまで在庫が有効になる）

        return $dinnerEndAt;
    }

    /**
     * 利用可能下限人数以上かつ利用可能上限人数かを判断（100人以上の場合はfalse）.
     *
     * @param int $headcount
     * @param int $lowerLimit
     * @param int $upperLimit
     *
     * @return bool
     */
    public function checkHeadcount($headcount, $lowerLimit, $upperLimit)
    {
        $lowerLimit = is_null($lowerLimit) ? config('const.store.rsvLimit.bottom') : $lowerLimit;                                    // 利用可能下限人数が設定されていない場合は、0人とする
        $upperLimit = is_null($upperLimit) ? config('const.store.rsvLimit.upper') : $upperLimit; // 利用可能上限人数が設定されていない場合は、99人とする

        // 利用可能上限人数が99人より多かった場合は、99人に設定
        $upperLimit = $upperLimit > config('const.store.rsvLimit.upper') ? config('const.store.rsvLimit.upper') : $upperLimit;

        // 人数が利用可能下限人数より少ない場合
        if ($headcount < $lowerLimit) {
            return false;
        }

        // 人数が利用可能上限人数より多い場合
        if ($headcount > $upperLimit) {
            return false;
        }

        return true;
    }

    /**
     * メニューに紐づくランチ販売時間とディナー販売時間がなかった時に、販売可能か判断する.
     *
     * @param object $openingHours   営業時間
     * @param array  $arrayVisitDate 予約日
     * @param object $menu           メニュー
     * @param Carbon $startAt        各予約時間
     *
     * @return Multi 営業時間内であればInt（整数）を返す　営業時間外であればNULLを返す
     */
    public function noSalesTimeCanSale($openingHours, $arrayVisitDate, $menu, $startAt, $openingHourStartAt, $openingHourEndAt, $set)
    {
        foreach ($openingHours as $openingHour) {
            $arrayOpeningHourStart = explode(':', $openingHour->start_at);
            $openingHourStartAt = $openingHourStartAt->setDateTime($arrayVisitDate[0], $arrayVisitDate[1], $arrayVisitDate[2], $arrayOpeningHourStart[0], $arrayOpeningHourStart[1], $arrayOpeningHourStart[2]);
            $arrayOpeningHourEnd = explode(':', $openingHour->end_at);
            $openingHourEndAt = $openingHourEndAt->setDateTime($arrayVisitDate[0], $arrayVisitDate[1], $arrayVisitDate[2], $arrayOpeningHourEnd[0], $arrayOpeningHourEnd[1], $arrayOpeningHourEnd[2]);
            $openingHourEndAt->subMinutes($menu->provided_time);
            if ($openingHourStartAt->lte($startAt) && $openingHourEndAt->gte($startAt)) {
                return $set;
            }
        }
    }
}
