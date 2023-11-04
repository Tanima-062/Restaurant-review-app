<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MenuPriceRequest;
use App\Libs\DateTimeDuplicate;
use App\Models\Menu;
use App\Models\Price;
use DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Validator;
use View;

class MenuPriceController extends Controller
{
    /**
     * 管理画面 - メニュー料金(追加画面).　（※追加方法の仕様変更変更に伴い、本関数は未使用）
     *
     * @param int $id
     *
     * @return View
     */
    public function addForm($id)
    {
        $menuPrices = Price::menuId($id)->orderBy('id', 'asc');

        return view(
            'admin.Menu.Price.add',
            [
                'menu' => Menu::find($id),
                'menuPriceExists' => $menuPrices->exists(),
                'menuPrices' => $menuPrices->get()->toArray(),
                'menuPriceCodes' => config('const.menuPrices.price_cd'),
            ]
        );
    }

    /**
     * 管理画面 - メニュー料金(追加).　（※追加方法の仕様変更変更に伴い、本関数は未使用）
     *
     * @param Request request
     *
     * @return Redirector
     */
    public function add(Request $request)
    {
        if ($request->ajax()) {
            $rules = [
                'menu.*.price_cd' => 'required',
                'menu.*.price_start_date' => 'required|date',
                'menu.*.price_end_date' => 'required|date|after_or_equal:menu.*.price_start_date',
                'menu.*.price' => 'required|regex:/^[0-9]{1,8}+$/',
            ];

            $menus = $request->input('menu');
            $is_duplicate = false;

            // 日付重複の確認用
            $menus2 = $menus;
            $menus3 = $menus;

            // DBから日付データの取得
            $hasMenuPrices = Price::menuId($request->menu_id)
                    ->select('start_date', 'end_date')
                    ->orderBy('id', 'asc')->get();

            foreach ($menus2 as $key2 => $value2) {
                $price_start1 = $menus2[$key2]['price_start_date'];
                $price_end1 = $menus2[$key2]['price_end_date'];

                // DBとの日付重複チェック
                foreach ($hasMenuPrices as $hasKey => $hasMenuPrice) {
                    $hasPriceStartDate = $hasMenuPrices[$hasKey]['start_date'];
                    $hasPriceEndDate = $hasMenuPrices[$hasKey]['end_date'];

                    if ($key2 == $hasKey) {
                        continue;
                    }
                    if (DateTimeDuplicate::isDuplicatePeriod($price_start1, $price_end1, $hasPriceStartDate, $hasPriceEndDate)) {
                        $is_duplicate = true;
                        break 2;
                    }
                }

                // Request同士の日付重複チェック
                foreach ($menus3 as $key3 => $value3) {
                    $price_start2 = $menus3[$key3]['price_start_date'];
                    $price_end2 = $menus3[$key3]['price_end_date'];

                    if ($key2 == $key3) {
                        continue;
                    }
                    if (DateTimeDuplicate::isDuplicatePeriod($price_start1, $price_end1, $price_start2, $price_end2)) {
                        $is_duplicate = true;
                        break 2;
                    }
                }

                // 比較後の削除
                unset($menus2[$key2]);
            }

            $error = Validator::make($request->all(), $rules);
            $error->after(function (\Illuminate\Validation\Validator $validator) use ($is_duplicate) {
                // 日付重複した場合のバリデーション
                if ($is_duplicate) {
                    $validator->errors()->add('menu.*.price_start_date', 'メニュー料金の日付期間が重複しています。');
                }
            });

            if ($error->fails()) {
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {
                foreach ($menus as $menuPrice) {
                    // データ追加
                    Price::create([
                        'menu_id' => $request->menu_id,
                        'price_cd' => $menuPrice['price_cd'],
                        'start_date' => $menuPrice['price_start_date'],
                        'end_date' => $menuPrice['price_end_date'],
                        'price' => str_replace(',', '', $menuPrice['price']),
                    ]);
                }
            } catch (\Exception $e) {
                report($e);
                DB::rollback();
            }

            return response()->json([
                'success' => sprintf('「%s」の料金を追加しました。', $request->input('menu_name')),
                'url' => route('admin.menu.price.editForm', ['id' => $request->input('menu_id')]),
            ]);
        }
    }

    /**
     * 管理画面 - メニュー料金(編集画面).
     *
     * @param int $id
     *
     * @return View
     */
    public function editForm($id)
    {
        if (isset(parse_url(url()->previous())['query']) && preg_match('/^page=[0-9]*/', parse_url(url()->previous())['query'])) {
            session(['menuPriceRedirectTo' => url()->previous()]);
        }
        
        $menu = Menu::find($id);
        $this->authorize('menu', $menu); //  ポリシーによる、個別に制御
        $menuPrices = Price::menuId($id)->orderBy('id', 'asc');

        return view(
            'admin.Menu.Price.edit',
            [
                'menu' => $menu,
                'menuPriceExists' => $menuPrices->exists(),
                'menuPrices' => $menuPrices->get()->toArray(),
                'menuPriceCodes' => config('const.menuPrices.price_cd'),
            ]
        );
    }

    /**
     * 管理画面 - メニュー料金(更新).
     *
     * @param MenuPriceRequest request
     * @param int id
     *
     * @return Redirector
     */
    public function edit(MenuPriceRequest $request, int $id)
    {
        $menus = $request->input('menu');

        // 日付重複の確認用
        $menus2 = $menus;
        $menus3 = $menus;
        $is_duplicate = false;

        // 日付重複の確認処理
        foreach ($menus2 as $key2 => $value2) {
            $price_start1 = $value2['price_start_date'];
            $price_end1 = $value2['price_end_date'];

            foreach ($menus3 as $key3 => $value3) {
                $price_start2 = $value3['price_start_date'];
                $price_end2 = $value3['price_end_date'];

                if ($key2 == $key3) {
                    continue;
                }
                if (DateTimeDuplicate::isDuplicatePeriod($price_start1, $price_end1, $price_start2, $price_end2)) {
                    $is_duplicate = true;
                    break 2;
                }
            }
            // 比較後の削除
            unset($menus2[$key2]);
        }

        $error = Validator::make($request->all(), $request->rules());
        $error->after(function (\Illuminate\Validation\Validator $validator) use ($is_duplicate) {
            // 日付が重複した場合のバリデーション
            if ($is_duplicate) {
                $validator->errors()->add('menu.*.price_start_date', 'メニュー料金の日付期間が重複しています。');
            }
        })->validate();

        try {
            \DB::beginTransaction();
            foreach ($menus as $menuPrice) {
                if (!empty($menuPrice['price_id'])) {
                    // データ更新
                    Price::withoutGlobalScopes()
                        ->where('id', $menuPrice['price_id'])
                        ->update([
                            'price_cd' => $menuPrice['price_cd'],
                            'start_date' => $menuPrice['price_start_date'],
                            'end_date' => $menuPrice['price_end_date'],
                            'price' => str_replace(',', '', $menuPrice['price']),
                        ]);
                } else {
                    // 新規追加
                    Price::create([
                        'menu_id' => $request->menu_id,
                        'price_cd' => $menuPrice['price_cd'],
                        'start_date' => $menuPrice['price_start_date'],
                        'end_date' => $menuPrice['price_end_date'],
                        'price' => str_replace(',', '', $menuPrice['price']),
                    ]);
                }
            }
            \DB::commit();
        } catch (\Exception $e) {
            report($e);
            DB::rollback();

            return redirect(url()->previous())->with(
                'custom_error',
                sprintf('「%s」の料金を保存できませんでした。', $request->input('menu_name'))
            );
        }

        return redirect(url()->previous())->with(
            'message',
            sprintf('「%s」の料金を保存しました。', $request->input('menu_name'))
        );
    }

    /**
     * 管理画面 - メニュー料金(削除).
     *
     * @param int id
     * @param int price_id
     *
     * @return RedirectResponse
     */
    public function delete(int $id, int $price_id)
    {
        try {
            $menu = Menu::find($id);
            $this->authorize('menu', $menu); //  ポリシーによる、個別に制御
            $menuPrice = Price::findOrFail($price_id);
            $menuPrice->delete();

            return response()->json(['result' => 'ok']);
        } catch (\Exception $e) {
            return false;
        }
    }
}
