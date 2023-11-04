<?php

namespace App\Modules\Ebica;

use App\Libs\Cipher;
use App\Modules\Ebica\EbicaBase;
use App\Modules\Ebica\EbicaValidation;
use App\Modules\Reservation\IFReservation;
use App\Models\Vacancy;
use App\Models\Menu;
use App\Models\Store;
use App\Models\Option;
use App\Modules\Ebica\EbicaChangeReserve;
use App\Modules\Ebica\EbicaStockSave;
use App\Models\Reservation;
use App\Models\Stock;
use Carbon\Carbon;

class EbicaReservation implements IFReservation
{
    /**
     * レストラン予約登録処理
     *
     * @param array $info
     * @param Reservation $reservation
     * @param string $errMsg
     *
     * @return boolean
     */
    public function saveReservation(array $info, Reservation $reservation, string &$errMsg = null)
    {
        try {
            $ebicaReserve = new EbicaReserve();

            $data['date'] = $info['application']['visitDate'];
            $data['time'] = $info['application']['visitTime'];
            $data['first_name'] = Cipher::decrypt($info['customer']['firstName']);
            $data['last_name'] = Cipher::decrypt($info['customer']['lastName']);
            $data['headcount'] = $info['application']['persons'];
            $data['phone_number'] = Cipher::decrypt($info['customer']['tel']);
            $data['email'] = Cipher::decrypt($info['customer']['email']);
            $data['remarks'] = !empty($info['customer']['request']) ? Cipher::decrypt($info['customer']['request']) : '';

            $menu = $info['application']['menus'][0];
            $menuObj = Menu::find($menu['menu']['id']);
            $data['prepaid'] = $menuObj->menuPrice($info['application']['visitDate'])->price == 0 ? '未決済' : '済み';
            $data['shop_id'] = Store::find($reservation->reservationStore->store_id)->external_api->api_store_id;
            //登録用メニュー配列作成
            $data['course_name'] = $menuObj->name;
            $data['course_count'] = $info['application']['persons'];
            if (isset($menu['options'])) {
                foreach ($menu['options'] as $key => $option) {
                    $optionObj = Option::where('id', $option['id'])->first();
                    $options[] = $optionObj->keyword.':'.$optionObj->contents.' × '.$option['count'];
                }
                $data['option'] = join("\n", $options);
            }

            $ebicaReservation = $ebicaReserve->postReservation($data);

            if (!$ebicaReservation) {
                $errMsg = isset($ebicaReserve->errorMsg) ?  $ebicaReserve->errorMsg : Lang::get('message.restaurantCompleteFailure');
                return false;
            }
            $reservation->external_reservation_id = $ebicaReservation['reservation_id'];
            $reservation->save();
            return true;
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function changeReservation(Reservation $reservation, &$msg = null)
    {
        try {
            $ebicaChangeReserve = new EbicaChangeReserve();
            $dt = new Carbon($reservation->pick_up_datetime);

            $data['status'] = 'change';
            $data['shop_id'] = Store::find($reservation->reservationStore->store_id)->external_api->api_store_id;
            $data['date'] = $dt->format('Y-m-d');
            $data['time'] = $dt->format('H:i');
            $data['headcount'] = $reservation->persons;
            $data['reservation_id'] = $reservation->external_reservation_id;
            $data['remarks'] = !empty($reservation->request) ? $reservation->request : '';
            $data['prepaid'] = '済み';
            $data['force'] = config('restaurant.ebica.changeReservation.force');
            //メニュー/オプション用の配列を作成
            foreach ($reservation->reservationMenus as $reservationMenu) {
                $data['course_name'] = $reservationMenu->name;
                $data['course_count'] = $reservation->persons;
                if ($reservationMenu->reservationOptions->count() > 0) {
                    foreach ($reservationMenu->reservationOptions as $reservationOption) {
                        $options[] = $reservationOption->keyword.':'.$reservationOption->contents.' × '.$reservationOption->count;
                    }
                    $data['option'] = join("\n", $options);
                }
            }

            $ebicaReservation = $ebicaChangeReserve->patchReservation($data);
            if (!$ebicaReservation) {
                $msg = isset($ebicaChangeReserve->errorMsg) ?  $ebicaChangeReserve->errorMsg : Lang::get('message.restaurantCompleteFailure');
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function cancelReservation(Reservation $reservation, &$msg)
    {
        try {
            $ebicaChangeReserve = new EbicaChangeReserve();
            $data['status'] = 'cancel';
            $data['reservation_id'] = $reservation->external_reservation_id;
            $data['shop_id'] = Store::find($reservation->reservationStore->store_id)->external_api->api_store_id;
            $data['force'] = config('restaurant.ebica.changeReservation.force');

            $ebicaReservation = $ebicaChangeReserve->patchReservation($data);
            if (!$ebicaReservation) {
                $msg = \Lang::get('message.reservationCancelFaimure0');
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function getClosingTime(int $apiStoreId, Carbon $dt)
    {
        $ebicaStockSave = new EbicaStockSave;

        $stocks = json_decode(json_encode($ebicaStockSave->getStock($apiStoreId, $dt->format('Y-m-d'))), true);
        if (empty($stocks['stocks'])) {
            return null;
        }

        $externalClosingTime = end(end($stocks['stocks'])['stock'])['reservation_time'];

        return $externalClosingTime;
    }
}
