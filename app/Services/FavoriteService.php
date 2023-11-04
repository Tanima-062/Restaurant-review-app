<?php

namespace App\Services;

use App\Models\Favorite;
use App\Models\Menu;
use App\Models\Store;

class FavoriteService
{
    public function __construct(
        Menu $menu,
        Favorite $favorite,
        Store $store
    ) {
        $this->menu = $menu;
        $this->favorite = $favorite;
        $this->store = $store;
    }

    /**
     * テイクアウトお気に入り一覧取得.
     *
     * @param int userId ユーザID
     * @param string pickUpDate 受取日
     * @param string pickUpTime 受取時間
     * @param string menuIds メニューid一覧
     *
     * @return array
     */
    public function get($userId, $pickUpDate = null, $pickUpTime = null, $menuIds = '')
    {
        try {
            if ($userId > 0) {
                $ids = $this->favorite->getFavoriteIds($userId, key(config('code.appCd.to')));
            } else {
                $ids = explode(',', $menuIds);
            }
            $result = $this->menu->get($ids, $pickUpDate, $pickUpTime)->toArray();
            $res = [];
            foreach ($result as $key => $val) {
                $res[$key]['id'] = $val['id'];
                $res[$key]['name'] = $val['name'];
                $res[$key]['description'] = $val['description'];
                if (isset($val['image'])) {
                    $res[$key]['thumbImage'] = [
                        'id' => $val['image']['id'],
                        'imageCd' => $val['image']['image_cd'],
                        'imageUrl' => $val['image']['url'],
                    ];
                }
                if (isset($val['menuPrice'])) {
                    $res[$key]['price'] = [
                        'id' => $val['menuPrice']['id'],
                        'priceCd' => $val['menuPrice']['price_cd'],
                        'price' => $val['menuPrice']['price'],
                    ];
                }
                if (isset($val['store'])) {
                    $res[$key]['store'] = [
                        'id' => $val['store']['id'],
                        'name' => $val['store']['name'],
                        'latitude' => $val['store']['latitude'],
                        'longitude' => $val['store']['longitude'],
                    ];

                    if (isset($val['store']['stations'])) {
                        $res[$key]['store']['station'] = [
                            'id' => $val['store']['stations']['id'],
                            'name' => $val['store']['stations']['name'],
                            'latitude' => $val['store']['stations']['latitude'],
                            'longitude' => $val['store']['stations']['longitude'],
                        ];
                    }
                }
            }

            return ['takeoutMenus' => $res];
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * レストランお気に入り一覧取得.
     *
     * @param int userId ユーザID
     * @param array params パラメータ配列
     *
     * @return array
     */
    public function getFavoriteStores($userId, $params)
    {
        try {
            // ログインユーザの場合はDBに保存されているお気に入りを取得
            if ($userId > 0) {
                $storeIds = $this->favorite->getFavoriteIds($userId, key(config('code.appCd.rs')));
            } else {
                $storeIds = empty($params['storeIds']) ? [] : explode(',', $params['storeIds']);
            }

            $visitDate = isset($params['pickUpDate']) ? $params['pickUpDate'] : null;
            $visitTime = isset($params['pickUpTime']) ? $params['pickUpTime'] : null;
            $searchParams = [
                'appCd' => config('code.gmServiceCd.rs'),
                'visitDate' => $visitDate,
                'visitTime' => $visitTime,
            ];
            // 人数(レストラン)
            if (!empty($params['visitPeople'])) {
                $searchParams['visitPeople'] = $params['visitPeople'];
            }
            $result = $this->store->getFavoriteStores($searchParams, $params['dateUndecided'], $storeIds);

            $res = [];
            foreach ($result as $key => $val) {
                $res[$key]['id'] = $val->id;
                $res[$key]['name'] = $val->name;
                $res[$key]['access'] = $val->access;
                $res[$key]['daytimeBudgetLowerLimit'] = $val->daytime_budget_lower_limit;
                $res[$key]['nightBudgetLowerLimit'] = $val->night_budget_lower_limit;

                $storeGenres = $val->genres;
                if (!is_null($storeGenres)) {
                    foreach ($storeGenres as $storeGenre) {
                        $tmpStoreGenre = [];
                        $tmpStoreGenre['id'] = $storeGenre->id;
                        $tmpStoreGenre['name'] = $storeGenre->name;
                        $tmpStoreGenre['genreCd'] = $storeGenre->genre_cd;
                        $tmpStoreGenre['appCd'] = $storeGenre->app_cd;
                        $tmpStoreGenre['path'] = $storeGenre->path;
                        $tmpStoreGenre['isDelegate'] = $storeGenre->is_delegate;
                        $res[$key]['storeGenres'][] = $tmpStoreGenre;
                    }
                }

                $images = $val->image;
                if (!is_null($images)) {
                    foreach ($images as $image) {
                        $res[$key]['storeImage']['id'] = $image->id;
                        $res[$key]['storeImage']['imageCd'] = $image->image_cd;
                        $res[$key]['storeImage']['imageUrl'] = $image->url;
                    }
                }

                $recommendMenus = $val->recommendMenus;
                if (!is_null($recommendMenus)) {
                    foreach ($recommendMenus as $recommendMenu) {
                        $res[$key]['recommendMenu']['id'] = $recommendMenu->id;
                        $res[$key]['recommendMenu']['name'] = $recommendMenu->name;
                        $menuPrice = $recommendMenu->menuPrice();
                        if (!is_null($menuPrice)) {
                            $res[$key]['recommendMenu']['price']['id'] = $menuPrice->id;
                            $res[$key]['recommendMenu']['price']['priceCd'] = $menuPrice->price_cd;
                            $res[$key]['recommendMenu']['price']['price'] = $menuPrice->price;
                        }
                    }
                }

                $openinghours = $val->openinghours;
                if (!is_null($openinghours)) {
                    foreach ($openinghours as $openinghour) {
                        $tmpOpeninghours = [];
                        $tmpOpeninghours['id'] = $openinghour->id;
                        $tmpOpeninghours['openTime'] = $openinghour->start_at;
                        $tmpOpeninghours['closeTime'] = $openinghour->end_at;
                        $tmpOpeninghours['code'] = $openinghour->opening_hour_cd;
                        $tmpOpeninghours['lastOrderTIme'] = $openinghour->last_order_time;
                        $tmpOpeninghours['week'] = $openinghour->week;
                        $res[$key]['openinghours'][] = $tmpOpeninghours;
                    }
                }

                $res[$key]['latitude'] = $val->latitude;
                $res[$key]['longitude'] = $val->longitude;
                $res[$key]['appCd'] = $val->app_cd;
                $res[$key]['lowerOrdersTime'] = $val->lower_orders_time;
                $res[$key]['priceLevel'] = $val->price_level;
            }

            return ['restorantStores' => $res];
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
