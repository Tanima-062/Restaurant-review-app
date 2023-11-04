<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOpeningHourEditRequest;
use App\Libs\DateTimeDuplicate;
use App\Models\OpeningHour;
use App\Models\Store;
use Carbon\Carbon;
use DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Validator;
use View;

class StoreOpeningHourController extends Controller
{
    /**
     * 管理画面 - 店舗営業時間(編集画面).
     *
     * @param int $id
     *
     * @return View
     */
    public function editForm($id)
    {
        // indexから来た場合のみ、redirectのためにURLをセッションで保持
        if (isset(parse_url(url()->previous())['query']) && preg_match('/^page=[0-9]*/', parse_url(url()->previous())['query'])) {
            session(['storeOpeningHourRedirectTo' => url()->previous()]);
        }

        $store = Store::findOrFail($id);
        $this->authorize('store', $store); // ポリシーによる、個別に制御

        $storeOpeningHourConst = config('const.storeOpeningHour');
        $storeOpeningHours = OpeningHour::storeId($id)->orderBy('id', 'asc');

        return view(
            'admin.Store.OpeningHour.edit',
            [
                'store' => $store,
                'storeOpeningHourExists' => $storeOpeningHours->exists(),
                'storeOpeningHours' => $storeOpeningHours->get(),
                'codes' => $storeOpeningHourConst['opening_hour_cd'],
                'weeks' => $storeOpeningHourConst['week'],
            ]
        );
    }

    /**
     * 管理画面 - 店舗営業時間(更新).
     *
     * @param StoreOpeningHourEditRequest request
     * @param int id
     *
     * @return Redirector
     */
    public function edit(StoreOpeningHourEditRequest $request, int $id)
    {
        $store = Store::findOrFail($id);
        $this->authorize('store', $store); // ポリシーによる、個別に制御

        $inputStores = $request->input('store');
        $error = Validator::make($request->all(), $request->rules());

        // 新規追加+追加直後の削除の操作に伴い、配列の要素番号が飛び番になることがあるため、要素番号を再発番
        $stores = array_values($inputStores);

        // 曜日別にまとめるFor文
        $storeWeeks = $stores;
        $numberOfWeek = [];
        foreach ($storeWeeks as $form) {
            for ($i = 0; $i <= 7; ++$i) {
                $numberOfWeek[$i][] = $form['week'][$i];
            }
        }

        // 曜日に「1」がある場合にstart_time, end_timeを配列をつくる
        $opens = [];
        foreach ($numberOfWeek as $key => $value) {
            foreach ($value as $num => $v) {
                if ($v === '1') {
                    $opens[$key][] = [
                        'start_at' => $stores[$num]['start_at'],
                        'end_at' => $stores[$num]['end_at'],
                    ];
                }
            }
        }

        // 同じ曜日、時間帯の重複を確認する処理
        $is_duplicate = false;
        if (!is_null($opens)) {
            foreach ($opens as $i => $open) {
                if (count($open) > 1) { // 同じ曜日に「1」が2つ以上ある場合、比較対象になる
                    $storeOpeningHours1 = $open;
                    $storeOpeningHours2 = $open;
                    foreach ($storeOpeningHours1 as $key1 => $storeOpeningHour1) {
                        $start_at1 = $storeOpeningHour1['start_at'];
                        $end_at1 = $storeOpeningHour1['end_at'];
                        foreach ($storeOpeningHours2 as $key2 => $storeOpeningHour2) {
                            $start_at2 = $storeOpeningHour2['start_at'];
                            $end_at2 = $storeOpeningHour2['end_at'];
                            if ($key1 === $key2) {
                                continue;
                            }
                            if (DateTimeDuplicate::isTimeDuplicate($start_at1, $end_at1, $start_at2, $end_at2)) {
                                $is_duplicate = true;
                                break 2;
                            }
                            // 比較後の削除
                            unset($storeOpeningHours1[$key1]);
                        }
                    }
                }
            }
        }

        foreach ($stores as $storeOpeningHour) {
            // データ更新
            $checkedWeek = implode($storeOpeningHour['week'], '');
            $error->after(function (\Illuminate\Validation\Validator $validator) use ($checkedWeek, $is_duplicate) {
                // 営業曜日にチェックが入っていない場合
                if ($checkedWeek === '00000000') {
                    $validator->errors()->add($checkedWeek, '営業曜日は、必ず一つ以上チェックしてください');
                }
                // 曜日、時間帯が重複した場合のバリデーション
                if ($is_duplicate) {
                    $validator->errors()->add($checkedWeek, '営業曜日または、営業時間の設定が重複しています');
                }
            })->validate();
            try {
                \DB::beginTransaction();

                if (empty($storeOpeningHour['opening_hour_id'])) {
                    // 新規追加
                    OpeningHour::create([
                        'store_id' => $request->input('store_id'),
                        'opening_hour_cd' => $storeOpeningHour['opening_hour_cd'],
                        'start_at' => $storeOpeningHour['start_at'],
                        'end_at' => $storeOpeningHour['end_at'],
                        'last_order_time' => $storeOpeningHour['last_order_time'],
                        'week' => $checkedWeek,
                    ]);
                } else {
                    // 更新
                    OpeningHour::withoutGlobalScopes()
                    ->where('store_id', $id)
                    ->where('id', $storeOpeningHour['opening_hour_id'])
                    ->update([
                        'opening_hour_cd' => $storeOpeningHour['opening_hour_cd'],
                        'start_at' => $storeOpeningHour['start_at'],
                        'end_at' => $storeOpeningHour['end_at'],
                        'last_order_time' => $storeOpeningHour['last_order_time'],
                        'week' => $checkedWeek,
                    ]);
                }
                \DB::commit();
            } catch (\Exception $e) {
                report($e);
                DB::rollback();

                return redirect(url()->previous())->with(
                    'custom_error',
                    sprintf('「%s」の営業時間を保存できませんでした。', $request->input('store_name'))
                );
            }
        }

        return redirect(url()->previous())->with(
            'message',
            sprintf('「%s」の営業時間を保存しました。', $request->input('store_name'))
        );
    }

    /**
     * 管理画面 - 店舗営業時間(追加画面).　（※追加方法の仕様変更変更に伴い、本関数は未使用）
     *
     * @param int id
     *
     * @return View
     */
    public function addForm(int $id)
    {
        $store = Store::findOrFail($id);
        $this->authorize('store', $store); // ポリシーによる、個別に制御

        $storeOpeningHourConst = config('const.storeOpeningHour');

        return view(
            'admin.Store.OpeningHour.add',
            [
                'store' => $store,
                'codes' => $storeOpeningHourConst['opening_hour_cd'],
                'weeks' => $storeOpeningHourConst['week'],
            ]
        );
    }

    /**
     * 管理画面 - 店舗営業時間(追加).　（※追加方法の仕様変更変更に伴い、本関数は未使用）
     *
     * @param Request request
     *
     * @return RedirectResponse
     */
    public function add(Request $request)
    {
        if ($request->ajax()) {
            $rules = [
                'opening_hour_cd' => 'required',
                'start_at' => ['required', 'regex:/\A([01][0-9]|2[0-3]):[0-5][0-9]\Z/'],
                'end_at' => ['required', 'regex:/\A([01][0-9]|2[0-3]):[0-5][0-9]\Z/'],
                'last_order_time' => 'required',
            ];

            $store = Store::findOrFail($request->input('store_id'));
            $this->authorize('store', $store); // ポリシーによる、個別に制御

            // DBから曜日データの取得
            $hasStoreOpeningHours = OpeningHour::storeId($request->store_id)
                ->select('week', 'start_at', 'end_at')
                ->orderBy('id', 'asc')->get();

            // DBのデータを曜日別にまとめるFor文
            $numberOfWeek = [];
            foreach ($hasStoreOpeningHours as $hasStoreOpeningHour) {
                for ($i = 0; $i <= 7; ++$i) {
                    $numberOfWeek[$i][] = $hasStoreOpeningHour->week[$i];
                }
            }

            $collectionWeek = collect($numberOfWeek);
            $arrayWeek = $collectionWeek->toArray();
            $checkedWeeks = $request->input('week');
            $checkedWeek = implode($checkedWeeks, '');
            $inputStartTime = $request->input('start_at');
            $inputEndTime = $request->input('end_at');

            // DBから取得した曜日配列にRequestのデータを挿入して、$opensの配列をつくる
            $opens = [];
            foreach ($arrayWeek as $key => $value) {
                $value[] = $checkedWeeks[$key];
                $lastkey = array_key_last($value);
                foreach ($value as $num => $v) {
                    if ($v === '1') {
                        $opens[$key][] = [
                            'start_at' => $num === $lastkey ?
                                $inputStartTime :
                                Carbon::parse($hasStoreOpeningHours[$num]->start_at)->format('H:i'),
                            'end_at' => $num === $lastkey ?
                                $inputEndTime :
                                Carbon::parse($hasStoreOpeningHours[$num]->end_at)->format('H:i'),
                        ];
                    }
                }
            }

            // 同じ曜日、時間帯の重複を確認する処理
            $is_duplicate = false;
            if (!is_null($opens)) {
                foreach ($opens as $i => $open) {
                    if (count($open) > 1) { // 同じ曜日に「1」が2つ以上ある場合、比較対象になる
                        $storeOpeningHours1 = $open;
                        $storeOpeningHours2 = $open;
                        foreach ($storeOpeningHours1 as $key1 => $storeOpeningHour1) {
                            $start_at1 = $storeOpeningHour1['start_at'];
                            $end_at1 = $storeOpeningHour1['end_at'];
                            foreach ($storeOpeningHours2 as $key2 => $storeOpeningHour2) {
                                $start_at2 = $storeOpeningHour2['start_at'];
                                $end_at2 = $storeOpeningHour2['end_at'];
                                if ($key1 === $key2) {
                                    continue;
                                }
                                if (DateTimeDuplicate::isTimeDuplicate($start_at1, $end_at1, $start_at2, $end_at2)) {
                                    $is_duplicate = true;
                                    break 2;
                                }
                                // 比較後の削除
                                unset($storeOpeningHours1[$key1]);
                            }
                        }
                    }
                }
            }

            $error = Validator::make($request->all(), $rules);
            $error->after(function (\Illuminate\Validation\Validator $validator) use ($checkedWeek, $is_duplicate, $request) {
                // 営業曜日にチェックが入っていない場合
                if ($checkedWeek === '00000000') {
                    $validator->errors()->add($checkedWeek, '営業曜日は、必ず一つ以上チェックしてください');
                }
                // 曜日、時間帯が重複した場合のバリデーション
                if ($is_duplicate) {
                    $validator->errors()->add($checkedWeek, '営業曜日または、営業時間の設定が重複しています');
                }

                // ラストオーダーは営業開始時間と終了時間の間で設定
                if ($request->input('last_order_time')) {
                    $lastOrderTime = new Carbon($request->input('last_order_time'));
                    $start = new Carbon($request->input('start_at'));
                    $end = new Carbon($request->input('end_at'));

                    if (!$lastOrderTime->between($start, $end)) {
                        $validator->errors()->add('last_order_time', 'ラストオーダーは営業開始時間と終了時間の間で設定してください。');
                    }
                }
            });

            if ($error->fails()) {
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {
                \DB::beginTransaction();
                // データ追加
                OpeningHour::create([
                    'store_id' => $request->input('store_id'),
                    'opening_hour_cd' => $request->input('opening_hour_cd'),
                    'week' => $checkedWeek,
                    'start_at' => $inputStartTime,
                    'end_at' => $inputEndTime,
                    'last_order_time' => $request->input('last_order_time'),
                ]);
                \DB::commit();
            } catch (\Exception $e) {
                report($e);
                \DB::rollback();

                return response()->json([
                    'error' => [sprintf('「%s」の営業時間を追加できませんでした。', $request->input('store_name'))],
                    'url' => route('admin.store.open.editForm', ['id' => $request->input('store_id')]),
                ]);
            }

            return response()->json([
                'success' => sprintf('「%s」の営業時間を追加しました。', $request->input('store_name')),
                'url' => route('admin.store.open.editForm', ['id' => $request->input('store_id')]),
            ]);
        }
    }

    /**
     * 管理画面 - 店舗営業時間(削除).
     *
     * @param int id
     * @param int openingHour_id
     *
     * @return Redirector
     */
    public function delete(int $id, int $openingHour_id)
    {
        $store = Store::findOrFail($id);
        $this->authorize('store', $store); // ポリシーによる、個別に制御

        try {
            $openingHour = OpeningHour::findOrFail($openingHour_id);
            $openingHour->delete();

            return response()->json(['result' => 'ok']);
        } catch (\Exception $e) {
            return false;
        }
    }
}
