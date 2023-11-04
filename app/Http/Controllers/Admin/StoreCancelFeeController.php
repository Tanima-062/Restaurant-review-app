<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreCancelFeeRequest;
use App\Models\CancelFee;
use App\Models\Store;

class StoreCancelFeeController extends AdminController
{
    protected $redirectTo = 'admin/store/%d/cancel_fee';

    /**
     * 管理画面 - キャンセル料(一覧表示)
     *
     * @param integer $id
     * 
     * @return view
     */
    public function index(int $id)
    {
        $store = Store::find($id);
        $this->authorize('store', $store); // ポリシーによる、個別に制御

        $cancelFees = CancelFee::where('store_id', $id)->sortable()->get();

        // indexから来た場合のみ、redirectのためにURLをセッションで保持
        if (isset(parse_url(url()->previous())['query']) && preg_match('/^page=[0-9]*/', parse_url(url()->previous())['query'])) {
            session(['storeCancelFeeRedirectTo' => url()->previous()]);
        }

        return view(
            'admin.Store.CancelFee.index',
            [
                'store' => $store,
                'cancelFees' => $cancelFees,
            ]
        );
    }

    /**
     * 管理画面 - キャンセル料(追加画面)
     *
     * @param integer $id
     * 
     * @return view
     */
    public function addForm(int $id)
    {
        $store = Store::find($id);
        $this->authorize('store', $store); // ポリシーによる、個別に制御

        $storeCancelFeeConst = config('const.storeCancelFee');

        return view(
            'admin.Store.CancelFee.add',
            [
                'store' => $store,
                'storeCancelFeeConst' => $storeCancelFeeConst,
            ]
        );
    }

    /**
     * 管理画面 - キャンセル料(追加処理)
     * 
     * @param StoreCancelFeeRequest $request
     * 
     */
    public function add(StoreCancelFeeRequest $request)
    {
        try {
            \DB::beginTransaction();

            $store = Store::find($request->store_id);
            $this->authorize('store', $store); // ポリシーによる、個別に制御

            $data = $request->except('_token', 'redirect_to', 'store_name', 'store_code');

            //DB登録の為の日付整形
            $data['apply_term_from'] = str_replace('/', '-', $data['apply_term_from']);
            $data['apply_term_to'] = str_replace('/', '-', $data['apply_term_to']);

            // 来店後が選択されていた場合、期限単位、期限をNULLにする
            if ($data['visit'] === config('code.cancelPolicy.visit.after')) {
                $data['cancel_limit_unit'] = NULL;
                $data['cancel_limit'] = NULL;
                $data['cancel_fee'] = 100;
                $data['cancel_fee_unit'] = config('code.cancelPolicy.cancel_fee_unit.fixedRate');
            }

            CancelFee::create($data);
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollback();

            return redirect(sprintf($this->redirectTo, $request->store_id))
            ->with('custom_error', '登録に失敗しました。');
        }

        return redirect(sprintf($this->redirectTo, $data['store_id']))
            ->with('message', 'キャンセル料を登録しました。');
    }

    /**
     * 管理画面 - キャンセル料(編集画面)
     *
     * @param integer $id
     * 
     * @return view
     */
    public function editForm(int $id, int $cancel_fee_id)
    {
        $store = Store::find($id);
        $this->authorize('store', $store); // ポリシーによる、個別に制御

        $cancelFee = CancelFee::find($cancel_fee_id);

        $storeCancelFeeConst = config('const.storeCancelFee');

        return view(
            'admin.Store.CancelFee.edit',
            [
                'store' => $store,
                'cancelFee' => $cancelFee,
                'storeCancelFeeConst' => $storeCancelFeeConst,
            ]
        );
    }

    /**
     * 管理画面 - キャンセル料(更新処理)
     * 
     * @param integer $id
     * 
     */
    public function edit(StoreCancelFeeRequest $request, int $cancel_fee_id)
    {
        try {
            \DB::beginTransaction();

            $store = Store::find($request->store_id);
            $this->authorize('store', $store); // ポリシーによる、個別に制御

            $cancelFee = CancelFee::find($cancel_fee_id);
            $data = $request->except('_token', 'redirect_to', 'store_name', 'store_code');

            // 来店後が選択されていた場合、期限単位、期限をNULLにする
            if ($data['visit'] === config('code.cancelPolicy.visit.after')) {
                $data['cancel_limit_unit'] = NULL;
                $data['cancel_limit'] = NULL;
                $data['cancel_fee'] = 100;
                $data['cancel_fee_unit'] = config('code.cancelPolicy.cancel_fee_unit.fixedRate');
            }

            //DB更新の為の整形
            $data['published'] = (empty($data['published'])) ? 0 : 1;
            $data['apply_term_from'] = str_replace('/', '-', $data['apply_term_from']);
            $data['apply_term_to'] = str_replace('/', '-', $data['apply_term_to']);

            //DB更新
            $cancelFee->update([
            'app_cd' => $data['app_cd'],
            'apply_term_from' => $data['apply_term_from'],
            'apply_term_to' => $data['apply_term_to'],
            'visit' => $data['visit'],
            'cancel_limit' => $data['cancel_limit'],
            'cancel_limit_unit' => $data['cancel_limit_unit'],
            'cancel_fee' => $data['cancel_fee'],
            'cancel_fee_unit' => $data['cancel_fee_unit'],
            'fraction_unit' => $data['fraction_unit'],
            'fraction_round' => $data['fraction_round'],
            'cancel_fee_max' => $data['cancel_fee_max'],
            'cancel_fee_min' => $data['cancel_fee_min'],
            'published' =>  $data['published'],
            ]);
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollback();

            return redirect(sprintf($this->redirectTo, $request->store_id))
            ->with('custom_error', '更新に失敗しました。');
        }

        return redirect(sprintf($this->redirectTo, $data['store_id']))
            ->with('message', 'キャンセル料を登録しました。');
    }

    /**
     * 管理画面 - キャンセル料(削除)
     * 
     * @param integer $id
     * 
     * @param integer $cancel_fee_id
     */
    public function delete(int $id, int $cancel_fee_id)
    {
        try {
            $store = Store::find($id);
            $this->authorize('store', $store); // ポリシーによる、個別に制御
            $cancelFee = CancelFee::find($cancel_fee_id)->delete();
            return response()->json(['result' => 'ok']);
        } catch (\Throwable $e) {
            return False;
        }
    }
}
