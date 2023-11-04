<?php

namespace App\Services;

use App\Http\Requests\Api\v1\AuthReviewRequest;
use App\Libs\Cipher;
use App\Models\CancelFee;
use App\Models\CmThApplicationDetail;
use App\Models\GenreGroup;
use App\Models\Holiday;
use App\Models\Menu;
use App\Models\OpeningHour;
use App\Models\Reservation;
use App\Models\ReservationStore;
use App\Models\Review;
use App\Models\TmpAdminChangeReservation;
use App\Modules\UserLogin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redis;

class AuthService
{
    public function __construct(
        UserLogin $userLogin,
        Reservation $reservation,
        CancelFee $cancelFee,
        GenreGroup $genreGroup,
        Menu $menu,
        OpeningHour $openingHour
    ) {
        $this->userLogin = $userLogin;
        $this->reservation = $reservation;
        $this->cancelFee = $cancelFee;
        $this->genreGroup = $genreGroup;
        $this->menu = $menu;
        $this->openingHour = $openingHour;
    }

    /**
     * マイページ(予約確認)情報取得.
     *
     * @param string reservationNo 予約番号
     * @param string tel 電話番号
     * @param array resValues
     *
     * @return bool true:成功 false:失敗
     */
    public function getMypage(string $reservationNo, string $tel, array &$resValues = []): bool
    {
        try {
            $reservation = $this->reservation->getMypage($reservationNo, $tel);
            if (is_null($reservation)){
                $resValues['status'] = false;
                $resValues['message'] = Lang::get('message.reservationNotFound');

                return false;
            }
            $reservationId = substr($reservationNo, 2);
            $tmpAdminChangeReservation = $reservation->payment_status == config('code.paymentStatus.wait_payment.key') ?
            TmpAdminChangeReservation::where('reservation_id', $reservationId)->where('is_invalid', 0)->whereNull('status')->first() : null;
            $changeInfo = is_null($tmpAdminChangeReservation) ? null : json_decode($tmpAdminChangeReservation->info, true);

            $resValues['status'] = true;
            $resValues['message'] = $reservation->app_cd === 'RS' ? Lang::get('message.restaurantCompleteSuccess1') : Lang::get('message.takeoutCompleteSuccess');
            $resValues['reservation']['id'] = $reservation->id;
            $resValues['reservation']['appCd'] = $reservation->app_cd;
            $resValues['reservation']['firstName'] = $reservation->first_name;
            $resValues['reservation']['lastName'] = $reservation->last_name;
            $resValues['reservation']['email'] = $reservation->email;
            $resValues['reservation']['persons'] = is_null($changeInfo) ? $reservation->persons : $changeInfo['persons'];
            $resValues['reservation']['tel'] = $reservation->tel;
            $resValues['reservation']['total'] = $reservation->total;
            $resValues['reservation']['afterTotal'] = is_null($changeInfo) ? null : $changeInfo['total'] ;
            $resValues['reservation']['reservationStatus'] = $reservation->reservation_status;
            $resValues['reservation']['paymentStatus'] = $reservation->payment_status;
            $resValues['reservation']['paymentLimit'] = $reservation->payment_limit;
            $resValues['reservation']['cancelDatetime'] = $reservation->cancel_datetime;
            $resValues['reservation']['pickUpDateTime'] = is_null($changeInfo) ? $reservation->pick_up_datetime : $changeInfo['pick_up_datetime'];
            $resValues['reservation']['pick_up_receive_datetime'] = $reservation->pick_up_receive_datetime;
            $resValues['reservation']['hasReview'] = $reservation->hasReview;

            $resValues['reservation']['request'] = $reservation->request;

            foreach ($reservation->reservationMenus as $key => $menu) {
                $tmpMenu = [];
                $tmpMenu['id'] = $menu->id;
                $tmpMenu['reservationId'] = $menu->reservation_id;
                $tmpMenu['name'] = $menu->name;
                $tmpMenu['count'] = $menu->count;
                $tmpMenu['unitPrice'] = $menu->unit_price;
                $tmpMenu['price'] = $menu->price;
                $tmpMenu['onlySeat'] = ($menu->price > 0) ? false : true;
                $tmpMenu['menuId'] = $menu->menu_id;
                foreach ($menu->reservationOptions as $key => $option) {
                    $tmpOption = [];
                    $tmpOption['id'] = $option->id;
                    $tmpOption['optionCd'] = $option->option_cd;
                    $tmpOption['keywordId'] = $option->keyword_id;
                    $tmpOption['keyword'] = $option->keyword;
                    $tmpOption['contentsId'] = $option->idcontents_id;
                    $tmpOption['contents'] = $option->contents;
                    $tmpOption['price'] = $option->price;
                    $tmpOption['count'] = $option->count;
                    $tmpOption['unitPrice'] = $option->unit_price;
                    $tmpMenu['reservationOptions'][] = $tmpOption;
                }

                $resValues['reservation']['reservationMenus'][] = $tmpMenu;
            }

            $resValues['reservation']['reservationStore']['id'] = $reservation->reservationStore->id;
            $resValues['reservation']['reservationStore']['reservationId'] = $reservation->reservationStore->reservation_id;
            $resValues['reservation']['reservationStore']['name'] = $reservation->reservationStore->name;
            $resValues['reservation']['reservationStore']['postalCode'] = $reservation->reservationStore->store->postal_code;
            $resValues['reservation']['reservationStore']['address'] = $reservation->reservationStore->address;
            $resValues['reservation']['reservationStore']['email'] = $reservation->reservationStore->email;
            $resValues['reservation']['reservationStore']['tel'] = $reservation->reservationStore->tel;
            $resValues['reservation']['reservationStore']['latitude'] = $reservation->reservationStore->latitude;
            $resValues['reservation']['reservationStore']['longitude'] = $reservation->reservationStore->longitude;
            $resValues['reservation']['reservationStore']['storeId'] = $reservation->reservationStore->store_id;

            $genres = $this->genreGroup->getCookingGenreByStoreId($reservation->reservationStore->store_id, config('const.genre.bigGenre.b-cooking.word'));
            if (!is_null($genres)) {
                foreach ($genres as $key => $genre) {
                    $resValues['reservation']['reservationStore']['genres'][$key]['id'] = $genre->id;
                    $resValues['reservation']['reservationStore']['genres'][$key]['name'] = $genre->name;
                    $resValues['reservation']['reservationStore']['genres'][$key]['genreCd'] = $genre->genre_cd;
                    $resValues['reservation']['reservationStore']['genres'][$key]['appCd'] = $genre->app_cd;
                    $resValues['reservation']['reservationStore']['genres'][$key]['path'] = $genre->path;
                    $resValues['reservation']['reservationStore']['genres'][$key]['isDelegate'] = $genre->isDelegate;
                }
            }

            $dt = new Carbon($reservation->pick_up_datetime);
            list($week) = $this->menu->getWeek($dt);
            $isHoliday = Holiday::where('date', $dt->format('Y-m-d'))->exists();
            $openingHours = $this->openingHour->getMypageOpeningHour($reservation->reservationStore->store_id, $week, $isHoliday);
            foreach ($openingHours as $key => $openingHour) {
                $resValues['reservation']['reservationStore']['openingHours'][$key]['id'] = $openingHour->id;
                $resValues['reservation']['reservationStore']['openingHours'][$key]['openTime'] = $openingHour->start_at;
                $resValues['reservation']['reservationStore']['openingHours'][$key]['closeTime'] = $openingHour->end_at;
                $resValues['reservation']['reservationStore']['openingHours'][$key]['openingHourCd'] = $openingHour->opening_hour_cd;
                $resValues['reservation']['reservationStore']['openingHours'][$key]['lastOrderTime'] = $openingHour->last_order_time;
                $resValues['reservation']['reservationStore']['openingHours'][$key]['week'] = $openingHour->week;
            }
        } catch (\Throwable $e) {
            \Log::error($e);
            $resValues['status'] = false;
            $resValues['message'] = Lang::get('message.takeoutCompleteFailure');

            return false;
        }

        return true;
    }

    /**
     * アンケート情報を登録する.
     *
     * @param AuthReviewRequest request 登録内容
     * @param array resValues
     *
     * @return bool true:成功 false:失敗
     */
    public function registerReview(AuthReviewRequest $request, array &$resValues = []): bool
    {
        try {
            $resValues['status'] = true;
            $resValues['message'] = Lang::get('message.reviewSuccess');

            $reservationId = substr($request->reservationNo, 2);

            $review = new Review();
            $review->published = 0;
            $review->reservation_id = $reservationId;
            $review->menu_id = $request->menuId;
            $review->evaluation_cd = $request->evaluationCd;
            $review->body = $request->body;
            $review->store_id = ReservationStore::where('reservation_id', $reservationId)->first()->store_id;
            $user = $this->userLogin->getLoginUser();

            if (!is_null($user) && (isset($request->isRealName)) && ($request->isRealName)) {
                $review->user_name = Cipher::encrypt($this->reservation->full_name);
            }
            $review->user_id = CmThApplicationDetail::getApplicationByReservationId($reservationId)->cmThApplication->user_id;
            $review->save();
        } catch (\Throwable $e) {
            $resValues['status'] = false;
            $resValues['message'] = Lang::get('message.reviewFailure');
            \Log::error($e);

            return false;
        }

        return true;
    }

    public static function getApiToken(&$ret)
    {
        // APIトークン生成
        $token = hash_hmac('sha256', uniqid($ret['email']), env('APP_KEY'));
        $ret['api_token'] = $token;

        // kvs上でユーザ情報と紐付け
        $key = config('takeout.apiToken.prefix').$token;
        Redis::set($key, json_encode($ret), config('takeout.apiToken.expiration'));

        // 保存チェック
        $cache = Redis::get($key);
        if (empty($cache)) {
            return false;
        }

        return true;
    }

    public static function getUserInfo($token, &$info)
    {
        $key = config('takeout.apiToken.prefix').$token;
        $info = json_decode(Redis::get($key), true);
        if (empty($info)) {
            return false;
        }

        return true;
    }

    public static function clearToken($token)
    {
        $key = config('takeout.apiToken.prefix').$token;
        Redis::del($key);
        $info = Redis::get($key);
        if (empty($info)) {
            return true;
        }

        return false;
    }
}
