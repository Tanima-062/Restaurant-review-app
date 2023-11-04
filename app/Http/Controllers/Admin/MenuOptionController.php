<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MenuOptionOkonomiAddRequest;
use App\Http\Requests\Admin\MenuOptionOkonomiEditRequest;
use App\Http\Requests\Admin\MenuOptionToppingEditRequest;
use App\Models\Menu;
use App\Models\Option;
use App\Rules\MbStringCheck;
use DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Validator;
use View;

class MenuOptionController extends Controller
{
    /**
     * 管理画面 - メニューオプション(お好み/トッピング 一覧画面).
     *
     * @param int id
     *
     * @return View
     */
    public function index(int $id)
    {
        if (isset(parse_url(url()->previous())['query']) && preg_match('/^page=[0-9]*/', parse_url(url()->previous())['query'])) {
            session(['menuOptionRedirectTo' => url()->previous()]);
        }

        $menuOptionConst = config('const.menuOptions');
        $menu = Menu::find($id);
        $this->authorize('menu', $menu); //  ポリシーによる、個別に制御
        $menuOptions = Option::menuId($id);
        $menuOptionOkonomis = Option::menuId($id)->where('option_cd', 'OKONOMI');
        $menuOptionToppings = Option::menuId($id)->where('option_cd', 'TOPPING');

        // keyword_id同士での並び替え
        $getOptionOkonomis = $menuOptionOkonomis->sortable()->get()->groupBy('keyword_id');

        return view(
            'admin.Menu.Option.index',
            [
                'menu' => $menu,
                'menuOptionExists' => $menuOptions->exists(),
                'menuOptionOkonomiExists' => $menuOptionOkonomis->exists(),
                'menuOptionToppingExists' => $menuOptionToppings->exists(),
                'menuOptionOkonomis' => $getOptionOkonomis,
                'contentsOptionOkonomis' => $menuOptionOkonomis->select('contents')->get()->toArray(),
                'menuOptionToppings' => $menuOptionToppings->sortable()->get()->toArray(),
                'menuOptionRequired' => $menuOptionConst['required'],
            ]
        );
    }

    /**
     * 管理画面 - メニューオプション(項目追加 画面).
     *
     * @param int $id
     *
     * @return View
     */
    public function okonomiKeywordAddForm($id)
    {
        $menuOptionConst = config('const.menuOptions');
        $menuOptions = Option::menuId($id)->orderBy('id', 'asc');

        return view(
            'admin.Menu.Option.Okonomi.addKeyword',
            [
                'menu' => Menu::find($id),
                'menuOptionExists' => $menuOptions->exists(),
                'menuOptions' => $menuOptions->get()->toArray(),
                'menuOptionRequired' => $menuOptionConst['required'],
            ]
        );
    }

    /**
     * 管理画面 - メニューオプション(項目追加).
     *
     * @param MenuOptionOkonomiAddRequest request
     *
     * @return RedirectResponse
     */
    public function okonomiKeywordAdd(MenuOptionOkonomiAddRequest $request)
    {
        try {
            \DB::beginTransaction();
            $keywordIdMax = Option::where('menu_id', $request->input('menu_id'))
            ->select('keyword_id')
            ->max('keyword_id');

            // データ追加
            Option::create([
                'option_cd' => $request->input('option_cd'),
                'required' => $request->input('required'),
                'keyword_id' => $keywordIdMax + 1,
                'keyword' => $request->input('keyword'),
                'contents_id' => $request->input('contents_id'),
                'contents' => $request->input('contents'),
                'price' => $request->input('price'),
                'menu_id' => $request->input('menu_id'),
            ]);
            \DB::commit();
        } catch (\Exception $e) {
            report($e);
            DB::rollback();

            return redirect(route('admin.menu.option', ['id' => $request->input('menu_id')]))->with(
                'custom_error',
                sprintf('「%s」オプションの項目を追加できませんでした。', $request->input('menu_name'))
            );
        }

        return redirect(route('admin.menu.option', ['id' => $request->input('menu_id')]))->with(
            'message',
            sprintf('「%s」オプションの項目を追加しました。', $request->input('menu_name'))
        );
    }

    /**
     * 管理画面 - メニューオプション(内容追加).
     *
     * @param Request request
     * @param int id
     *
     * @return RedirectResponse
     */
    public function okonomiContentsAdd(Request $request, int $id)
    {
        if ($request->ajax()) {
            $rules = [
                'menuOption.contents' => 'required|string|max:200',
                'menuOption.price' => 'required|digits_between:1,8|numeric',
            ];

            $menus = $request->input('menuOption');
            $menuOption = Option::findOrFail($id);
            $contentsIdMax = Option::where('keyword_id', $menuOption->keyword_id)
                ->select('contents_id')
                ->max('contents_id');
            $error = Validator::make($request->all(), $rules);

            if ($error->fails()) {
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {
                \DB::beginTransaction();
                if (!isset($contentsIdMax)) {
                    // データ更新
                    Option::where('id', $menuOption->id)->update([
                        'contents_id' => 1,
                        'contents' => $menus['contents'],
                        'price' => $menus['price'],
                    ]);
                } else {
                    // データ追加
                    $contentsId = $contentsIdMax + 1;
                    Option::create([
                        'option_cd' => $menuOption->option_cd,
                        'required' => $menuOption->required,
                        'keyword_id' => $menuOption->keyword_id,
                        'keyword' => $menuOption->keyword,
                        'contents_id' => $contentsId,
                        'contents' => $menus['contents'],
                        'price' => $menus['price'],
                        'menu_id' => $menus['menu_id'],
                    ]);
                }
                \DB::commit();
            } catch (\Exception $e) {
                report($e);
                DB::rollback();

                return response()->json([
                    'error' => sprintf('「%s」オプションの内容を保存できませんでした。', $menuOption->keyword),
                ]);
            }

            return response()->json([
                'success' => sprintf('「%s」オプションの内容を保存しました。', $menuOption->keyword),
            ]);
        }
    }

    /**
     * 管理画面 - メニューオプション(お好み 編集画面).
     *
     * @param int menu_id
     * @param Request request
     *
     * @return View
     */
    public function okonomiEditForm(int $menu_id, Request $request)
    {
        $keyword_id = $request->keyword_id;
        $menuOptionConst = config('const.menuOptions');
        $menuOptions = Option::menuId($menu_id)->where('keyword_id', $request->keyword_id)->orderBy('id', 'asc');

        return view(
            'admin.Menu.Option.Okonomi.edit', compact('keyword_id'),
            [
                'menu' => Menu::find($menu_id),
                'menuOptionExists' => $menuOptions->exists(),
                'menuOptions' => $menuOptions->get()->toArray(),
                'menuOptionRequired' => $menuOptionConst['required'],
            ]
        );
    }

    /**
     * 管理画面 - メニューオプション(お好み 更新).
     *
     * @param MenuOptionOkonomiEditRequest request
     *
     * @return Redirector
     */
    public function okonomiEdit(MenuOptionOkonomiEditRequest $request)
    {
        try {
            \DB::beginTransaction();

            $menuOptions = $request->input('menuOption');
            $menuOkonomi = $request->input('menuOkonomi');
            $hasMenuOptions = Option::query()
            ->where('menu_id', $request->menu_id)
            ->where('keyword_id', $request->keyword_id)
            ->get();
            // Okonomi データ更新
            foreach ($hasMenuOptions as $hasMenuOption) {
                $hasMenuOption->fill([
                    'required' => $menuOkonomi[0]['required'],
                    'keyword' => $menuOkonomi[0]['keyword'],
                ])->save();
            }

            foreach ($menuOptions as $menuOption) {
                Option::withoutGlobalScopes()
                        ->where('id', $menuOption['id'])
                        ->update([
                            'contents' => $menuOption['contents'],
                            'price' => str_replace(',', '', $menuOption['price']),
                        ]);
            }
            \DB::commit();
        } catch (\Exception $e) {
            report($e);
            DB::rollback();

            return redirect(route('admin.menu.option', ['id' => $request->input('menu_id')]))->with(
                'custom_error',
                sprintf('「%s」のお好みを更新できませんでした。', $request->input('menu_name'))
            );
        }

        return redirect(route('admin.menu.option', ['id' => $request->input('menu_id')]))->with(
            'message',
            sprintf('「%s」のお好みを更新しました。', $request->input('menu_name'))
        );
    }

    /**
     * 管理画面 - メニューオプション(トッピング 追加画面).
     *
     * @param int id
     *
     * @return RedirectResponse
     */
    public function toppingAddForm(int $id)
    {
        $menuOptionConst = config('const.menuOptions');
        $menuOptions = Option::menuId($id)->orderBy('id', 'asc');
        $menu = Menu::find($id);

        // 利用コードがレストランの場合
        if ($menu->app_cd === key(config('code.appCd.rs'))) {
            return redirect(route('admin.menu.option', ['id' => $menu->id]))->with(
                'message',
                sprintf('利用コードが'.config('code.appCd.rs.RS').'の場合、トッピングを追加することはできません。', $menu->id));
        }

        return view(
            'admin.Menu.Option.Topping.add',
            [
                'menu' => $menu,
                'menuOptionExists' => $menuOptions->exists(),
                'menuOptions' => $menuOptions->get()->toArray(),
                'menuOptionRequired' => $menuOptionConst['required'],
            ]
        );
    }

    /**
     * 管理画面 - メニューオプション(トッピング 追加).
     *
     * @param Request request
     *
     * @return RedirectResponse
     */
    public function toppingAdd(Request $request)
    {
        if ($request->ajax()) {
            $rules = [
                'menuOption.*.contents' => ['required', 'string', new MbStringCheck(config('const.menuOptions.contents.upper'))],
                'menuOption.*.price' => 'required|digits_between:1,8',
            ];

            $menus = $request->input('menuOption');

            $error = Validator::make($request->all(), $rules);

            if ($error->fails()) {
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {
                \DB::beginTransaction();
                foreach ($menus as $menuOption) {
                    $contentsIdMax = Option::where('menu_id', $request->menu_id)
                        ->where('option_cd', 'TOPPING')
                        ->select('contents_id')
                        ->max('contents_id');

                    // データ追加
                    Option::create([
                        'option_cd' => $menuOption['option_cd'],
                        'contents_id' => $contentsIdMax + 1,
                        'contents' => $menuOption['contents'],
                        'price' => str_replace(',', '', $menuOption['price']),
                        'menu_id' => $request->menu_id,
                    ]);
                }
                \DB::commit();
            } catch (\Exception $e) {
                report($e);
                DB::rollback();

                return response()->json([
                    'custom_error' => sprintf('「%s」オプションのトッピングを追加できませんでした。', $request->input('menu_name')),
                    'url' => route('admin.menu.option', ['id' => $request->input('menu_id')]),
                ]);
            }

            return response()->json([
                'success' => sprintf('「%s」オプションのトッピングを追加しました。', $request->input('menu_name')),
                'url' => route('admin.menu.option', ['id' => $request->input('menu_id')]),
            ]);
        }
    }

    /**
     * 管理画面 - メニューオプション(トッピング 更新).
     *
     * @param MenuOptionToppingEditRequest request
     *
     * @return RedirectResponse
     */
    public function toppingEdit(MenuOptionToppingEditRequest $request)
    {
        $menuOptionToppings = $request->input('menuOptionTopping');

        try {
            \DB::beginTransaction();
            // Topping
            foreach ($menuOptionToppings as $menuOptionTopping) {
                // データ更新
                if (!empty($menuOptionTopping['option_id'])) {
                    Option::withoutGlobalScopes()
                        ->where('id', $menuOptionTopping['option_id'])
                        ->update([
                            'contents' => $menuOptionTopping['contents'],
                            'price' => str_replace(',', '', $menuOptionTopping['price']),
                        ]);
                }
            }
            \DB::commit();
        } catch (\Exception $e) {
            report($e);
            DB::rollback();

            return redirect(url()->previous())->with(
                'custom_error',
                sprintf('「%s」のトッピングを更新できませんでした。', $request->input('menu_name'))
            );
        }

        return redirect(url()->previous())->with(
            'message',
            sprintf('「%s」のトッピングを更新しました。', $request->input('menu_name'))
        );
    }

    /**
     * 管理画面 - メニューオプション(お好み/トッピング 削除).
     *
     * @param int id
     * @param int option_id
     *
     * @return RedirectResponse
     */
    public function delete(int $id, int $option_id)
    {
        try {
            $menuOption = Option::findOrFail($option_id);
            $menuOption->delete();

            return response()->json(['result' => 'ok']);
        } catch (\Exception $e) {
            return false;
        }
    }
}
