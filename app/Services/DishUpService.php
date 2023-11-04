<?php

namespace App\Services;

use App\Libs\Mail\TakeoutMail;
use App\Models\Reservation;
use App\Models\Staff;

class DishUpService
{
    public function __construct(
        Reservation $reservation
    ) {
        $this->reservation = $reservation;
    }

    /**
     * 注文を受ける(調理開始).
     *
     * @param int reservationId 予約ID
     *
     * @return true:成功 false:失敗
     */
    public function startCooking($reservationId)
    {
        try {
            $this->reservation->startCooking($reservationId);

            // メール送信
            $takeoutMailClient = new TakeoutMail($reservationId);
            $takeoutMailClient->confirmReservationByClient();
        } catch (\Throwable $e) {
            \Log::error(
                sprintf('::reservationId=(%s), error=%s', $reservationId, $e)
            );

            return false;
        }

        return true;
    }

    /**
     * 注文一覧取得.
     *
     * @param int staffId 担当者ID
     * @param int reservationDate 注文(予約)日
     *
     * @return array res 予約リスト
     */
    public function list($staffId, $reservationDate)
    {
        $query = Reservation::whereBetween('pick_up_datetime', [$reservationDate.' 00:00:00', $reservationDate.' 23:59:59'])
            ->whereNull('pick_up_receive_datetime')
            ->where('reservation_status', '!=', config('code.reservationStatus.cancel.key'));

        $query->whereHas('reservationStore', function ($query) use ($staffId) {
            $query->where('store_id', Staff::find($staffId)->store_id);
        });

        $reservations = $query->get();
        $res = [];
        foreach ($reservations as $reservation) {
            $tmpRes = [];
            $tmpRes['id'] = $reservation->id;
            $tmpRes['reservationNo'] = $this->reservation->getTakeoutReservationNo($reservation->id);
            $tmpRes['reservationStatus'] = $reservation->reservation_status;
            $tmpRes['persons'] = $reservation->persons;
            $tmpRes['pickUpDateTime'] = $reservation->pick_up_datetime;
            $tmpRes['storeReceptionDateTime'] = $reservation->store_reception_datetime;
            $tmpRes['request'] = $reservation->request;
            $tmpRes['store']['useFax'] = ((int) $reservation->reservationStore->use_fax === 1) ? true : false;
            $tmpRes['createdAt'] = $reservation->created_at;
            foreach ($reservation->reservationMenus as $menu) {
                $tmpMenu = [];
                $tmpMenu['id'] = $menu->id;
                $tmpMenu['reservationId'] = $menu->reservation_id;
                $tmpMenu['name'] = $menu->name;
                $tmpMenu['count'] = $menu->count;
                $tmpMenu['unitPrice'] = $menu->unit_price;
                $tmpMenu['price'] = $menu->price;
                foreach ($menu->reservationOptions as $option) {
                    $tmpOption = [];
                    $tmpOption['id'] = $option->id;
                    $tmpOption['optionCd'] = $option->option_cd;
                    $tmpOption['keywordId'] = $option->keyword_id;
                    $tmpOption['keyword'] = $option->keyword;
                    $tmpOption['contentsId'] = $option->contents_id;
                    $tmpOption['contents'] = $option->contents;
                    $tmpOption['price'] = $option->price;
                    $tmpMenu['reservationOptions'][] = $tmpOption;
                }
                $tmpRes['reservationMenus'][] = $tmpMenu;
            }
            $res[] = $tmpRes;
        }

        return $res;
    }
}
