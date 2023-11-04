<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CallTracerRequest;
use App\Http\Requests\Admin\StoreApiRequest;
use App\Http\Requests\Admin\TelSupportRequest;
use App\Models\CallTrackers;
use App\Models\ExternalApi;
use App\Models\Store;
use App\Models\TelSupport;

class StoreApiController extends AdminController
{
    /**
     * 管理画面 - 店舗API(編集画面)
     *
     * @param int $id
     */
    public function editForm($id)
    {
        // indexから来た場合のみ、redirectのためにURLをセッションで保持
        if (isset(parse_url(url()->previous())['query']) && preg_match('/^page=[0-9]*/', parse_url(url()->previous())['query'])) {
            session(['storeApiRedirectTo' => url()->previous()]);
        }

        $store = Store::find($id);
        $this->authorize('store', $store); // ポリシーによる、個別に制御

        $externalApi = ExternalApi::where('store_id', $id)->first();
        $callTracker = CallTrackers::where('store_id', $id)->first();
        $telSupport = TelSupport::where('store_id', $id)->first();

        return view(
            'admin.Store.Api.edit',
            [
                'store' => $store,
                'externalApi' => $externalApi,
                'externalApiCd' => config('code.externalApiCd'),
                'callTracker' => $callTracker,
                'telSupport' => $telSupport,
                'hasTelSupport' => config('const.store.tel_support'),
            ]
        );
    }

    /**
     * 管理画面 - 店舗API(更新/登録処理)
     *
     * @param StoreApiRequest $request
     * @param int $id
     */
    public function edit(StoreApiRequest $request, $id)
    {
        try {
            \DB::beginTransaction();
            $store = Store::find($id);
            $this->authorize('store', $store); // ポリシーによる、個別に制御

            ExternalApi::updateOrCreate(
                [
                    'store_id' => $id
                ],
                [
                    'store_id' => $id,
                    'api_cd' => $request->api_cd,
                    'api_store_id' => $request->api_store_id,
                ]
            );
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollback();

            return redirect(route('admin.store.api.editForm', ['id' => $id]))
                ->with('custom_error', '更新に失敗しました。');
        }

        return redirect(route('admin.store.api.editForm', ['id' => $id]))
    ->with('message', '更新しました。');
    }

    /**
     * 管理画面 - 店舗API(削除)
     *
     * @param [type] $id
     *
     * @return void
     */
    public function delete($id)
    {
        try {
            $store = Store::find($id);
            $this->authorize('store', $store); // ポリシーによる、個別に制御

            ExternalApi::where('store_id', $id)->delete();

            return response()->json(['result' => 'ok']);
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * @param CallTracerRequest $request
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function callEdit(CallTracerRequest $request, int $id)
    {
        $this->authorize('store', Store::find($id)); // ポリシーによる、個別に制御
        try {
            $callTracker = CallTrackers::firstOrNew(['store_id' => $id]);
            $callTracker->store_id = $id;
            $callTracker->advertiser_id = $request->input('advertiser_id', '');
            $callTracker->save();
        } catch (\Throwable $e) {
            return redirect(route('admin.store.api.editForm', ['id' => $id]))
                ->with('custom_error', '更新に失敗しました。');
        }

        return redirect(route('admin.store.api.editForm', ['id' => $id]))
            ->with('message', '更新しました。');
    }

    /**
     * @param int $id
     * @return bool|\Illuminate\Http\JsonResponse
     */
    public function callDelete(int $id)
    {
        try {
            $this->authorize('store', Store::find($id)); // ポリシーによる、個別に制御

            CallTrackers::where('store_id', $id)->delete();

            return response()->json(['result' => 'ok']);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param TelSupportRequest $request
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function telSupportEdit(TelSupportRequest $request, int $id)
    {
        $this->authorize('store', Store::find($id)); // ポリシーによる、個別に制御
        try {
            $callTracker = TelSupport::firstOrNew(['store_id' => $id]);
            $callTracker->store_id = $id;
            $callTracker->is_tel_support = $request->input('tel_support', '');
            $callTracker->save();
        } catch (\Throwable $e) {
            return redirect(route('admin.store.api.editForm', ['id' => $id]))
                ->with('custom_error', '更新に失敗しました。');
        }

        return redirect(route('admin.store.api.editForm', ['id' => $id]))
            ->with('message', '更新しました。');
    }
}
