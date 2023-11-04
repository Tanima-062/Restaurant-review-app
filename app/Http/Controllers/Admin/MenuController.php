<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MenuAddRequest;
use App\Http\Requests\Admin\MenuEditRequest;
use App\Http\Requests\Admin\MenuSearchRequest;
use App\Http\Requests\Admin\MenuPublishRequest;
use App\Http\Requests\Admin\MenuPrivateRequest;
use App\Models\Menu;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Redirector;
use View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    protected $redirectTo = 'admin/menu/';

    public $searchPageMaxLimit = 30;  // 1ページあたりの最大件数

    /**
     * 管理画面 - メニュー一覧
     *
     * @param MenuSearchRequest $request
     * @return View
     */
    public function index(MenuSearchRequest $request)
    {
        $menus = Menu::with(
            'store',
            'menuPrices',
            'genres'
        )->adminSearchFilter($request->validated())->sortable()->paginate($this->searchPageMaxLimit);

        return view('admin.Menu.index', [
            'menus' => $menus,
        ]);
    }

    /**
     * 管理画面 - メニュー(編集)
     *
     * @param int $id
     * @return View
     */
    public function editForm(Store $storeModel, int $id)
    {
        $menuConstants = config('const.menu');
        $menu = Menu::find($id);
        $this->authorize('menu', $menu); //  ポリシーによる、個別に制御

        // 最低注文時間の計算
        list($hours, $minutes) = $storeModel->calcMinToHour($menu['lower_orders_time']);
        $menu['lower_orders_time_hour'] = $hours;
        $menu['lower_orders_time_minute'] = $minutes;

        $stores = Store::query()->orderBy('id')->get();

        return view('admin.Menu.edit', [
            'menu' => $menu,
            'stores' => $stores,
            'appCd' => config('code.appCd'),
            'freeDrinks' => $menuConstants['free_drinks'],
            'providedDayOfWeeks' => $menuConstants['provided_day_of_week'],
        ]);
    }

    /**
     * 管理画面 - メニュー(更新)
     *
     * @param MenuEditRequest $request
     * @param int $id
     * @return Redirector
     */
    public function edit(MenuEditRequest $request, Store $storeModel, int $id)
    {
        try {
            \DB::beginTransaction();
            $menu = Menu::find($id);
            $this->authorize('menu', $menu); //  ポリシーによる、個別に制御
            
            // 最低注文時間計算
            $request['lower_orders_time'] = $storeModel->calcHourToMin($request['lower_orders_time_hour'], $request['lower_orders_time_minute']);

            // 公開・非公開フラグ変換
            $published = (!empty($request->input('published'))) ? 1 : 0;

            // 表示・非表示フラグ変換
            $buffet_lp_published = (!empty($request->input('buffet_lp_published'))) ? 1 : 0;

            // 公開・非公開変更時は最終更新ユーザーを更新
            if ($menu->published !== $published) {
                $menu->staff_id = (!empty((Auth::user())->id)) ? (Auth::user())->id : null;
            }

            $menu->update([
                'name' => $request->input('menu_name'),
                'store_id' => $request->input('store_name', (\Auth::user())->store_id),
                'app_cd' => $request->input('app_cd'),
                'number_of_orders_same_time' => $request->input('number_of_orders_same_time'),
                'number_of_course' => $request->input('number_of_course'),
                'free_drinks' => $request->input('free_drinks'),
                'provided_time' => $request->input('provided_time'),
                'available_number_of_lower_limit' => $request->input('available_number_of_lower_limit'),
                'available_number_of_upper_limit' => $request->input('available_number_of_upper_limit'),
                'description' => $request->input('menu_description'),
                'plan' => $request->input('plan'),
                'notes' => $request->input('menu_notes'),
                'sales_lunch_start_time' => $request->input('sales_lunch_start_time'),
                'sales_lunch_end_time' => $request->input('sales_lunch_end_time'),
                'sales_dinner_start_time' => $request->input('sales_dinner_start_time'),
                'sales_dinner_end_time' => $request->input('sales_dinner_end_time'),
                'provided_day_of_week' => implode($request->input('provided_day_of_week'), ''),
                'lower_orders_time' => $request['lower_orders_time'],
                'remarks' => $request->input('remarks'),
                'published' => $published,
                'buffet_lp_published' => $buffet_lp_published,
            ]);
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollBack();

            return redirect(route('admin.menu'))
            ->with('custom_error', sprintf('メニュー「%s」を更新できませんでした', $request->menu_name));
        }

        return redirect(route('admin.menu'))
        ->with('message', sprintf('メニュー「%s」を更新しました', $menu->name));
    }

    /**
     * 管理画面 - メニュー(追加画面)
     *
     * @return View
     */
    public function addForm()
    {
        $menuConstants = config('const.menu');
        $stores = Store::query()->distinct()->orderBy('id')->get();

        return view('admin.Menu.add', [
            'stores' => $stores,
            'appCd' => config('code.appCd'),
            'freeDrinks' => $menuConstants['free_drinks'],
            'providedDayOfWeeks' => $menuConstants['provided_day_of_week'],
        ]);
    }

    /**
     * 管理画面 - メニュー(追加)
     *
     * @param MenuAddRequest $request
     * @return Redirector
     */
    public function add(MenuAddRequest $request, Store $storeModel)
    {
        try {
            \DB::beginTransaction();
            // 最低注文時間計算
            $request['lower_orders_time'] = $storeModel->calcHourToMin($request['lower_orders_time_hour'], $request['lower_orders_time_minute']);

            // 表示・非表示フラグ変換
            $buffet_lp_published = (!empty($request->input('buffet_lp_published'))) ? 1 : 0;

            Menu::firstOrCreate([
                'name' => $request->input('menu_name'),
                'store_id' => $request->input('store_name', (\Auth::user())->store_id),
                'app_cd' => $request->input('app_cd'),
                'number_of_orders_same_time' => $request->input('number_of_orders_same_time'),
                'number_of_course' => $request->input('number_of_course'),
                'free_drinks' => $request->input('free_drinks'),
                'provided_time' => $request->input('provided_time'),
                'available_number_of_lower_limit' => $request->input('available_number_of_lower_limit'),
                'available_number_of_upper_limit' => $request->input('available_number_of_upper_limit'),
                'description' => $request->input('menu_description'),
                'plan' => $request->input('plan'),
                'notes' => $request->input('menu_notes'),
                'sales_lunch_start_time' => $request->input('sales_lunch_start_time'),
                'sales_lunch_end_time' => $request->input('sales_lunch_end_time'),
                'sales_dinner_start_time' => $request->input('sales_dinner_start_time'),
                'sales_dinner_end_time' => $request->input('sales_dinner_end_time'),
                'provided_day_of_week' => implode($request->input('provided_day_of_week'), ''),
                'lower_orders_time' => $request['lower_orders_time'],
                'remarks' => $request->input('remarks'),
                'published' => $request->input('published'),
                'buffet_lp_published' => $buffet_lp_published,
                'staff_id' => Auth::user()->id,
            ]);
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollBack();

            return redirect(route('admin.menu'))
            ->with('custom_error', sprintf('メニュー「%s」を作成できませんでした', $request->input('menu_name')));
        }

        // 全件検索して、最後のページ数を取得
        $pageNumber = 1;
        $menuLists = Menu::query()->adminSearchFilter([])->count();
        if ($menuLists > 0) {
            $pageNumber = ceil($menuLists / $this->searchPageMaxLimit);
        }

        return redirect(route('admin.menu', ['page' => $pageNumber]))
        ->with('message', sprintf("メニュー「%s」を作成しました", $request->input('menu_name')));
    }

    /**
     * 管理画面 - メニュー(削除)
     *
     * @param int $id
     * @return JsonResponse
     */
    public function delete(int $id)
    {
        try {
            $menu = Menu::findOrFail($id);
            $this->authorize('menu', $menu); //  ポリシーによる、個別に制御
            $menu->delete();

            return response()->json(['result' => 'ok']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 管理画面 - メニュー公開
     */
    public function setPublish(MenuPublishRequest $request, Int $id)
    {
        $menu = Menu::find($id);
        $menu->published = $request->published;
        $menu->staff_id = (!empty((Auth::user())->id)) ? (Auth::user())->id : null;
        $menu->save();

        return redirect($this->redirectTo)
        ->with('message', sprintf('メニュー「%s」を公開しました。', $menu->name));
    }

    /**
     * 管理画面 - メニュー非公開
     */
    public function setPrivate(MenuPrivateRequest $request, Int $id)
    {
        $menu = Menu::find($id);
        $menu->published = $request->published;
        $menu->staff_id = (!empty((Auth::user())->id)) ? (Auth::user())->id : null;
        $menu->save();

        return redirect($this->redirectTo)
        ->with('message', sprintf('メニュー「%s」を非公開にしました。', $menu->name));
    }
}
