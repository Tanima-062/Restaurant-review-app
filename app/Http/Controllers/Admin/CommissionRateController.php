<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CommissionRateRequest;
use App\Models\CommissionRate;
use Illuminate\Http\Request;

class CommissionRateController extends Controller
{
    protected $redirectTo = "admin/settlement_company/%d";

    public function index(Request $request, int $settlementCompanyId)
    {
        $config = config('const.commissionRate');
        $commissionRates = CommissionRate::settlementCompanyId($settlementCompanyId)->sortable()->get();

        return view('admin.CommissionRate.index',
            [
                'page' => $request->get('page', 1),
                'config' => $config,
                'commissionRates' => $commissionRates,
                'settlementCompanyId' => $settlementCompanyId
            ]
        );
    }

    public function addForm(int $settlementCompanyId)
    {
        return view('admin.CommissionRate.add',
            [
                'settlementCompanyId' => $settlementCompanyId,
                'onlySeats' => config('const.commissionRate.only_seat')
            ]
        );
    }

    public function add(CommissionRateRequest $request)
    {
        try {
            \DB::beginTransaction();
            $data = $this->format($request);

            CommissionRate::create($data);
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollBack();

            return redirect(sprintf($this->redirectTo.'/commission_rate', $request->settlement_company_id))
                ->with('custom_error', '販売手数料を作成できませんでした');
        }

        return redirect(sprintf($this->redirectTo.'/commission_rate', $data['settlement_company_id']))
            ->with('message', "販売手数料を作成しました");
    }

    public function editForm(int $settlementCompanyId, int $commissionRateId)
    {
        return view(
            'admin.CommissionRate.edit',
            [
                'settlementCompanyId' => $settlementCompanyId,
                'commissionRate' => CommissionRate::find($commissionRateId),
                'onlySeats' => config('const.commissionRate.only_seat'),
            ]
        );
    }

    public function edit(CommissionRateRequest $request)
    {
        try {
            \DB::beginTransaction();
            $data = $this->format($request);

            $commissionRate = CommissionRate::find($data['id']);
            $commissionRate->setRawAttributes($data);
            $commissionRate->save();
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollBack();

            return redirect(sprintf($this->redirectTo.'/commission_rate', $request->settlement_company_id))
                ->with('custom_error', '販売手数料を更新できませんでした');
        }

        return redirect(sprintf($this->redirectTo.'/commission_rate', $data['settlement_company_id']))
            ->with('message', "販売手数料を編集しました");
    }

    private function format($request)
    {
        $fromYM = sprintf(
            '%04d-%02d',
            $request->input('apply_term_from_year'),
            $request->input('apply_term_from_month')
        );
        $toYM = sprintf(
            '%04d-%02d',
            $request->input('apply_term_to_year'),
            $request->input('apply_term_to_month')
        );

        $data = $request->except(['_token', 'redirect_to']);
        $data['published'] = (!empty($data['published'])) ? 1 : 0;
        $data['apply_term_from'] = date('Y-m-d 00:00:00', strtotime('first day of ' . $fromYM));
        $data['apply_term_to'] = date('Y-m-d 23:59:59', strtotime('last day of ' . $toYM));
        unset($data['apply_term_from_year']);
        unset($data['apply_term_from_month']);
        unset($data['apply_term_to_year']);
        unset($data['apply_term_to_month']);

        return $data;
    }

    public function delete(int $settlementCompanyId, int $id)
    {
        try {
            CommissionRate::destroy($id);
            return response()->json(['result' => 'ok']);
        } catch (\Exception $e) {
            return false;
        }
    }
}
