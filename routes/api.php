<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::group(['middleware' => 'accessLog'], function () {
    Route::group(['prefix' => 'gourmet'], function () {
        Route::group(
        [
            'prefix' => 'v1',
            'namespace' => 'Api\v1',
        ],
        function () {
            Route::group(['prefix' => '{langCd}'], function () {
                Route::match(['get', 'options'], 'test', 'TestController@index');

                // area
                Route::get('area/{areaCd}', 'AreaController@getArea')->name('getArea');

                // takeout
                Route::get('takeout/search', 'TakeoutController@search')->name('searchTakeout');
                Route::get('takeout/notice', 'TakeoutController@notice')->name('notice');
                Route::post('takeout/reservation/save', 'TakeoutController@save')->name('saveTakeout');
                Route::post('takeout/reservation/complete', 'TakeoutController@complete')->name('completeTakeout');
                Route::get('takeout/menu/{id}', 'TakeoutController@detailMenu')->name('detailMenu');
                Route::get('takeout/story', 'TakeoutController@getStory')->name('getStory');
                Route::get('takeout/recommend', 'TakeoutController@getRecommendation')->name('getRecommendation');
                Route::get('takeout/genre/{genreCd}', 'TakeoutController@getTakeoutGenre')->name('getTakeoutGenre');
                Route::post('takeout/reservation/close', 'TakeoutController@close')->name('closeTakeout');
                Route::get('takeout/searchBox', 'TakeoutController@searchBox')->name('searchBox');

                // restaurant
                Route::get('restaurant/notice', 'RestaurantController@notice')->name('restaurantNotice');
                Route::get('restaurant/menu/{id}', 'RestaurantController@detailMenu')->name('restaurantDetailMenu');
                Route::post('restaurant/reservation/save', 'RestaurantController@save')->name('saveRestaurant');
                Route::post('restaurant/reservation/complete', 'RestaurantController@complete')->name('completeRestaurant');
                Route::get('restaurant/story', 'RestaurantController@getStory')->name('getStory');
                Route::get('restaurant/searchBox', 'RestaurantController@searchBox')->name('searchBox');
                Route::get('restaurant/recommend', 'RestaurantController@getRecommendation')->name('getRecommendation');
                Route::post('restaurant/reservation/change', 'RestaurantController@change')->name('changeRestaurant');
                Route::get('restaurant/reservation/calcPriceMenu', 'RestaurantController@calcPriceMenu')->name('calcPriceMenu');
                Route::get('restaurant/reservation/calcCancelFee', 'RestaurantController@calcCancelFee')->name('calcCancelFee');
                Route::post('restaurant/reservation/cancel', 'RestaurantController@cancel')->name('cancelRestaurant');
                Route::get('restaurant/menuVacancy', 'RestaurantController@menuVacancy')->name('menuVavancy');
                Route::post('reservation/directPayment', 'RestaurantController@directPayment')->name('directPayment');

                // mypage
                Route::post('reservation', 'AuthController@getMypage')->name('getReservation');
                Route::post('reservation/review', 'AuthController@registerReview')->name('registerReview');

                // favorite
                Route::get('favorite', 'FavoriteController@get')->name('getFavorite');
                Route::group(['middleware' => 'userAuth'], function () {
                    Route::post('favorite/register', 'FavoriteController@register')->name('registerFavorite');
                    Route::post('favorite/delete', 'FavoriteController@delete')->name('deleteFavorite');
                });

                // store
                Route::get('store/{id}/takeoutMenu', 'StoreController@getStoreTakeoutMenu')->name('getStoreTakeoutMenu');
                Route::get('store/{id}/restaurantMenu', 'StoreController@getStoreRestaurantMenu')->name('getStoreRestaurantMenu');
                Route::get('store/{id}/breadcrumb', 'StoreController@getBreadcrumb')->name('getBreadcrumb');
                Route::get('store/{id}/cancelPolicy', 'StoreController@getCancelPolicy')->name('getCancelPolicy');
                Route::get('store/{id}/review', 'StoreController@getStoreReview')->name('getStoreReview');
                Route::get('store/{id}/image', 'StoreController@getStoreImage')->name('getStoreImage');
                Route::get('store/search', 'StoreController@storeSearch')->name('storeSearch');
                Route::get('store/{id}/buffet', 'StoreController@getStoreBuffet')->name('getStoreBuffet');

                Route::get('store/{id}', 'StoreController@get')->name('getStore');

                // dish up
                Route::group(['middleware' => 'staffAuth'], function () {
                    Route::post('dish-up/startCooking', 'DishUpController@startCooking')->name('startCooking');
                    Route::get('dish-up/list', 'DishUpController@list')->name('listDishUp');
                });
                Route::post('dish-up/login', 'DishUpController@login')->name('staffLogin');
                Route::get('dish-up/login', 'DishUpController@checkLogin')->name('checkStaffLogin');
                Route::post('dish-up/logout', 'DishUpController@logout')->name('staffLogout');

                Route::group(['prefix' => 'auth'], function () {
                    // auth
                    Route::get('login', 'AuthController@checkLogin')->name('checkLogin');
                    Route::post('login', 'AuthController@login')->name('login');
                    Route::post('logout', 'AuthController@logout')->name('logout');
                });

                Route::post('payment/authCallback', 'UtilController@saveOrderCode')->name('requestPaymentAuthCallback');
            });
        });
        //callReach
        Route::post('receive/callreach', 'CallReachController@receiveResult')->name('receiveCallReachResult');
    });
    Route::group(['prefix' => 'admin'], function () {
        Route::group(
            [
                'prefix' => 'v1',
                'namespace' => 'Api\v1',
            ],
            function () {
                Route::get('area', 'AreaController@getAreaAdmin')->name('getArea');

                // new入金一覧 与信キャンセル
                Route::post('newpayment/yoshin_cancel', 'PaymentController@yoshinCancel');
                // new入金一覧 計上
                Route::post('newpayment/card_capture', 'PaymentController@cardCapture');
                // new入金一覧経由 入金情報
                Route::post('newpayment/review', 'PaymentController@reviewPayment');
            });
    });
});
