<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettlementAggregateRequest;
use App\Models\SettlementCompany;
use App\Modules\Settlement\AllAggregate;
use Carbon\Carbon;

class SettlementAggregateController extends Controller
{
    const PAGINATE = 5;
    private $allAggregate = null;
    private $settle = 0;
    private $shop = 0;
    private $appCd = '';
    private $term = -1;
    private $carryForwardPriceList = []; // 繰延額リスト

    public function index(SettlementAggregateRequest $request)
    {
        $monthOne = $request->input('monthOne', 0);
        $monthTwo = $request->input('monthTwo', 0);
        $termYear = $request->input('termYear', Carbon::now()->format('Y'));
        $termMonth = $request->input('termMonth', Carbon::now()->format('m'));
        $settlementCompanyId = $request->input('settlementCompanyId', 0);
        $page = $request->input('page', 1);

        $term = sprintf("%d%02d", $termYear, $termMonth);

        $this->allAggregate = new AllAggregate($settlementCompanyId, $term, ($monthOne + $monthTwo));
        $allAggregate = $this->settViewFormat($this->allAggregate->allAggregate);
        $partAggregate = $this->settViewFormat($this->allAggregate->aggregate);

        if (\Auth::user()->settlement_company_id > 0) {
            $this->allAggregate->allSettlementCompanyIds = [\Auth::user()->settlement_company_id];
            $this->allAggregate->partSettlementCompanyIds = [\Auth::user()->settlement_company_id];
        }
        // 精算管理会社が大量にいるのでページング対応する。モデルのsortableと一致するようにする。
        $pageCol = collect($this->allAggregate->partSettlementCompanyIds);
        $setList = $pageCol->forPage($page, self::PAGINATE);
        $aggregate = $partAggregate->whereIn('settlement_company_id', $setList);

        $settlementCompanies = SettlementCompany::whereIn('id', $this->allAggregate->allSettlementCompanyIds)->get();

        $partSettlementCompanies = SettlementCompany::whereIn('id', $this->allAggregate->partSettlementCompanyIds)
            ->sortable()->paginate(self::PAGINATE);

        return view('admin.SettlementAggregate.index', [
            'allAggregate' => $allAggregate,
            'aggregate' => $aggregate,
            'settlementCompanies' => $settlementCompanies,
            'partSettlementCompanies' => $partSettlementCompanies,
        ]);
    }

    private function settViewFormat($aggregate)
    {
        // 同じキーが出てきたらフラグを落とす。フラグが落ちてたら表示しない
        return $aggregate->map(function ($agg, $key){
            $flags = explode('.', $key);

            $agg['term'] = $this->allAggregate->termStr($agg['term']);
            $agg['app_cd'] = $this->allAggregate->appCdStr($agg['app_cd']);

            // 販売手数料
            $agg['commission_fixed_tax'] = floor($agg['commission_rate_fixed_fee_tax'] * (float)$agg['tax'] / 100);    // 税額(事前決済)
            $agg['commission_rate_fixed_fee_no_tax'] = $agg['commission_rate_fixed_fee_tax'] - $agg['commission_fixed_tax']; // 税抜手数料(事前決済)
            $agg['commission_flat_tax'] = floor($agg['commission_rate_flat_fee_tax'] * (float)$agg['tax'] / 100);      // 税額(現地決済)
            $agg['commission_rate_flat_fee_no_tax'] = $agg['commission_rate_flat_fee_tax'] - $agg['commission_flat_tax'];    // 税抜手数料(現地決済)

            // キャンセル料手数料
            $agg['cancel_rate'] = $agg['commission_rate_fixed']; // キャンセル料料率
            $agg['cancel_rate_fixed_fee_tax'] = floor($agg['cancel_detail_price_sum'] * (float)$agg['cancel_rate'] / 100); // 税込(事前決済)
            $agg['cancel_fixed_tax'] = floor($agg['cancel_rate_fixed_fee_tax'] * (float)$agg['tax'] / 100); // 税額(事前決済)
            $agg['cancel_rate_fixed_fee_no_tax'] = $agg['cancel_rate_fixed_fee_tax'] - $agg['cancel_fixed_tax'];  // 税抜手数料(事前決済)

            // 当期精算 TODO: 電話手数料を後で足す
            $agg['current_settlement_price'] = ($agg['total'] + $agg['cancel_detail_price_sum']) -
                ($agg['commission_rate_fixed_fee_tax'] + $agg['commission_rate_flat_fee_tax'] + $agg['cancel_rate_fixed_fee_tax']);

            $agg['carry_forward_price'] = 0; // 前期繰越額

            if (count($flags) == 4) {
                $preKey = sprintf("%d.%d.%s.1", $flags[0], $flags[1], $flags[2]);
                // 前期繰越(1日~15日)
                if ((int)$flags[3] == 1 && abs($agg['current_settlement_price']) <= 3000) {
                    $this->carryForwardPriceList[$key] = $agg['current_settlement_price'];
                    $agg['settlement_price'] = 0; // 精算金額
                } elseif ((int)$flags[3] == 2 && isset($this->carryForwardPriceList[$preKey])) { // 前期繰越(16日~月末)
                    $agg['carry_forward_price'] = $this->carryForwardPriceList[$preKey]; // 前期繰越額
                    $agg['settlement_price'] = $agg['current_settlement_price'] + $agg['carry_forward_price']; // 精算金額
                } else {
                    $agg['settlement_price'] = $agg['current_settlement_price'];
                }
            }

            // 精算種別
            if ($agg['app_cd'] == '合計') {
                $agg['settlement_type'] = ($agg['settlement_price'] > 0) ? '支払': '請求';
            }

            if (count($flags) != 4) {
                return $agg;
            }

            if ($flags[0] == $this->settle) {
                $agg['v_settle'] = false;
            }
            if ($flags[0] == $this->settle && $flags[1] == $this->shop) {
                $agg['v_shop'] = false;
            }
            if ($flags[0] == $this->settle && $flags[1] == $this->shop && $flags[2] == $this->appCd) {
                $agg['v_app_cd'] = false;
            }
            if ($flags[0] == $this->settle && $flags[1] == $this->shop && $flags[2] == $this->appCd &&
                $flags[2] == $this->appCd && $flags[3] == $this->term) {
                $agg['v_term'] = false;
            }

            $this->settle = $flags[0];
            $this->shop = ($flags[0] == $this->settle) ? $flags[1] : 0;
            $this->appCd = ($flags[0] == $this->settle && $flags[1] == $this->shop) ? $flags[2] : '';
            $this->term = ($flags[0] == $this->settle && $flags[1] == $this->shop && $flags[2] == $this->appCd) ? $flags[3] : -1;

            return $agg;
        });
    }
}
