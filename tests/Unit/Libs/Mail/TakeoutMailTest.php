<?php

namespace Tests\Unit\Libs\Mail;

use App\Libs\Mail\TakeoutMail;
use App\Models\CmThApplication;
use App\Models\CmThApplicationDetail;
use App\Models\MailDBQueue;
use App\Models\Menu;
use App\Models\OpeningHour;
use App\Models\Option;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationOption;
use App\Models\ReservationStore;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TakeoutMailTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testCompleteReservationForClient()
    {
        $fromAddress = config('restaurant.mail.devClientFrom');
        if (\App::environment('production')) {
            $fromAddress = config('restaurant.mail.prdClientFrom');
        }

        list($reservation, $cmThApplication) = $this->_createData();

        // 送信内容をMailDBQueueに書き込む（店舗用）
        $restaurantMail = new TakeoutMail($reservation->id);
        $restaurantMail->completeReservationForClient('gourmet-teststore@adventure-inc.co.jp');

        // 送信内容が登録されていることを確認
        $result = MailDBQueue::where('cm_application_id', $cmThApplication->cm_application_id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('【スカイチケットグルメ】ご注文入りました！', $result[0]['subject']);
        $this->assertSame('gourmet-teststore@adventure-inc.co.jp', $result[0]['to_address_enc']);
        $this->assertSame($fromAddress, $result[0]['from_address_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertTrue(strpos($result[0]['message_enc'], 'お客様から「テスト店舗」様へ、ご注文をいただきました。') !== false);
        $this->assertTrue(strpos($result[0]['message_enc'], '10/10 (土) 9:00') !== false);                      // 商品のお受け取り日時
        $this->assertTrue(strpos($result[0]['message_enc'], '合計(税込）￥2,300') !== false);                    // お支払い金額
        $this->assertTrue(strpos($result[0]['message_enc'], '〇テストメニュー    ¥1,000 x 2') !== false);         // メニュー
        $this->assertTrue(strpos($result[0]['message_enc'], 'オプション  ¥300') !== false);                      // オプション合計
        $this->assertTrue(strpos($result[0]['message_enc'], 'ご飯の量:多め    ¥150 x 2') !== false);             // オプション内訳
        $this->assertTrue(strpos($result[0]['message_enc'], 'TO' . $reservation->id) !== false);                // 予約番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'グルメ 太郎') !== false);                            // お客様名
        $this->assertTrue(strpos($result[0]['message_enc'], '090-1111-2222') !== false);                        // お客様電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'gourmet-test@adventure-inc.co.jp') !== false);     // お客様メールアドレス
    }

    public function testCompleteReservationForUser()
    {
        list($reservation, $cmThApplication) = $this->_createData();

        // 送信内容をMailDBQueueに書き込む（お客様用）
        $restaurantMail = new TakeoutMail($reservation->id);
        $restaurantMail->completeReservationForUser();

        // 送信内容が登録されていることを確認
        $result = MailDBQueue::where('cm_application_id', $cmThApplication->cm_application_id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('【スカイチケットグルメ】ご注文ありがとうございました！', $result[0]['subject']);
        $this->assertSame('gourmet-test@adventure-inc.co.jp', $result[0]['to_address_enc']);
        $this->assertSame(config('takeout.mail.from'), $result[0]['from_address_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertTrue(strpos($result[0]['message_enc'], '「テスト店舗」のご注文を受け付けました。') !== false);
        $this->assertTrue(strpos($result[0]['message_enc'], '10/10 (土) 9:00') !== false);                      // 商品のお受け取り日時
        $this->assertTrue(strpos($result[0]['message_enc'], '合計(税込）￥2,300') !== false);                    // お支払い金額
        $this->assertTrue(strpos($result[0]['message_enc'], '〇テストメニュー    ¥1,000 x 2') !== false);         // メニュー
        $this->assertTrue(strpos($result[0]['message_enc'], 'オプション  ¥300') !== false);                      // オプション合計
        $this->assertTrue(strpos($result[0]['message_enc'], 'ご飯の量:多め    ¥150 x 2') !== false);             // オプション内訳
        $this->assertTrue(strpos($result[0]['message_enc'], 'テスト店舗') !== false);                            // 店舗名
        $this->assertTrue(strpos($result[0]['message_enc'], '〒123-4567') !== false);                           // 店舗電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], '東京都渋谷区テスト住所1-2-3') !== false);              // 店舗住所
        $this->assertTrue(strpos($result[0]['message_enc'], '06-1111-2222') !== false);                         // 店舗電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], '08:00:00~14:00:00/17:00:00~21:00:00') !== false);  // 店舗営業時間
        $this->assertTrue(strpos($result[0]['message_enc'], 'TO' . $reservation->id) !== false);                // 予約番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'グルメ 太郎') !== false);                            // お客様名
        $this->assertTrue(strpos($result[0]['message_enc'], '090-1111-2222') !== false);                        // お客様電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'gourmet-test@adventure-inc.co.jp') !== false);     // お客様メールアドレス
    }

    public function testConfirmReservationByClient()
    {
        list($reservation, $cmThApplication) = $this->_createData();

        // 送信内容をMailDBQueueに書き込む（お客様用）
        $restaurantMail = new TakeoutMail($reservation->id);
        $restaurantMail->confirmReservationByClient();

        // 送信内容が登録されていることを確認
        $result = MailDBQueue::where('cm_application_id', $cmThApplication->cm_application_id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('【スカイチケットグルメ】ご注文が確定しました！', $result[0]['subject']);
        $this->assertSame('gourmet-test@adventure-inc.co.jp', $result[0]['to_address_enc']);
        $this->assertSame(config('takeout.mail.from'), $result[0]['from_address_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertTrue(strpos($result[0]['message_enc'], '「テスト店舗」のご注文が確定しました。') !== false);
        $this->assertTrue(strpos($result[0]['message_enc'], '10/10 (土) 9:00') !== false);                      // 商品のお受け取り日時
        $this->assertTrue(strpos($result[0]['message_enc'], '合計(税込）￥2,300') !== false);                     // お支払い金額
        $this->assertTrue(strpos($result[0]['message_enc'], '〇テストメニュー    ¥1,000 x 2') !== false);         // メニュー
        $this->assertTrue(strpos($result[0]['message_enc'], 'オプション  ¥300') !== false);                      // オプション合計
        $this->assertTrue(strpos($result[0]['message_enc'], 'ご飯の量:多め    ¥150 x 2') !== false);             // オプション内訳
        $this->assertTrue(strpos($result[0]['message_enc'], 'テスト店舗') !== false);                            // 店舗名
        $this->assertTrue(strpos($result[0]['message_enc'], '〒123-4567') !== false);                           // 店舗電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], '東京都渋谷区テスト住所1-2-3') !== false);              // 店舗住所
        $this->assertTrue(strpos($result[0]['message_enc'], '06-1111-2222') !== false);                         // 店舗電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], '08:00:00~14:00:00/17:00:00~21:00:00') !== false);  // 店舗営業時間
        $this->assertTrue(strpos($result[0]['message_enc'], 'TO' . $reservation->id) !== false);                // 予約番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'グルメ 太郎') !== false);                            // お客様名
        $this->assertTrue(strpos($result[0]['message_enc'], '090-1111-2222') !== false);                        // お客様電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'gourmet-test@adventure-inc.co.jp') !== false);     // お客様メールアドレス
    }

    public function testCloseReservation()
    {
        list($reservation, $cmThApplication) = $this->_createData();

        // 送信内容をMailDBQueueに書き込む（お客様用）
        $restaurantMail = new TakeoutMail($reservation->id);
        $restaurantMail->closeReservation();

        // 送信内容が登録されていることを確認
        $result = MailDBQueue::where('cm_application_id', $cmThApplication->cm_application_id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('【スカイチケットグルメ】ご利用ありがとうございました！ご感想をお聞かせください', $result[0]['subject']);
        $this->assertSame('gourmet-test@adventure-inc.co.jp', $result[0]['to_address_enc']);
        $this->assertSame(config('takeout.mail.from'), $result[0]['from_address_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertTrue(strpos($result[0]['message_enc'], '簡単なアンケート入力をお願いしております。') !== false);
        $this->assertTrue(strpos($result[0]['message_enc'], '10/10 (土) 9:00') !== false);                      // 商品のお受け取り日時
        $this->assertTrue(strpos($result[0]['message_enc'], '合計(税込）￥2,300') !== false);                     // お支払い金額
        $this->assertTrue(strpos($result[0]['message_enc'], '〇テストメニュー    ¥1,000 x 2') !== false);         // メニュー
        $this->assertTrue(strpos($result[0]['message_enc'], 'オプション  ¥300') !== false);                      // オプション合計
        $this->assertTrue(strpos($result[0]['message_enc'], 'ご飯の量:多め    ¥150 x 2') !== false);             // オプション内訳
        $this->assertTrue(strpos($result[0]['message_enc'], 'テスト店舗') !== false);                            // 店舗名
        $this->assertTrue(strpos($result[0]['message_enc'], '〒123-4567') !== false);                           // 店舗電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], '東京都渋谷区テスト住所1-2-3') !== false);              // 店舗住所
        $this->assertTrue(strpos($result[0]['message_enc'], '06-1111-2222') !== false);                         // 店舗電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], '08:00:00~14:00:00/17:00:00~21:00:00') !== false);  // 店舗営業時間
        $this->assertTrue(strpos($result[0]['message_enc'], 'TO' . $reservation->id) !== false);                // 予約番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'グルメ 太郎') !== false);                            // お客様名
        $this->assertTrue(strpos($result[0]['message_enc'], '090-1111-2222') !== false);                        // お客様電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'gourmet-test@adventure-inc.co.jp') !== false);     // お客様メールアドレス
    }

    public function testChangeReservationForUser()
    {
        list($reservation, $cmThApplication) = $this->_createData();
        $oldPickUpDatetime = $reservation->pick_up_datetime;
        $newPickUpDatetime = '2099-10-11 12:30:00';

        // 送信内容をMailDBQueueに書き込む（お客様用）
        $restaurantMail = new TakeoutMail($reservation->id);
        $restaurantMail->changeReservationForUser($oldPickUpDatetime, $newPickUpDatetime);

        // 送信内容が登録されていることを確認
        $result = MailDBQueue::where('cm_application_id', $cmThApplication->cm_application_id)->get();
        // print_r($result->toArray());
        $this->assertCount(1, $result);
        $this->assertSame('【スカイチケットグルメ】ご注文変更のお知らせです。', $result[0]['subject']);
        $this->assertSame('gourmet-test@adventure-inc.co.jp', $result[0]['to_address_enc']);
        $this->assertSame(config('takeout.mail.from'), $result[0]['from_address_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertTrue(strpos($result[0]['message_enc'], 'ご注文された「テスト店舗」から、内容変更の連絡がありました。') !== false);
        $this->assertTrue(strpos($result[0]['message_enc'], '10/10 (土) 9:00　　→　　10/11 (日) 12:30') !== false);  // 商品のお受け取り日時
        $this->assertTrue(strpos($result[0]['message_enc'], '合計(税込）￥2,300') !== false);                        // お支払い金額
        $this->assertTrue(strpos($result[0]['message_enc'], '〇テストメニュー    ¥1,000 x 2') !== false);            // メニュー
        $this->assertTrue(strpos($result[0]['message_enc'], 'オプション  ¥300') !== false);                         // オプション合計
        $this->assertTrue(strpos($result[0]['message_enc'], 'ご飯の量:多め    ¥150 x 2') !== false);                // オプション内訳
        $this->assertTrue(strpos($result[0]['message_enc'], 'テスト店舗') !== false);                               // 店舗名
        $this->assertTrue(strpos($result[0]['message_enc'], '〒123-4567') !== false);                             // 店舗電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], '東京都渋谷区テスト住所1-2-3') !== false);               // 店舗住所
        $this->assertTrue(strpos($result[0]['message_enc'], '06-1111-2222') !== false);                          // 店舗電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], '08:00:00~14:00:00/17:00:00~21:00:00') !== false);   // 店舗営業時間
        $this->assertTrue(strpos($result[0]['message_enc'], 'TO' . $reservation->id) !== false);                 // 予約番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'グルメ 太郎') !== false);                             // お客様名
        $this->assertTrue(strpos($result[0]['message_enc'], '090-1111-2222') !== false);                         // お客様電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'gourmet-test@adventure-inc.co.jp') !== false);      // お客様メールアドレス
    }

    public function testCancelReservationForUser()
    {
        list($reservation, $cmThApplication) = $this->_createData();

        // 送信内容をMailDBQueueに書き込む（お客様用）
        $restaurantMail = new TakeoutMail($reservation->id);
        $restaurantMail->cancelReservationForUser();

        // 送信内容が登録されていることを確認
        $result = MailDBQueue::where('cm_application_id', $cmThApplication->cm_application_id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('【スカイチケットグルメ】ご注文キャンセルのお知らせです。', $result[0]['subject']);
        $this->assertSame('gourmet-test@adventure-inc.co.jp', $result[0]['to_address_enc']);
        $this->assertSame(config('takeout.mail.from'), $result[0]['from_address_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertTrue(strpos($result[0]['message_enc'], '今回ご注文いただいたメニューは店舗事情により、ご注文を確定することができませんでした。') !== false);
        $this->assertTrue(strpos($result[0]['message_enc'], '10/10 (土) 9:00') !== false);                      // 商品のお受け取り日時
        $this->assertTrue(strpos($result[0]['message_enc'], '合計(税込）￥2,300') !== false);                     // お支払い金額
        $this->assertTrue(strpos($result[0]['message_enc'], '〇テストメニュー    ¥1,000 x 2') !== false);         // メニュー
        $this->assertTrue(strpos($result[0]['message_enc'], 'オプション  ¥300') !== false);                      // オプション合計
        $this->assertTrue(strpos($result[0]['message_enc'], 'ご飯の量:多め    ¥150 x 2') !== false);             // オプション内訳
        $this->assertTrue(strpos($result[0]['message_enc'], 'テスト店舗') !== false);                            // 店舗名
        $this->assertTrue(strpos($result[0]['message_enc'], '〒123-4567') !== false);                           // 店舗電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], '東京都渋谷区テスト住所1-2-3') !== false);              // 店舗住所
        $this->assertTrue(strpos($result[0]['message_enc'], '06-1111-2222') !== false);                         // 店舗電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], '08:00:00~14:00:00/17:00:00~21:00:00') !== false);  // 店舗営業時間
        $this->assertTrue(strpos($result[0]['message_enc'], 'TO' . $reservation->id) !== false);                // 予約番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'グルメ 太郎') !== false);                            // お客様名
        $this->assertTrue(strpos($result[0]['message_enc'], '090-1111-2222') !== false);                        // お客様電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'gourmet-test@adventure-inc.co.jp') !== false);     // お客様メールアドレス
    }

    public function testRemindReservationForClient()
    {
        $fromAddress = config('restaurant.mail.devClientFrom');
        if (\App::environment('production')) {
            $fromAddress = config('restaurant.mail.prdClientFrom');
        }

        list($reservation, $cmThApplication) = $this->_createData();

        // 送信内容をMailDBQueueに書き込む（店舗用）
        $restaurantMail = new TakeoutMail($reservation->id);
        $restaurantMail->remindReservationForClient('gourmet-teststore@adventure-inc.co.jp');

        // 送信内容が登録されていることを確認
        $result = MailDBQueue::where('cm_application_id', $cmThApplication->cm_application_id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('【スカイチケットグルメ】30分後に注文が入っております！受注確定させて下さい。', $result[0]['subject']);
        $this->assertSame('gourmet-teststore@adventure-inc.co.jp', $result[0]['to_address_enc']);
        $this->assertSame($fromAddress, $result[0]['from_address_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertTrue(strpos($result[0]['message_enc'], 'お客様からの注文を確定させてください') !== false);
        $this->assertTrue(strpos($result[0]['message_enc'], '10/10 (土) 9:00') !== false);                      // 商品のお受け取り日時
        $this->assertTrue(strpos($result[0]['message_enc'], '合計(税込）￥2,300') !== false);                     // お支払い金額
        $this->assertTrue(strpos($result[0]['message_enc'], '〇テストメニュー    ¥1,000 x 2') !== false);         // メニュー
        $this->assertTrue(strpos($result[0]['message_enc'], 'オプション  ¥300') !== false);                      // オプション合計
        $this->assertTrue(strpos($result[0]['message_enc'], 'ご飯の量:多め    ¥150 x 2') !== false);             // オプション内訳
        $this->assertTrue(strpos($result[0]['message_enc'], 'TO' . $reservation->id) !== false);                // 予約番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'グルメ 太郎') !== false);                            // お客様名
        $this->assertTrue(strpos($result[0]['message_enc'], '090-1111-2222') !== false);                        // お客様電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'gourmet-test@adventure-inc.co.jp') !== false);     // お客様メールアドレス
    }

    private function _createData($total = 2300)
    {
        $store = new Store();
        $store->name = 'テスト店舗';
        $store->postal_code = '123-4567';
        $store->address_1 = '東京都';
        $store->address_2 = '渋谷区';
        $store->address_3 = 'テスト住所1-2-3';
        $store->tel = '06-1111-2222';
        $store->save();

        $openingHour = new OpeningHour();
        $openingHour->store_id = $store->id;
        $openingHour->week = '11111100';
        $openingHour->start_at = '08:00:00';
        $openingHour->end_at = '14:00:00';
        $openingHour->opening_hour_cd = 'ALL_DAY';
        $openingHour->save();

        $openingHour2 = new OpeningHour();
        $openingHour2->store_id = $store->id;
        $openingHour2->week = '11111111';
        $openingHour2->start_at = '17:00:00';
        $openingHour2->end_at = '21:00:00';
        $openingHour2->opening_hour_cd = 'ALL_DAY';
        $openingHour2->save();

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->name = 'テストメニュー';
        $menu->save();

        $option = new Option();
        $option->menu_id = $menu->id;
        $option->keyword = 'ご飯の量';
        $option->contents = '多め';
        $option->save();

        $reservation = new Reservation();
        $reservation->pick_up_datetime = '2099-10-10 09:00:00';
        $reservation->last_name = 'グルメ';
        $reservation->first_name = '太郎';
        $reservation->tel = '090-1111-2222';
        $reservation->email = 'gourmet-test@adventure-inc.co.jp';
        $reservation->total = $total;
        $reservation->persons = 1;
        $reservation->save();

        $reservationStore = new ReservationStore();
        $reservationStore->reservation_id = $reservation->id;
        $reservationStore->store_id = $store->id;
        $reservationStore->name = 'テスト店舗';
        $reservationStore->save();

        $reservationMenu = new ReservationMenu();
        $reservationMenu->reservation_id = $reservation->id;
        $reservationMenu->menu_id = $menu->id;
        $reservationMenu->name = 'テストメニュー';
        $reservationMenu->count = 2;
        $reservationMenu->unit_price = 1000;
        $reservationMenu->price = 2000;
        $reservationMenu->save();

        $reservationOption = new ReservationOption();
        $reservationOption->option_id = $option->id;
        $reservationOption->reservation_menu_id = $reservationMenu->id;
        $reservationOption->keyword = 'ご飯の量';
        $reservationOption->contents = '多め';
        $reservationOption->count = 2;
        $reservationOption->unit_price = 150;
        $reservationOption->price = 300;
        $reservationOption->save();

        $cmThApplication = new CmThApplication();
        $cmThApplication->user_id = 1;
        $cmThApplication->lang_id = 1;
        $cmThApplication->save();

        $cmThApplicationDetail = new CmThApplicationDetail();
        $cmThApplicationDetail->cm_application_id = $cmThApplication->cm_application_id;
        $cmThApplicationDetail->application_id = $reservation->id;
        $cmThApplicationDetail->service_cd = 'gm';
        $cmThApplicationDetail->save();

        return [$reservation, $cmThApplication];
    }
}
