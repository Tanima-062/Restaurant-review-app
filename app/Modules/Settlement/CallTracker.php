<?php

namespace App\Modules\Settlement;

use App\Models\CallTrackerLogs;
use App\Models\CallTrackers;
use App\Models\Store;

class CallTracker
{
    // 30秒につき350円(30秒未満だったら0円)
    const COMMISSION_FEE = 350;
    const COMMISSION_SECONDS = 30;

    private function getLogFromDB(string $startDate, string $endDate, string $advertiserId)
    {
        $callTrackerLogs = CallTrackerLogs::where('client_id', $advertiserId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('valid_status', 1) // コールトラッカーの管理画面で成果判定で30秒で重複判定1日に設定しておかないとここが1にならない
            ->get();

        $data = [];
        foreach ($callTrackerLogs as $callTrackerLog) {
            $tmp['amount'] = (int)($callTrackerLog['call_secs'] / self::COMMISSION_SECONDS) * self::COMMISSION_FEE;
            $data[] = $tmp;
        }
        return $data;
    }

    public function getCommissionByMonth(string $startDate, string $endDate, int $settlementCompanyId)
    {
        $stores = Store::where('settlement_company_id', $settlementCompanyId)->get(['id', 'name']);
        $callTrackers = CallTrackers::whereIn('store_id', $stores->pluck('id')->all())->get();

        $logData = [];
        foreach ($callTrackers as $callTracker) { // 店舗単位
            $logs = $this->getLogFromDB($startDate, $endDate, $callTracker->advertiser_id);
            $data = [
                'store_id' => $callTracker->store_id,
                'storeName' => $stores->firstWhere('id', $callTracker->store_id)->name,
                'count' => count($logs),
                'amount' => array_sum(array_column($logs, 'amount'))
            ];
            $logData[] = $data;
        }

        return $logData;
    }
}
