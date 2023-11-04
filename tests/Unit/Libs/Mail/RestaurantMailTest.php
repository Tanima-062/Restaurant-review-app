<?php

namespace Tests\Unit\Libs\Mail;

use App\Libs\Mail\RestaurantMail;
use App\Models\CancelFee;
use App\Models\CmThApplication;
use App\Models\CmThApplicationDetail;
use App\Models\MailDBQueue;
use App\Models\Menu;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationStore;
use App\Models\Store;
use App\Models\TmpAdminChangeReservation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RestaurantMailTest extends TestCase
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

    public function testCompleteReservationForUser()
    {
        list($reservation, $cmThApplication) = $this->_createData();

        // 送信内容をMailDBQueueに書き込む（お客様用）
        $restaurantMail = new RestaurantMail($reservation->id);
        $restaurantMail->completeReservationForUser();

        // 送信内容が登録されていることを確認
        $result = MailDBQueue::where('cm_application_id', $cmThApplication->cm_application_id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('【スカイチケットグルメ】ご予約ありがとうございました！', $result[0]['subject']);
        $this->assertSame('gourmet-test@adventure-inc.co.jp', $result[0]['to_address_enc']);
        $this->assertSame(config('restaurant.mail.from'), $result[0]['from_address_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertTrue(strpos($result[0]['message_enc'], '「テスト店舗」のご予約が確定しました。') !== false);
        $this->assertTrue(strpos($result[0]['message_enc'], '2099年10月10日 (土) 9:00') !== false);             // 来店日時
        $this->assertTrue(strpos($result[0]['message_enc'], '2名') !== false);                                 // 人数
        $this->assertTrue(strpos($result[0]['message_enc'], '合計(税込み）￥2,000') !== false);                  // お支払い金額
        $this->assertTrue(strpos($result[0]['message_enc'], '≫≫≫事前決済済みです≪≪≪') !== false);            // 事前決済である
        $this->assertTrue(strpos($result[0]['message_enc'], 'テストメニュー') !== false);                        // プラン
        $this->assertTrue(strpos($result[0]['message_enc'], 'なし') !== false);                                // 追加オプション
        $this->assertTrue(strpos($result[0]['message_enc'], '卵アレルギーです。') !== false);                    // ご要望
        $this->assertTrue(strpos($result[0]['message_enc'], 'テスト店舗') !== false);                           // 店舗名
        $this->assertTrue(strpos($result[0]['message_enc'], '〒123-4567') !== false);                          // 店舗電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], '東京都渋谷区テスト住所1-2-3') !== false);            // 店舗住所
        $this->assertTrue(strpos($result[0]['message_enc'], '06-1111-2222') !== false);                       // 店舗電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'RS' . $reservation->id) !== false);              // 予約番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'グルメ 太郎') !== false);                          // お客様名
        $this->assertTrue(strpos($result[0]['message_enc'], '090-1111-2222') !== false);                      // お客様電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'gourmet-test@adventure-inc.co.jp') !== false);   // お客様メールアドレス
    }

    public function testCompleteReservationForClient()
    {
        $fromAddress = config('restaurant.mail.devClientFrom');
        if (\App::environment('production')) {
            $fromAddress = config('restaurant.mail.prdClientFrom');
        }

        // 注文料金が1円以上
        {
            list($reservation, $cmThApplication) = $this->_createData();

            // 送信内容をMailDBQueueに書き込む（店舗用）
            $restaurantMail = new RestaurantMail($reservation->id);
            $restaurantMail->completeReservationForClient('gourmet-test111@adventure-inc.co.jp');

            // 送信内容が登録されていることを確認
            $result = MailDBQueue::where('cm_application_id', $cmThApplication->cm_application_id)->get();
            $this->assertCount(1, $result);
            $this->assertSame('【スカイチケットグルメ】プランのご予約いただきました！（事前決済）', $result[0]['subject']);
            $this->assertSame('gourmet-test111@adventure-inc.co.jp', $result[0]['to_address_enc']);
            $this->assertSame($fromAddress, $result[0]['from_address_enc']);
            $this->assertNotEmpty($result[0]['message_enc']);
            $this->assertNotEmpty($result[0]['message_enc']);
            $this->assertTrue(strpos($result[0]['message_enc'], 'お客様から「テスト店舗」様へ、ご予約が入りました。') !== false);
            $this->assertTrue(strpos($result[0]['message_enc'], '2099年10月10日 (土) 9:00') !== false);                     // 来店日時
            $this->assertTrue(strpos($result[0]['message_enc'], '2名') !== false);                                         // 人数
            $this->assertTrue(strpos($result[0]['message_enc'], '合計(税込み）￥2,000') !== false);                          // お支払い金額
            $this->assertTrue(strpos($result[0]['message_enc'], '≫≫≫事前決済済みです≪≪≪') !== false);                    // 事前決済である
            $this->assertTrue(strpos($result[0]['message_enc'], 'テストメニュー') !== false);                               // プラン
            $this->assertTrue(strpos($result[0]['message_enc'], 'なし') !== false);                                       // 追加オプション
            $this->assertTrue(strpos($result[0]['message_enc'], '卵アレルギーです。') !== false);                           // ご要望
            $this->assertTrue(strpos($result[0]['message_enc'], 'RS' . $reservation->id) !== false);                     // 予約番号
            $this->assertTrue(strpos($result[0]['message_enc'], 'グルメ 太郎') !== false);                                 // お客様名
            $this->assertTrue(strpos($result[0]['message_enc'], '090-1111-2222') !== false);                             // お客様電話番号
            $this->assertTrue(strpos($result[0]['message_enc'], 'gourmet-test@adventure-inc.co.jp') !== false);          // お客様メールアドレス
        }

        // 注文料金が0円（席のみ予約）
        {
            // 注文料金を0円に変更
            list($reservation2, $cmThApplication2) = $this->_createData(0);

            // 送信内容をMailDBQueueに書き込む（店舗用）
            $restaurantMail = new RestaurantMail($reservation2->id);
            $restaurantMail->completeReservationForClient('gourmet-test111@adventure-inc.co.jp');

            // 送信内容が登録されていることを確認
            $result = MailDBQueue::where('cm_application_id', $cmThApplication2->cm_application_id)->get();
            $this->assertCount(1, $result);
            $this->assertSame('【スカイチケットグルメ】プランのご予約いただきました！', $result[0]['subject']);
            $this->assertSame('gourmet-test111@adventure-inc.co.jp', $result[0]['to_address_enc']);
            $this->assertSame($fromAddress, $result[0]['from_address_enc']);
            $this->assertNotEmpty($result[0]['message_enc']);
            $this->assertNotEmpty($result[0]['message_enc']);
            $this->assertTrue(strpos($result[0]['message_enc'], 'お客様から「テスト店舗」様へ、ご予約が入りました。') !== false);
            $this->assertTrue(strpos($result[0]['message_enc'], '2099年10月10日 (土) 9:00') !== false);                     // 来店日時
            $this->assertTrue(strpos($result[0]['message_enc'], '2名') !== false);                                         // 人数
            $this->assertTrue(strpos($result[0]['message_enc'], '合計(税込み）￥0') !== false);                              // お支払い金額
            $this->assertFalse(strpos($result[0]['message_enc'], '≫≫≫事前決済済みです≪≪≪') !== false);                   // 事前決済ではない
            $this->assertTrue(strpos($result[0]['message_enc'], 'テストメニュー') !== false);                               // プラン
            $this->assertTrue(strpos($result[0]['message_enc'], 'なし') !== false);                                       // 追加オプション
            $this->assertTrue(strpos($result[0]['message_enc'], '卵アレルギーです。') !== false);                           // ご要望
            $this->assertTrue(strpos($result[0]['message_enc'], 'RS' . $reservation2->id) !== false);                    // 予約番号
            $this->assertTrue(strpos($result[0]['message_enc'], 'グルメ 太郎') !== false);                                 // お客様名
            $this->assertTrue(strpos($result[0]['message_enc'], '090-1111-2222') !== false);                             // お客様電話番号
            $this->assertTrue(strpos($result[0]['message_enc'], 'gourmet-test@adventure-inc.co.jp') !== false);          // お客様メールアドレス
        }
    }

    public function testUserChangeReservationForClient()
    {
        $fromAddress = config('restaurant.mail.devClientFrom');
        if (\App::environment('production')) {
            $fromAddress = config('restaurant.mail.prdClientFrom');
        }

        list($reservation, $cmThApplication) = $this->_createData();

        $newReservation = Reservation::find($reservation->id);
        $newReservation->pick_up_datetime = '2099-10-11 12:00:00';
        $newReservation->save();

        // 送信内容をMailDBQueueに書き込む（店舗用）
        $restaurantMail = new RestaurantMail($newReservation->id);
        $restaurantMail->userChangeReservationForClient($reservation, 'gourmet-teststore@adventure-inc.co.jp');

        // 送信内容が登録されていることを確認
        $result = MailDBQueue::where('cm_application_id', $cmThApplication->cm_application_id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('【スカイチケットグルメ】予約内容が変更されました。', $result[0]['subject']);
        $this->assertSame('gourmet-teststore@adventure-inc.co.jp', $result[0]['to_address_enc']);
        $this->assertSame($fromAddress, $result[0]['from_address_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertTrue(strpos($result[0]['message_enc'], 'お客様により下記予約内容が変更されました。') !== false);
        $this->assertTrue(strpos($result[0]['message_enc'], '2099年10月10日 (土) 9:00　⇒　2099年10月11日 (日) 12:00') !== false);    // 時間変更内容が含まれていること
        $this->assertTrue(strpos($result[0]['message_enc'], '2名') !== false);                                                   // 人数
        $this->assertTrue(strpos($result[0]['message_enc'], '合計(税込み）￥2,000') !== false);                                    // お支払い金額
        $this->assertTrue(strpos($result[0]['message_enc'], 'テストメニュー') !== false);                                          // プラン
        $this->assertTrue(strpos($result[0]['message_enc'], 'なし') !== false);                                                  // 追加オプション
        $this->assertTrue(strpos($result[0]['message_enc'], '卵アレルギーです。') !== false);                                      // ご要望
        $this->assertTrue(strpos($result[0]['message_enc'], 'RS' . $reservation->id) !== false);                                // 予約番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'グルメ 太郎') !== false);                                            // お客様名
        $this->assertTrue(strpos($result[0]['message_enc'], '090-1111-2222') !== false);                                        // お客様電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'gourmet-test@adventure-inc.co.jp') !== false);                     // お客様メールアドレス
    }

    public function testUserChangeReservationForUser()
    {
        // 注文料金が1円以上&金額が変更前と異なる
        {
            list($reservation, $cmThApplication) = $this->_createData();

            $newReservation = Reservation::find($reservation->id);
            $newReservation->pick_up_datetime = '2099-10-11 12:00:00';
            $newReservation->total = 1500;
            $newReservation->save();

            // 送信内容をMailDBQueueに書き込む（お客様用）
            $restaurantMail = new RestaurantMail($newReservation->id);
            $restaurantMail->userChangeReservationForUser($reservation);

            // 送信内容が登録されていることを確認
            $result = MailDBQueue::where('cm_application_id', $cmThApplication->cm_application_id)->get();
            $this->assertCount(1, $result);
            $this->assertSame('【スカイチケットグルメ】お支払いが完了しました。', $result[0]['subject']);
            $this->assertSame('gourmet-test@adventure-inc.co.jp', $result[0]['to_address_enc']);
            $this->assertSame(config('restaurant.mail.from'), $result[0]['from_address_enc']);
            $this->assertNotEmpty($result[0]['message_enc']);
            $this->assertNotEmpty($result[0]['message_enc']);
            $this->assertTrue(strpos($result[0]['message_enc'], 'ご予約変更のお支払いが完了いたしましたので、下記内容でご予約承りました。') !== false);
            $this->assertTrue(strpos($result[0]['message_enc'], '2099年10月10日 (土) 9:00　⇒　2099年10月11日 (日) 12:00') !== false);             // 時間変更内容が含まれていること
            $this->assertTrue(strpos($result[0]['message_enc'], '2名') !== false);                                                             // 人数
            $this->assertTrue(strpos($result[0]['message_enc'], '合計(税込み）￥2,000　⇒　￥1,500') !== false);                                   // お支払い金額
            // $this->assertTrue(strpos($result[0]['message_enc'], '≫≫≫事前決済済みです≪≪≪') !== false);            // 事前決済である
            $this->assertTrue(strpos($result[0]['message_enc'], 'テストメニュー') !== false);                                                   // プラン
            $this->assertTrue(strpos($result[0]['message_enc'], 'なし') !== false);                                                           // 追加オプション
            $this->assertTrue(strpos($result[0]['message_enc'], '卵アレルギーです。') !== false);                                               // ご要望
            $this->assertTrue(strpos($result[0]['message_enc'], 'テスト店舗') !== false);                                                      // 店舗名
            $this->assertTrue(strpos($result[0]['message_enc'], '〒123-4567') !== false);                                                    // 店舗電話番号
            $this->assertTrue(strpos($result[0]['message_enc'], '東京都渋谷区テスト住所1-2-3') !== false);                                      // 店舗住所
            $this->assertTrue(strpos($result[0]['message_enc'], '06-1111-2222') !== false);                                                 // 店舗電話番号
            $this->assertTrue(strpos($result[0]['message_enc'], 'RS' . $reservation->id) !== false);                                        // 予約番号
            $this->assertTrue(strpos($result[0]['message_enc'], 'グルメ 太郎') !== false);                                                    // お客様名
            $this->assertTrue(strpos($result[0]['message_enc'], '090-1111-2222') !== false);                                                // お客様電話番号
            $this->assertTrue(strpos($result[0]['message_enc'], 'gourmet-test@adventure-inc.co.jp') !== false);                             // お客様メールアドレス
        }

        // 注文料金が1円以上&金額が変更前と同じ
        {
            list($reservation, $cmThApplication) = $this->_createData();

            $newReservation = Reservation::find($reservation->id);
            $newReservation->pick_up_datetime = '2099-10-11 12:00:00';
            $newReservation->save();

            // 送信内容をMailDBQueueに書き込む（お客様用）
            $restaurantMail = new RestaurantMail($newReservation->id);
            $restaurantMail->userChangeReservationForUser($reservation);

            // 送信内容が登録されていることを確認
            $result = MailDBQueue::where('cm_application_id', $cmThApplication->cm_application_id)->get();
            $this->assertCount(1, $result);
            $this->assertSame('【スカイチケットグルメ】ご予約内容の変更を承りました。', $result[0]['subject']);
            $this->assertSame('gourmet-test@adventure-inc.co.jp', $result[0]['to_address_enc']);
            $this->assertSame(config('restaurant.mail.from'), $result[0]['from_address_enc']);
            $this->assertNotEmpty($result[0]['message_enc']);
            $this->assertTrue(strpos($result[0]['message_enc'], 'ご予約の変更を承りましたので下記予約内容をご確認下さい。') !== false);
            $this->assertTrue(strpos($result[0]['message_enc'], '2099年10月10日 (土) 9:00　⇒　2099年10月11日 (日) 12:00') !== false);    // 時間変更内容が含まれていること
            $this->assertTrue(strpos($result[0]['message_enc'], '2名') !== false);                                                    // 人数
            $this->assertTrue(strpos($result[0]['message_enc'], '合計(税込み）￥2,000') !== false);                                    // お支払い金額
            $this->assertTrue(strpos($result[0]['message_enc'], 'テストメニュー') !== false);                                          // プラン
            $this->assertTrue(strpos($result[0]['message_enc'], 'なし') !== false);                                                  // 追加オプション
            $this->assertTrue(strpos($result[0]['message_enc'], '卵アレルギーです。') !== false);                                      // ご要望
            $this->assertTrue(strpos($result[0]['message_enc'], 'テスト店舗') !== false);                                             // 店舗名
            $this->assertTrue(strpos($result[0]['message_enc'], '〒123-4567') !== false);                                           // 店舗電話番号
            $this->assertTrue(strpos($result[0]['message_enc'], '東京都渋谷区テスト住所1-2-3') !== false);                             // 店舗住所
            $this->assertTrue(strpos($result[0]['message_enc'], '06-1111-2222') !== false);                                        // 店舗電話番号
            $this->assertTrue(strpos($result[0]['message_enc'], 'RS' . $reservation->id) !== false);                               // 予約番号
            $this->assertTrue(strpos($result[0]['message_enc'], 'グルメ 太郎') !== false);                                           // お客様名
            $this->assertTrue(strpos($result[0]['message_enc'], '090-1111-2222') !== false);                                       // お客様電話番号
            $this->assertTrue(strpos($result[0]['message_enc'], 'gourmet-test@adventure-inc.co.jp') !== false);                    // お客様メールアドレス
        }

        // 注文料金が0円（席のみ予約）
        {
            list($reservation, $cmThApplication) = $this->_createData(0);

            $newReservation = Reservation::find($reservation->id);
            $newReservation->pick_up_datetime = '2099-10-11 12:00:00';
            $newReservation->save();

            // 送信内容をMailDBQueueに書き込む（お客様用）
            $restaurantMail = new RestaurantMail($newReservation->id);
            $restaurantMail->userChangeReservationForUser($reservation);

            // 送信内容が登録されていることを確認
            $result = MailDBQueue::where('cm_application_id', $cmThApplication->cm_application_id)->get();
            $this->assertCount(1, $result);
            $this->assertSame('【スカイチケットグルメ】ご予約内容の変更を承りました。', $result[0]['subject']);
            $this->assertSame('gourmet-test@adventure-inc.co.jp', $result[0]['to_address_enc']);
            $this->assertSame(config('restaurant.mail.from'), $result[0]['from_address_enc']);
            $this->assertNotEmpty($result[0]['message_enc']);
            $this->assertTrue(strpos($result[0]['message_enc'], 'ご予約の変更を承りましたので下記予約内容をご確認下さい。') !== false);
            $this->assertTrue(strpos($result[0]['message_enc'], '2099年10月10日 (土) 9:00　⇒　2099年10月11日 (日) 12:00') !== false);    // 時間変更内容が含まれていること
            $this->assertTrue(strpos($result[0]['message_enc'], '2名') !== false);                                                    // 人数
            $this->assertTrue(strpos($result[0]['message_enc'], '合計(税込み）￥0') !== false);                                         // お支払い金額
            $this->assertTrue(strpos($result[0]['message_enc'], 'テストメニュー') !== false);                                          // プラン
            $this->assertTrue(strpos($result[0]['message_enc'], 'なし') !== false);                                                  // 追加オプション
            $this->assertTrue(strpos($result[0]['message_enc'], '卵アレルギーです。') !== false);                                      // ご要望
            $this->assertTrue(strpos($result[0]['message_enc'], 'テスト店舗') !== false);                                             // 店舗名
            $this->assertTrue(strpos($result[0]['message_enc'], '〒123-4567') !== false);                                           // 店舗電話番号
            $this->assertTrue(strpos($result[0]['message_enc'], '東京都渋谷区テスト住所1-2-3') !== false);                             // 店舗住所
            $this->assertTrue(strpos($result[0]['message_enc'], '06-1111-2222') !== false);                                        // 店舗電話番号
            $this->assertTrue(strpos($result[0]['message_enc'], 'RS' . $reservation->id) !== false);                               // 予約番号
            $this->assertTrue(strpos($result[0]['message_enc'], 'グルメ 太郎') !== false);                                           // お客様名
            $this->assertTrue(strpos($result[0]['message_enc'], '090-1111-2222') !== false);                                       // お客様電話番号
            $this->assertTrue(strpos($result[0]['message_enc'], 'gourmet-test@adventure-inc.co.jp') !== false);                    // お客様メールアドレス
        }
    }

    public function testAdminChangeReservationForUser()
    {
        list($reservation, $cmThApplication) = $this->_createData();
        $this->_addTmpAdminChangeReservation($reservation->id);

        // 送信内容をMailDBQueueに書き込む（お客様用）
        $restaurantMail = new RestaurantMail($reservation->id);
        $restaurantMail->adminChangeReservationForUser($reservation);

        // 送信内容が登録されていることを確認
        $result = MailDBQueue::where('cm_application_id', $cmThApplication->cm_application_id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('【スカイチケットグルメ】ご注文変更のお知らせです。', $result[0]['subject']);
        $this->assertSame('gourmet-test@adventure-inc.co.jp', $result[0]['to_address_enc']);
        $this->assertSame(config('restaurant.mail.from'), $result[0]['from_address_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertTrue(strpos($result[0]['message_enc'], 'ご予約された「テスト店舗」から、予約内容変更の連絡がありました。') !== false);
        $this->assertTrue(strpos($result[0]['message_enc'], '2099年10月10日 (土) 9:00　⇒　2099年10月13日 (火) 17:00') !== false);    // 時間変更内容が含まれていること
        $this->assertTrue(strpos($result[0]['message_enc'], '2名　⇒　3名') !== false);                                             // 人数変更内容が含まれていること
        $this->assertTrue(strpos($result[0]['message_enc'], '合計(税込み）￥2,000　⇒　￥3,000') !== false);                          // 料金変更内容が含まれていること
        $this->assertTrue(strpos($result[0]['message_enc'], 'テストメニュー') !== false);                                          // プラン
        $this->assertTrue(strpos($result[0]['message_enc'], 'なし') !== false);                                                  // 追加オプション
        $this->assertTrue(strpos($result[0]['message_enc'], '卵アレルギーです。') !== false);                                      // ご要望
        $this->assertTrue(strpos($result[0]['message_enc'], 'テスト店舗') !== false);                                            // 店舗名
        $this->assertTrue(strpos($result[0]['message_enc'], '〒123-4567') !== false);                                           // 店舗電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], '東京都渋谷区テスト住所1-2-3') !== false);                              // 店舗住所
        $this->assertTrue(strpos($result[0]['message_enc'], '06-1111-2222') !== false);                                         // 店舗電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'RS' . $reservation->id) !== false);                                // 予約番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'グルメ 太郎') !== false);                                            // お客様名
        $this->assertTrue(strpos($result[0]['message_enc'], '090-1111-2222') !== false);                                        // お客様電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'gourmet-test@adventure-inc.co.jp') !== false);                     // お客様メールアドレス
    }

    public function testUserCancelReservationForClient()
    {
        $fromAddress = config('restaurant.mail.devClientFrom');
        if (\App::environment('production')) {
            $fromAddress = config('restaurant.mail.prdClientFrom');
        }

        list($reservation, $cmThApplication) = $this->_createData();

        // 送信内容をMailDBQueueに書き込む（店舗用）
        $restaurantMail = new RestaurantMail($reservation->id);
        $restaurantMail->userCancelReservationForClient('gourmet-test111@adventure-inc.co.jp');

        // 送信内容が登録されていることを確認
        $result = MailDBQueue::where('cm_application_id', $cmThApplication->cm_application_id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('【スカイチケットグルメ】予約がキャンセルされました。', $result[0]['subject']);
        $this->assertSame('gourmet-test111@adventure-inc.co.jp', $result[0]['to_address_enc']);
        $this->assertSame($fromAddress, $result[0]['from_address_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertTrue(strpos($result[0]['message_enc'], 'お客様により下記予約がキャンセルされました。') !== false);
        $this->assertTrue(strpos($result[0]['message_enc'], '2099年10月10日 (土) 9:00') !== false);             // 来店日時
        $this->assertTrue(strpos($result[0]['message_enc'], '2名') !== false);                                 // 人数
        $this->assertTrue(strpos($result[0]['message_enc'], '合計(税込み）￥2,000') !== false);                  // お支払い金額
        $this->assertTrue(strpos($result[0]['message_enc'], '≫≫≫事前決済済みです≪≪≪') !== false);            // 事前決済である
        $this->assertTrue(strpos($result[0]['message_enc'], 'テストメニュー') !== false);                        // プラン
        $this->assertTrue(strpos($result[0]['message_enc'], 'なし') !== false);                                // 追加オプション
        $this->assertTrue(strpos($result[0]['message_enc'], '卵アレルギーです。') !== false);                    // ご要望
        $this->assertTrue(strpos($result[0]['message_enc'], 'RS' . $reservation->id) !== false);              // 予約番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'グルメ 太郎') !== false);                          // お客様名
        $this->assertTrue(strpos($result[0]['message_enc'], '090-1111-2222') !== false);                      // お客様電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'gourmet-test@adventure-inc.co.jp') !== false);   // お客様メールアドレス
    }

    public function testUserCancelReservationForUser()
    {
        list($reservation, $cmThApplication) = $this->_createData();

        // 送信内容をMailDBQueueに書き込む（お客様用）
        $restaurantMail = new RestaurantMail($reservation->id);
        $restaurantMail->userCancelReservationForUser();

        // 送信内容が登録されていることを確認
        $result = MailDBQueue::where('cm_application_id', $cmThApplication->cm_application_id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('【スカイチケットグルメ】ご予約のキャンセルを承りました。', $result[0]['subject']);
        $this->assertSame('gourmet-test@adventure-inc.co.jp', $result[0]['to_address_enc']);
        $this->assertSame(config('restaurant.mail.from'), $result[0]['from_address_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertTrue(strpos($result[0]['message_enc'], 'ご予約のキャンセルを承りましたので下記予約内容をご確認下さい。') !== false);
        $this->assertTrue(strpos($result[0]['message_enc'], '2099年10月10日 (土) 9:00') !== false);             // 来店日時
        $this->assertTrue(strpos($result[0]['message_enc'], '2名') !== false);                                 // 人数
        $this->assertTrue(strpos($result[0]['message_enc'], '合計(税込み）￥2,000') !== false);                  // お支払い金額
        $this->assertTrue(strpos($result[0]['message_enc'], '≫≫≫事前決済済みです≪≪≪') !== false);            // 事前決済である
        $this->assertTrue(strpos($result[0]['message_enc'], 'テストメニュー') !== false);                        // プラン
        $this->assertTrue(strpos($result[0]['message_enc'], 'なし') !== false);                                // 追加オプション
        $this->assertTrue(strpos($result[0]['message_enc'], '卵アレルギーです。') !== false);                    // ご要望
        $this->assertTrue(strpos($result[0]['message_enc'], 'テスト店舗') !== false);                           // 店舗名
        $this->assertTrue(strpos($result[0]['message_enc'], '〒123-4567') !== false);                          // 店舗電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], '東京都渋谷区テスト住所1-2-3') !== false);            // 店舗住所
        $this->assertTrue(strpos($result[0]['message_enc'], '06-1111-2222') !== false);                       // 店舗電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'RS' . $reservation->id) !== false);              // 予約番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'グルメ 太郎') !== false);                          // お客様名
        $this->assertTrue(strpos($result[0]['message_enc'], '090-1111-2222') !== false);                      // お客様電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'gourmet-test@adventure-inc.co.jp') !== false);   // お客様メールアドレス
    }

    public function testAdminCancelReservationForUser()
    {
        list($reservation, $cmThApplication) = $this->_createData();

        // 送信内容をMailDBQueueに書き込む（お客様用）
        $restaurantMail = new RestaurantMail($reservation->id);
        $restaurantMail->adminCancelReservationForUser();

        // 送信内容が登録されていることを確認
        $result = MailDBQueue::where('cm_application_id', $cmThApplication->cm_application_id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('【スカイチケットグルメ】ご予約キャンセルのお知らせです。', $result[0]['subject']);
        $this->assertSame('gourmet-test@adventure-inc.co.jp', $result[0]['to_address_enc']);
        $this->assertSame(config('restaurant.mail.from'), $result[0]['from_address_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertTrue(strpos($result[0]['message_enc'], 'ご予約いただいた店舗より、本日、予約キャンセルの処理がされました。') !== false);
        $this->assertTrue(strpos($result[0]['message_enc'], '2099年10月10日 (土) 9:00') !== false);             // 来店日時
        $this->assertTrue(strpos($result[0]['message_enc'], '2名') !== false);                                 // 人数
        $this->assertTrue(strpos($result[0]['message_enc'], '合計(税込み）￥2,000') !== false);                  // お支払い金額
        $this->assertTrue(strpos($result[0]['message_enc'], '≫≫≫事前決済済みです≪≪≪') !== false);            // 事前決済である
        $this->assertTrue(strpos($result[0]['message_enc'], 'テストメニュー') !== false);                        // プラン
        $this->assertTrue(strpos($result[0]['message_enc'], 'なし') !== false);                                // 追加オプション
        $this->assertTrue(strpos($result[0]['message_enc'], '卵アレルギーです。') !== false);                    // ご要望
        $this->assertTrue(strpos($result[0]['message_enc'], 'テスト店舗') !== false);                           // 店舗名
        $this->assertTrue(strpos($result[0]['message_enc'], '〒123-4567') !== false);                          // 店舗電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], '東京都渋谷区テスト住所1-2-3') !== false);            // 店舗住所
        $this->assertTrue(strpos($result[0]['message_enc'], '06-1111-2222') !== false);                       // 店舗電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'RS' . $reservation->id) !== false);              // 予約番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'グルメ 太郎') !== false);                          // お客様名
        $this->assertTrue(strpos($result[0]['message_enc'], '090-1111-2222') !== false);                      // お客様電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], 'gourmet-test@adventure-inc.co.jp') !== false);   // お客様メールアドレス
    }

    public function testQuestionnaireForUser()
    {
        list($reservation, $cmThApplication) = $this->_createData();

        // 送信内容をMailDBQueueに書き込む（お客様用）
        $restaurantMail = new RestaurantMail($reservation->id);
        $restaurantMail->questionnaireForUser();

        // 送信内容が登録されていることを確認
        $result = MailDBQueue::where('cm_application_id', $cmThApplication->cm_application_id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('【スカイチケットグルメ】ご予約の御礼', $result[0]['subject']);
        $this->assertSame('gourmet-test@adventure-inc.co.jp', $result[0]['to_address_enc']);
        $this->assertSame(config('restaurant.mail.from'), $result[0]['from_address_enc']);
        $this->assertNotEmpty($result[0]['message_enc']);
        $this->assertTrue(strpos($result[0]['message_enc'], '簡単なアンケート入力をお願いしております。') !== false);
        $this->assertTrue(strpos($result[0]['message_enc'], '2099年10月10日 (土) 9:00') !== false);             // 来店日時
        $this->assertTrue(strpos($result[0]['message_enc'], '2名') !== false);                                 // 人数
        $this->assertTrue(strpos($result[0]['message_enc'], '合計(税込み）￥2,000') !== false);                  // お支払い金額
        $this->assertTrue(strpos($result[0]['message_enc'], '≫≫≫事前決済済みです≪≪≪') !== false);            // 事前決済である
        $this->assertTrue(strpos($result[0]['message_enc'], 'テストメニュー') !== false);                        // プラン
        $this->assertTrue(strpos($result[0]['message_enc'], 'なし') !== false);                                // 追加オプション
        $this->assertTrue(strpos($result[0]['message_enc'], '卵アレルギーです。') !== false);                    // ご要望
        $this->assertTrue(strpos($result[0]['message_enc'], 'テスト店舗') !== false);                           // 店舗名
        $this->assertTrue(strpos($result[0]['message_enc'], '〒123-4567') !== false);                          // 店舗電話番号
        $this->assertTrue(strpos($result[0]['message_enc'], '東京都渋谷区テスト住所1-2-3') !== false);            // 店舗住所
        $this->assertTrue(strpos($result[0]['message_enc'], '06-1111-2222') !== false);                       // 店舗電話番号
    }

    public function testGetCancelPolicy()
    {
        list($reservation, $cmThApplication) = $this->_createData();
        $restaurantMail = new RestaurantMail($reservation->id);

        $cancelFee = $this->_addCanelFee($reservation->reservationStore->store->id);

        // 来店日1日前まで予約料金の定率
        {
            $result = $restaurantMail->getCancelPolicy();
            $this->assertCount(1, $result);
            $this->assertSame('来店日の1日前まで・・・・・予約料金の50％', $result[0]);
        }

        // 来店日の当日、予約料金の定率
        {
            CancelFee::find($cancelFee->id)->update(['cancel_limit' => 0, 'cancel_fee' => 100]);
            $result = $restaurantMail->getCancelPolicy();
            $this->assertCount(1, $result);
            $this->assertSame('来店日当日・・・・・予約料金の100％', $result[0]);
        }

        // 来店日の当日12時間前まで、予約料金の定率
        {
            CancelFee::find($cancelFee->id)->update(['cancel_limit_unit' => 'TIME', 'cancel_limit' => 12, 'cancel_fee' => 70]);
            $result = $restaurantMail->getCancelPolicy();
            $this->assertCount(1, $result);
            $this->assertSame('来店日の12時間まで・・・・・予約料金の70％', $result[0]);
        }

        // 来店日の当日12時間前まで、定額
        {
            CancelFee::find($cancelFee->id)->update(['cancel_limit_unit' => 'TIME', 'cancel_limit' => 12, 'cancel_fee_unit' => 'FLAT_RATE', 'cancel_fee' => 1500]);
            $result = $restaurantMail->getCancelPolicy();
            $this->assertCount(1, $result);
            $this->assertSame('来店日の12時間まで・・・・・1500円', $result[0]);
        }

        // 来店後
        {
            $cancelFee2 = $this->_addCanelFee($reservation->reservationStore->store->id, 'AFTER');
            $result = $restaurantMail->getCancelPolicy();
            $this->assertCount(2, $result);
            $this->assertSame('来店日の12時間まで・・・・・1500円', $result[0]);
            $this->assertSame('来店後　　 ・・・・・予約料金の100％', $result[1]);
        }

        // キャンセルポリシーのvisitが同じ＆cancel_limit_unitが同じレコードが２つ以上ある場合、期限の値が多い方が先に来ること
        {
            $cancelFee2 = $this->_addCanelFee($reservation->reservationStore->store->id);
            $cancelFee3 = $this->_addCanelFee($reservation->reservationStore->store->id);
            CancelFee::find($cancelFee3->id)->update(['cancel_limit' => 2]);
            $result = $restaurantMail->getCancelPolicy();
            $this->assertCount(4, $result);
            $this->assertSame('来店日の2日前まで・・・・・予約料金の50％', $result[0]);
            $this->assertSame('来店日の1日前まで・・・・・予約料金の50％', $result[1]);
            $this->assertSame('来店日の12時間まで・・・・・1500円', $result[2]);
            $this->assertSame('来店後　　 ・・・・・予約料金の100％', $result[3]);
        }
    }

    private function _createData($total = 2000)
    {
        $store = new Store();
        $store->name = 'テスト店舗';
        $store->postal_code = '123-4567';
        $store->address_1 = '東京都';
        $store->address_2 = '渋谷区';
        $store->address_3 = 'テスト住所1-2-3';
        $store->tel = '06-1111-2222';
        $store->save();

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->name = 'テストメニュー';
        $menu->save();

        $reservation = new Reservation();
        $reservation->pick_up_datetime = '2099-10-10 09:00:00';
        $reservation->last_name = 'グルメ';
        $reservation->first_name = '太郎';
        $reservation->tel = '090-1111-2222';
        $reservation->email = 'gourmet-test@adventure-inc.co.jp';
        $reservation->total = $total;
        $reservation->persons = 2;
        $reservation->request = '卵アレルギーです。';
        $reservation->save();

        $reservationStore = new ReservationStore();
        $reservationStore->reservation_id = $reservation->id;
        $reservationStore->store_id = $store->id;
        $reservationStore->save();

        $reservationMenu = new ReservationMenu();
        $reservationMenu->reservation_id = $reservation->id;
        $reservationMenu->menu_id = $menu->id;
        $reservationMenu->name = 'テストメニュー';
        $reservationMenu->count = 2;
        $reservationMenu->unit_price = 1000;
        $reservationMenu->price = 2000;
        $reservationMenu->save();

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

    private function _addTmpAdminChangeReservation($reservationId)
    {
        $tmpAdminChangeReservation = new TmpAdminChangeReservation();
        $tmpAdminChangeReservation->reservation_id = $reservationId;
        $tmpAdminChangeReservation->info = json_encode(['persons' => 3, 'pick_up_datetime' => '2099-10-13 17:00:00', 'total' => 3000]);
        $tmpAdminChangeReservation->save();
    }

    private function _addCanelFee($storeId, $visit = 'BEFORE')
    {
        $cancelFee = new CancelFee();
        $cancelFee->store_id = $storeId;
        $cancelFee->app_cd = 'RS';
        $cancelFee->apply_term_from = '2022-01-01';
        $cancelFee->apply_term_to = '2099-12-31';
        $cancelFee->visit = $visit;
        if ($visit == 'BEFORE') {
            $cancelFee->cancel_limit = 1;
            $cancelFee->cancel_limit_unit = 'DAY';
            $cancelFee->cancel_fee_unit = 'FIXED_RATE';
            $cancelFee->cancel_fee = 50;
        }
        $cancelFee->published = 1;
        $cancelFee->save();
        return $cancelFee;
    }
}
