<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/****
 * ↓テスト用 後で消す
 ****/
Route::post('/upload_test', 'TakeoutController@upload');
Route::get('/up_form', 'TakeoutController@upForm');

Route::get('/pdf', 'TakeoutController@pdf');
/****
 * ↑テスト用 後で消す
 ****/

Route::group(['namespace' => 'System', 'as' => 'system'], function () {
    Route::get('/health', 'HealthCheckController@index')->name('health');
});

/*******************************************************
 * 管理画面
 *******************************************************/
// 管理画面
Route::group(['prefix' => 'admin', 'namespace' => 'Admin', 'as' => 'admin.'], function () {
    // ログイン画面
    Route::get('/', 'LoginController@index')->name('index');
    // ログイン処理
    Route::post('login/', 'LoginController@login')->name('login');
    // ログアウト処理
    Route::get('logout/', 'LoginController@logout')->name('logout');

    // 認証必要なルートグループ
    Route::group(['middleware' => 'auth'], function () {
        // 初回強制パスワード変更チェックとアクセス権限チェックが必要なルートグループ
        Route::group(['middleware' => ['firstlogin', 'adminAuthorityPage']], function () {
            // 予約一覧
            Route::get('reservation/', 'ReservationController@index')->name('reservation');
            // 予約詳細
            Route::get('reservation/edit/{id}', 'ReservationController@editForm')->name('reservation.edit');
            // 予約詳細リダイレクト
            Route::get('reservation/edit/cm_application/{id}', 'ReservationController@redirectEditForm')->name('reservation.redirectEdit');
            // 対応履歴追加(Ajax)
            Route::post('reservation/save_message_board', 'ReservationController@saveMessageBoard');
            // 予約者情報変更(Ajax)
            Route::post('reservation/update_reservation_info', 'ReservationController@updateReservationInfo');
            // 予約キャンセル(Ajax)
            Route::post('reservation/cancel_reservation', 'ReservationController@cancelReservation');
            // お客様都合の予約キャンセル(Ajax)
            Route::post('reservation/cancel_reservation_for_user', 'ReservationController@cancelReservationForUser');
            // お店都合の予約キャンセル(Ajax)
            Route::post('reservation/cancel_reservation_for_admin', 'ReservationController@cancelReservationForAdmin');
            // 申込者情報変更(Ajax)
            Route::post('reservation/update_delegate_info', 'ReservationController@updateDelegateInfo');
            // 予約完了メール再送(Ajax)
            Route::post('reservation/send_reservation_mail', 'ReservationController@sendReservationMail');
            Route::post('reservation/clear_admin_change_info', 'ReservationController@clearAdminChangeInfo');

//            // キャンセル一覧
//            Route::get('cancels/', 'CancelController@index');
//
            // 入金一覧
            Route::get('payment/', 'PaymentController@index');
            Route::post('payment/yoshin_cancel', 'PaymentController@yoshinCancel');
            Route::post('payment/card_capture', 'PaymentController@cardCapture');
            Route::get('payment/detail/index/{id}', 'PaymentController@detail');
            Route::post('payment/detail/status_payment', 'PaymentController@statusPayment');
            Route::post('payment/detail/cancel_detail_add', 'PaymentController@cancelDetailAdd');
            Route::post('payment/detail/exec_refund', 'PaymentController@execRefund');

            // new入金一覧
            Route::get('newpayment/', 'NewPaymentController@index');
            Route::get('newpayment/detail/{reservationNumber}', 'PaymentController@detail');

            // スタッフ一覧
            Route::get('staff/', 'StaffController@index')->name('staff');
            // スタッフ追加
            Route::get('staff/add', 'StaffController@addForm')->name('staff.add');
            Route::post('staff/add', 'StaffController@add')->name('staff.add');
            // 店舗一覧取得(by スタッフ縛り)
            Route::get('staff/storeList/{id}', 'StaffController@storeList')->name('staff.store.list');

            // 精算会社一覧
            Route::get('settlement_company', 'SettlementCompanyController@index')->name('settlementCompany');
            // 精算会社追加
            Route::get('settlement_company/add', 'SettlementCompanyController@addForm')->name('settlementCompany.add');
            Route::post('settlement_company/add', 'SettlementCompanyController@add')->name('settlementCompany.add');
            // 精算会社編集
            Route::get('settlement_company/edit/{id}', 'SettlementCompanyController@editForm')->name('settlementCompany.edit');
            Route::post('settlement_company/edit/{id}', 'SettlementCompanyController@edit')->name('settlementCompany.edit');

            // 販売手数料一覧
            Route::get('settlement_company/{settlementCompanyId}/commission_rate', 'CommissionRateController@index')->name('commissionRate');
            // 販売手数料追加
            Route::get('settlement_company/{settlementCompanyId}/commission_rate/add', 'CommissionRateController@addForm')->name('commissionRate.add');
            Route::post('settlement_company/{settlementCompanyId}/commission_rate/add', 'CommissionRateController@add')->name('commissionRate.add');
            // 販売手数料編集
            Route::get('settlement_company/{settlementCompanyId}/commission_rate/edit/{id}', 'CommissionRateController@editForm')->name('commissionRate.edit');
            Route::post('settlement_company/{settlementCompanyId}/commission_rate/edit/{id}', 'CommissionRateController@edit')->name('commissionRate.edit');
            // 販売手数料削除
            Route::post('settlement_company/{settlementCompanyId}/commission_rate/delete/{id}', 'CommissionRateController@delete')->name('commissionRate.delete');

            // 精算額集計
            Route::get('settlement_aggregate', 'SettlementAggregateController@index')->name('settlementAggregate');

            // 精算確認一覧
            Route::get('settlement_confirm', 'SettlementConfirmController@index')->name('settlementConfirm');
            // 精算確認PDF
            Route::get('settlement_confirm/pdf_download', 'SettlementConfirmController@pdfDownload')->name('settlementConfirm.download');

            // 店舗管理一覧/基本情報管理
            Route::get('store', 'StoreController@index')->name('store');
            // 社内管理者、社内一般者のみ許可
            Route::group(['middleware' => ['can:inAndOutHouseGeneral-only']], function () {
                // 店舗追加
                Route::get('store/add', 'StoreController@addForm')->name('store.add');
                Route::post('store/add', 'StoreController@add')->name('store.add');
            });
            // 店舗情報編集
            Route::get('store/{id}/edit', 'StoreController@editForm')->name('store.edit');
            Route::post('store/{id}/edit', 'StoreController@edit')->name('store.edit');
            // 店舗削除
            Route::post('store/{id}/delete', 'StoreController@delete')->name('store.delete');

            // 店舗公開
            Route::post('store/{id}/publish', 'StoreController@setPublish')->name('store.publish');
            // 店舗非公開
            Route::post('store/{id}/private', 'StoreController@setPrivate')->name('store.private');

            // 店舗こだわり・料理ジャンル一覧
            Route::get('store/{id}/genre/edit', 'StoreGenreController@editForm')->name('store.genre.edit'); //既にリリース済みのため、url,nameの変更しない
            // 店舗こだわりジャンル編集/追加
            Route::post('store/{id}/genre/edit', 'StoreGenreController@edit')->name('store.genre.edit'); //既にリリース済みのため、url,nameの変更しない
            Route::get('store/{id}/genre/detailed/add', 'StoreGenreController@addForm')->name('store.genre.add'); //既にリリース済みのため、nameの変更しない
            Route::post('store/{id}/genre/detailed/add', 'StoreGenreController@add')->name('store.genre.add'); //既にリリース済みのため、nameの変更しない
            // 店舗こだわり・料理ジャンル削除
            Route::post('store/genre/delete/{id}', 'StoreGenreController@delete')->name('store.genre.delete'); // 消すのはジャンルであって店舗ではないのでidの位置はstoreの下ではない

            //キャンセル料一覧
            Route::get('store/{id}/cancel_fee', 'StoreCancelFeeController@index')->name('store.cancelFee');
            // キャンセル料/追加
            Route::get('store/{id}/cancel_fee/add', 'StoreCancelFeeController@addForm')->name('store.cancelFee.addForm');
            Route::post('store/cancel_fee/add', 'StoreCancelFeeController@add')->name('store.cancelFee.add');
            //キャンセル料/編集
            Route::get('store/{id}/cancel_fee/{cancel_fee_id}/edit', 'StoreCancelFeeController@editForm')->name('store.cancelFee.editForm');
            Route::post('store/cancel_fee/{cancel_fee_id}/edit', 'StoreCancelFeeController@edit')->name('store.cancelFee.edit');
            //キャンセル料削除
            Route::post('store/{id}/cancel_fee/{cancel_fee_id}/delete', 'StoreCancelFeeController@delete')->name('store.cancelFee.delete');

            //店舗API/編集
            Route::get('store/{id}/api/edit', 'StoreApiController@editForm')->name('store.api.editForm');
            Route::post('store/{id}/api/edit', 'StoreApiController@edit')->name('store.api.edit');
            //店舗API/削除
            Route::post('store/{id}/api/delete', 'StoreApiController@delete')->name('store.api.delete');

            //店舗電話予約/編集
            Route::post('store/{id}/call_tracker/edit', 'StoreApiController@callEdit')->name('store.call_tracker.edit');
            Route::post('store/{id}/call_tracker/delete', 'StoreApiController@callDelete')->name('store.call_tracker.delete');

            //店舗電話通知/編集
            Route::post('store/{id}/tel_support/edit', 'StoreApiController@telSupportEdit')->name('store.tel_support.edit');

            // 店舗空席
            Route::get('store/vacancy/{id}', 'StoreVacancyController@index')->name('store.vacancy');
            Route::post('store/vacancy/{id}/copy', 'StoreVacancyController@copy')->name('store.vacancy.copy');
            // 空席詳細時間設定
            Route::get('store/vacancy/{id}/edit', 'StoreVacancyController@editForm')->name('store.vacancy.editForm');
            Route::post('store/vacancy/{id}/edit', 'StoreVacancyController@edit')->name('store.vacancy.edit');
            // 空席詳細時間一括設定
            Route::get('store/vacancy/{id}/editAllForm', 'StoreVacancyController@editAllForm')->name('store.vacancy.editAllForm');
            Route::post('store/vacancy/{id}/editAll', 'StoreVacancyController@editAll')->name('store.vacancy.editAll');

            // 店舗料理ジャンル編集/追加
            Route::post('store/{id}/genre/cooking/edit', 'StoreGenreController@cookingEdit')->name('store.genre.cooking.edit');
            Route::get('store/{id}/genre/cooking/add', 'StoreGenreController@cookingAddForm')->name('store.genre.cooking.add');
            Route::post('store/{id}/genre/cooking/add', 'StoreGenreController@cookingAdd')->name('store.genre.cooking.add');

            // 店舗画像一覧/編集/削除
            Route::get('store/{id}/image/edit', 'StoreImageController@editForm')->name('store.image.editForm');
            Route::post('store/{id}/image/edit', 'StoreImageController@edit')->name('store.image.edit');
            Route::post('store/{id}/image/delete/{image_id}', 'StoreImageController@delete')->name('store.image.delete');
            // 店舗画像追加
            Route::get('store/{id}/image/add', 'StoreImageController@addForm')->name('store.image.addForm');
            Route::post('store/image/add', 'StoreImageController@add')->name('store.image.add');
            // 店舗営業時間追加
            Route::get('store/{id}/opening_hour/add', 'StoreOpeningHourController@addForm')->name('store.open.addForm');
            Route::post('store/opening_hour/add/', 'StoreOpeningHourController@add')->name('store.open.add');
            // 店舗営業時間編集/削除
            Route::get('store/{id}/opening_hour/edit', 'StoreOpeningHourController@editForm')->name('store.open.editForm');
            Route::post('store/{id}/opening_hour/edit', 'StoreOpeningHourController@edit')->name('store.open.edit');
            Route::post('store/{id}/opening_hour/delete/{openingHour_id}', 'StoreOpeningHourController@delete')->name('store.open.delete');

            // メニュー一覧
            Route::get('menu', 'MenuController@index')->name('menu');
            // メニュー追加
            Route::get('menu/add', 'MenuController@addForm')->name('menu.add');
            Route::post('menu/add', 'MenuController@add')->name('menu.add');
            // メニュー編集
            Route::get('menu/{id}/edit', 'MenuController@editForm')->name('menu.edit');
            Route::post('menu/{id}/edit', 'MenuController@edit')->name('menu.edit');
            // メニュー削除
            Route::post('menu/{id}/delete', 'MenuController@delete')->name('menu.delete');
            // メニュー公開
            Route::post('menu/{id}/publish', 'MenuController@setPublish')->name('menu.publish');
            // メニュー非公開
            Route::post('menu/{id}/private', 'MenuController@setPrivate')->name('menu.private');
            // メニュージャンル編集/追加/削除
            Route::get('menu/{id}/genre/edit', 'MenuGenreController@editForm')->name('menu.genre.edit');
            Route::post('menu/{id}/genre/edit', 'MenuGenreController@edit')->name('menu.genre.edit');
            Route::get('menu/{id}/genre/add', 'MenuGenreController@addForm')->name('menu.genre.add');
            Route::post('menu/{id}/genre/add', 'MenuGenreController@add')->name('menu.genre.add');
            Route::post('menu/genre/delete/{id}', 'MenuGenreController@delete')->name('menu.genre.delete'); // 消すのはジャンルであってメニューではないのでidの位置はmenuの下ではない
            // メニュー料金 追加
            Route::get('menu/{id}/price/add', 'MenuPriceController@addForm')->name('menu.price.addForm');
            Route::post('menu/{id}/price/add', 'MenuPriceController@add')->name('menu.price.add');
            // メニュー料金 編集/削除
            Route::get('menu/{id}/price/edit', 'MenuPriceController@editForm')->name('menu.price.editForm');
            Route::post('menu/{id}/price/edit', 'MenuPriceController@edit')->name('menu.price.edit');
            Route::post('menu/{id}/price/delete/{price_id}', 'MenuPriceController@delete')->name('menu.price.delete');
            // メニューオプション 一覧
            Route::get('menu/{id}/option', 'MenuOptionController@index')->name('menu.option');
            // メニューオプション お好み/トッピング 削除
            Route::post('menu/{id}/option/delete/{option_id}', 'MenuOptionController@delete')->name('menu.option.delete');
            // メニューオプション お好み 項目追加
            Route::get('menu/{id}/option/okonomi/add', 'MenuOptionController@okonomiKeywordAddForm')->name('menu.option.okonomiKeyword.addForm');
            Route::post('menu/{id}/option/okonomi/add', 'MenuOptionController@okonomiKeywordAdd')->name('menu.option.okonomiKeyword.add');
            // メニューオプション お好み 内容追加
            Route::post('menu/{id}/option/okonomi/contents/add', 'MenuOptionController@okonomiContentsAdd')->name('menu.option.okonomiContents.add');
            // メニューオプション お好み 編集
            Route::get('menu/{id}/option/okonomi/edit', 'MenuOptionController@okonomiEditForm')->name('menu.option.okonomi.editForm');
            // メニューオプション お好み 更新
            Route::post('menu/option/okonomi/edit', 'MenuOptionController@okonomiEdit')->name('menu.option.okonomi.edit');
            // メニューオプション トッピング 追加
            Route::get('menu/{id}/option/topping/add', 'MenuOptionController@toppingAddForm')->name('menu.option.topping.addForm');
            Route::post('menu/option/topping/add', 'MenuOptionController@toppingAdd')->name('menu.option.topping.add');
            // メニューオプション トッピング 編集
            Route::post('menu/{id}/option/topping/edit', 'MenuOptionController@toppingEdit')->name('menu.option.topping.edit');
            Route::post('menu/{id}/option/edit', 'MenuOptionController@edit')->name('menu.option.edit');
            // メニュー在庫 一覧
            Route::get('menu/stock/{id}', 'MenuStockController@index')->name('menu.stock');
            // メニュー在庫 作成
            Route::post('menu/stock/add', 'MenuStockController@add')->name('menu.stock.add');
            // メニュー在庫 編集
            Route::post('menu/stock/edit', 'MenuStockController@edit')->name('menu.stock.edd');
            // メニュー在庫 削除
            Route::post('menu/stock/delete', 'MenuStockController@delete')->name('menu.stock.delete');
            // メニュー在庫 まとめて更新/月
            Route::post('menu/stock/bulk_update', 'MenuStockController@bulkUpdate')->name('menu.stock.bulkUpdate');
            // メニュー在庫 データ取得用(ajax)
            Route::get('menu/stock/get_data/{id}', 'MenuStockController@getData')->name('menu.stock.getData');
            // メニュー画像 一覧/編集/削除
            Route::get('menu/{id}/image/edit', 'MenuImageController@editForm')->name('menu.image.editForm');
            Route::post('menu/{id}/image/edit', 'MenuImageController@edit')->name('menu.image.edit');
            Route::post('menu/{id}/image/delete/{image_id}', 'MenuImageController@delete')->name('menu.image.delete');
            // メニュー画像 追加
            Route::get('menu/{id}/image/add', 'MenuImageController@addForm')->name('menu.image.addForm');
            Route::post('menu/image/add', 'MenuImageController@add')->name('menu.image.add');

            // ジャンル一覧
            Route::get('genre/', 'GenreController@index')->name('genre');
            // エリア一覧
            Route::get('area/', 'AreaController@index')->name('area');
            // 社内管理者、社内一般者のみ許可
            Route::group(['middleware' => ['can:inAndOutHouseGeneral-only']], function () {
                // ジャンル追加
                Route::get('genre/add', 'GenreController@addForm')->name('genre.add');
                Route::post('genre/add', 'GenreController@add')->name('genre.add');
                // ジャンル編集
                Route::get('genre/{id}/edit', 'GenreController@editForm')->name('genre.edit');
                Route::post('genre/{id}/edit', 'GenreController@edit')->name('genre.edit');

                // エリア追加
                Route::get('area/add', 'AreaController@addForm')->name('area.add');
                Route::post('area/add', 'AreaController@add')->name('area.add');
                // エリア編集
                Route::get('area/{id}/edit', 'AreaController@editForm')->name('area.edit');
                Route::post('area/{id}/edit', 'AreaController@edit')->name('area.edit');
            });

            // 駅一覧
            Route::get('station/', 'StationController@index')->name('station');
            Route::post('station/upload', 'StationController@upload')->name('station.upload');
            Route::get('station/status', 'StationController@nowStatus')->name('station.status');

            // ストーリーマスタ一覧
            Route::get('story/', 'StoryController@index')->name('story');
            // ストーリーマスタ追加
            Route::get('story/add', 'StoryController@addForm')->name('story.addForm');
            Route::post('story/add', 'StoryController@add')->name('story.add');
            // ストーリーマスタ編集
            Route::get('story/{id}/edit', 'StoryController@editForm')->name('story.editForm');
            Route::post('story/{id}/edit', 'StoryController@edit')->name('story.edit');
            // ストーリーマスタ削除
            Route::post('story/{id}/delete', 'StoryController@delete')->name('story.delete');

//            // キャンセル料一覧
//            Route::get('cancel_fees/', 'CancelFeesController@index');
//            // キャンセル料追加
//            Route::get('cancel_fees/add', 'CancelFeesController@addForm');
//            Route::post('cancel_fees/add', 'CancelFeesController@add');
//            // キャンセル料編集
//            Route::get('cancel_fees/edit/{id}', 'CancelFeesController@editForm');
//            Route::post('cancel_fees/edit/{id}', 'CancelFeesController@edit');

            // お知らせ一覧
            Route::get('notice/', 'NoticeController@index')->name('notice');
            // お知らせ追加
            Route::get('notice/add', 'NoticeController@addForm')->name('notice.add');
            Route::post('notice/add', 'NoticeController@add')->name('notice.add');
            // お知らせ編集
            Route::get('notice/edit/{id}', 'NoticeController@editForm')->name('notice.edit');
            Route::post('notice/edit/{id}', 'NoticeController@edit')->name('notice.edit');
            // お知らせ詳細
            //Route::get('notice/view/{id}', 'NoticeController@viewForm');
//
//            // 操作履歴一覧
//            Route::get('action_logs/', 'ActionLogsController@index');
//            // 操作履歴閲覧
//            Route::get('action_logs/view/{id}', 'ActionLogsController@viewForm');
//
//            // 祝日一覧
//            Route::get('public_holidays/', 'PublicHolidaysController@index');
//
//            // 売上集計
//            Route::get('sales_summary_reservations/', 'SalesSummaryController@reservations');
//            Route::get('sales_summary_sales/', 'SalesSummaryController@sales');
//            Route::get('sales_summary_cancels/', 'SalesSummaryController@cancels');
//
//            // 精算額集計
//            Route::get('settlement_summary/', 'SettlementSummaryController@index');
//            // 精算後調整データ
//            Route::get('settlement_adjust/', 'SettlementSummaryController@adjust');

            // 環境確認
            Route::get('phpinfo', function () {
                phpinfo();
            });
        });
        // ジャンルリスト(ajax用)
        Route::get('common/genre/list', 'GenreController@list')->name('common.genre.list');

        // エリアリスト(ajax用)
        Route::get('common/area/list', 'AreaController@list')->name('common.area.list');

        // スタッフ編集
        Route::get('staff/edit/{id}', 'StaffController@editForm')->name('staff.edit');
        Route::post('staff/edit/{id}', 'StaffController@edit')->name('staff.edit');

        // パスワード変更
        Route::get('staff/edit_password', 'StaffController@editPasswordForm')->name('staff.editPasswordForm');
        // 初回強制パスワード変更
        Route::get('staff/edit_password_first_login', 'StaffController@editPasswordFirstLoginForm')->name('staff.editPasswordFirstLoginForm');
        // スタッフ編集-パスワードリセット
        Route::post('staff/edit_password/{id}', 'StaffController@editPassword')->name('staff.editPassword');
    });
});
