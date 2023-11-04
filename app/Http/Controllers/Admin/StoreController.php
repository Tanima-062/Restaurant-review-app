<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreAddRequest;
use App\Http\Requests\Admin\StoreEditRequest;
use App\Http\Requests\Admin\StorePrivateRequest;
use App\Http\Requests\Admin\StorePublishRequest;
use App\Http\Requests\Admin\StoreSearchRequest;
use Illuminate\Http\Request;
use App\Models\Area;
use App\Models\Image;
use App\Models\SettlementCompany;
use App\Models\Store;
use App\Services\AreaService;
use DB;
use Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use View;
use Illuminate\Support\Facades\Auth;

class StoreController extends AdminController
{
    protected $redirectTo = 'admin/store/';

    protected $redirectToPrevious; 

    public $searchPageMaxLimit = 30;  // 1ページあたりの最大件数

    public function __construct()
    {
        $this->redirectToPrevious = url()->previous();
    }

    /**
     * 管理画面 - 店舗一覧/基本情報管理.
     *
     * @return View
     */
    public function index(StoreSearchRequest $request)
    {
        $stores = Store::with(
            'settlementCompany',
            'openingHours',
            'staff',
        )->list()->adminSearchFilter($request->validated())->sortable()->paginate($this->searchPageMaxLimit);
        // dd($stores);

        return view('admin.Store.index', [
            'stores' => $stores,
        ]);
    }

    /**
     * 管理画面 - 店舗一覧/基本情報管理(編集).
     *
     * @return View
     */
    public function editForm(int $id, AreaService $areaService, Store $storeModel)
    {
        $storeConstants = config('const.store');

        $store = Store::findOrFail($id);
        $this->authorize('store', $store); // ポリシーによる、個別に制御

        // 最低注文時間の計算
        list($hours, $minutes) = $storeModel->calcMinToHour($store['lower_orders_time']);
        $store['lower_orders_time_hour'] = $hours;
        $store['lower_orders_time_minute'] = $minutes;

        $settlementCompanies = settlementCompany::query()->distinct()->get();
        $areasLevel1 = Area::where('level', 1)->where('published', 1)->get();
        $areasLevel2 = [];
        $parentArea = [];
        if (is_numeric($store->area_id)) {
            $area = Area::find($store->area_id);
            // area_idがNULLの時のエラー回避
            if (!is_null($area)) {
                $tmp = explode('/', $area->path);
                // area_idが存在しているかつLevelが3だった場合の表示制御のための条件演算子
                $parentArea = !isset($tmp[2]) ? Area::where('area_cd', $tmp[1])->first() : null;
                // area_idが存在しない時のエラー回避
                $areasLevel2 = isset($parentArea->area_cd) ? $areaService->getAreaAdmin(['areaCd' => $parentArea->area_cd]) : null;
            }
        }

        return view('admin.Store.edit', [
            'appCd' => config('code.appCd'),
            'store' => $store,
            'settlementCompanies' => $settlementCompanies,
            'areasLevel1' => $areasLevel1,
            'areasLevel2' => $areasLevel2,
            'parentArea' => $parentArea,
            'useFax' => $storeConstants['use_fax'],
            'regularHoliday' => $storeConstants['regular_holiday'],
            'canCard' => $storeConstants['can_card'],
            'cardTypes' => $storeConstants['card_types'],
            'canDigitalMoney' => $storeConstants['can_digital_money'],
            'digitalMoneyTypes' => $storeConstants['digital_money_types'],
            'smokingTypes' => $storeConstants['smoking_types'],
            'canCharter' => $storeConstants['can_charter'],
            'charterTypes' => $storeConstants['charter_types'],
            'hasPrivateRoom' => $storeConstants['has_private_room'],
            'privateRoomTypes' => $storeConstants['private_room_types'],
            'hasParking' => $storeConstants['has_parking'],
            'hasCoinParking' => $storeConstants['has_coin_parking'],
            'budgetLowerLimit' => $storeConstants['budget_lower_limit'],
            'budgetLimit' => $storeConstants['budget_limit'],
            'pickUpTimeInterval' => $storeConstants['pick_up_time_interval'],
        ]);
    }

    /**
     * 管理画面 - 店舗一覧/基本情報管理(更新).
     *
     * @return RedirectResponse|Redirector
     */
    public function edit(StoreEditRequest $request, Store $storeModel, int $id)
    {
        try {
            \DB::beginTransaction();
            $store = Store::with('image', 'takeoutMenus')->find($id);
            $code = $request->input('code');

            $changeImagesOrNot = $store->code !== $code ? true : false; // 店舗コードが変更された場合に、画像URLを変更するための真偽値

            $this->authorize('store', $store); // ポリシーによる、個別に制御
            
            // 最低注文時間計算
            $request['lower_orders_time'] = $storeModel->calcHourToMin($request['lower_orders_time_hour'], $request['lower_orders_time_minute']);

            $data = $request->except(
                    '_token',
                    'redirect_to',
                    Gate::check('client-only') ? 'settlement_company_id' : '',
                    'regular_holiday',
                    'card_types',
                    'digital_money_types',
                    'private_room_types',
                    Gate::check('client-only') ? 'code' : '',
                    'lower_orders_time_hour',
                    'lower_orders_time_minute',
                ) + [
                    'regular_holiday' => $this->formatRegularHoliday($request->input('regular_holiday')),
                    'card_types' => $request->input('can_card') === '1' ?
                        implode($request->input('card_types'), ',') : $request->input('card_types'),
                    'digital_money_types' => $request->input('can_digital_money') === '1' ?
                        implode($request->input('digital_money_types'), ',') : $request->input('digital_money_types'),
                    'private_room_types' => $request->input('has_private_room') === '1' ?
                        implode($request->input('private_room_types'), ',') : $request->input('private_room_types'),
                ];
            $store->update($data);

            if (Gate::check('inAndOutHouseGeneral-only') && $changeImagesOrNot) { // クライアント管理者、クライアント一般以外 かつ 店舗コードが変更された場合
                // 画像テーブル 店舗画像用urlを更新
                if (!is_null($store->image)) {
                    foreach ($store->image as $getStoreImage) {
                        $oldUrl = $getStoreImage->url;
                        $category = '/store';
                        // 画像ファイルを移動させる処理を呼び出す
                        $newUrl = $store->moveImage($oldUrl, $code, $category);
                        $store->image()->where('id', $getStoreImage->id)->update(['url' => $newUrl]);
                    }
                }

                // 画像テーブル メニュー画像用urlを更新
                if (!is_null($store->takeoutMenus)) {
                    foreach ($store->takeoutMenus as $menu) {
                        $menuImage = Image::where('menu_id', $menu->id)->first();
                        // メニューの画像がある場合
                        if (!is_null($menuImage)) {
                            $oldMenuUrl = $menuImage->url;
                            $category = '/menu';
                            // 画像ファイルを移動させる処理を呼び出す
                            $newUrl = $store->moveImage($oldMenuUrl, $code, $category);
                            $menuImage->update(['url' => $newUrl]);
                        }
                    }
                }
            }
            \DB::commit();
        } catch (\Exception $e) {
            report($e);
            DB::rollback();

            return redirect($request->redirect_to)
            ->with('custom_error', sprintf('店舗情報「%s」を更新できませんでした', $request->name));
        }

        return redirect($request->redirect_to)
            ->with('message', sprintf('店舗情報「%s」を更新しました', $store->name));
    }

    /**
     * 管理画面 - 店舗一覧/基本情報管理(作成).
     *
     * @return View
     */
    public function addForm()
    {
        $storeConstants = config('const.store');
        $settlementCompanies = settlementCompany::query()->distinct()->get();
        $areasLevel1 = Area::where('level', 1)->where('published', 1)->get();

        return view('admin.Store.add', [
            'appCd' => config('code.appCd'),
            'areasLevel1' => $areasLevel1,
            'settlementCompanies' => $settlementCompanies,
            'useFax' => $storeConstants['use_fax'],
            'regularHoliday' => $storeConstants['regular_holiday'],
            'canCard' => $storeConstants['can_card'],
            'cardTypes' => $storeConstants['card_types'],
            'canDigitalMoney' => $storeConstants['can_digital_money'],
            'digitalMoneyTypes' => $storeConstants['digital_money_types'],
            'smokingTypes' => $storeConstants['smoking_types'],
            'canCharter' => $storeConstants['can_charter'],
            'charterTypes' => $storeConstants['charter_types'],
            'hasPrivateRoom' => $storeConstants['has_private_room'],
            'privateRoomTypes' => $storeConstants['private_room_types'],
            'hasParking' => $storeConstants['has_parking'],
            'hasCoinParking' => $storeConstants['has_coin_parking'],
            'budgetLowerLimit' => $storeConstants['budget_lower_limit'],
            'budgetLimit' => $storeConstants['budget_limit'],
            'pickUpTimeInterval' => $storeConstants['pick_up_time_interval'],
        ]);
    }

    /**
     * 管理画面 - 店舗一覧/基本情報管理(追加).
     *
     * @return RedirectResponse|Redirector
     */
    public function add(StoreAddRequest $request, Store $storeModel)
    {
        try {
            \DB::beginTransaction();
            // 最低注文時間計算
            $request['lower_orders_time'] = $storeModel->calcHourToMin($request['lower_orders_time_hour'], $request['lower_orders_time_minute']);

            $data = $request->except(
                    '_token',
                    'redirect_to',
                    'regular_holiday',
                    'card_types',
                    'digital_money_types',
                    'private_room_types',
                    'geourl',
                    'areaLevel1',
                    'lower_orders_time_hour',
                    'lower_orders_time_minute',
                ) + [
                    'regular_holiday' => $this->formatRegularHoliday($request->input('regular_holiday')),
                    'card_types' => $request->input('can_card') === '1' ?
                        implode($request->input('card_types'), ',') : $request->input('card_types'),
                    'digital_money_types' => $request->input('can_digital_money') === '1' ?
                        implode($request->input('digital_money_types'), ',') : $request->input('digital_money_types'),
                    'private_room_types' => $request->input('has_private_room') === '1' ?
                        implode($request->input('private_room_types'), ',') : $request->input('private_room_types'),
                ];

            $data['staff_id'] = (!empty((Auth::user())->id)) ? (Auth::user())->id : null;
            Store::firstOrCreate($data);
            \DB::commit();
        } catch (\Exception $e) {
            report($e);
            \DB::rollback();

            return redirect(route('admin.store'))
            ->with('custom_error', sprintf('店舗「%s」を作成できませんでした', $request->input('name')));
        }


        // 全件検索して、最後のページ数を取得
        $pageNumber = 1;
        $storeLists = Store::query()->count();
        if ($storeLists > 0) {
            $pageNumber = ceil($storeLists / $this->searchPageMaxLimit);
        }

        return redirect(route('admin.store', ['page' => $pageNumber]))
            ->with('message', sprintf('店舗「%s」を作成しました', $request->input('name')));
    }

    /**
     * 管理画面 - 店舗一覧/基本情報管理(削除).
     *
     * @return RedirectResponse|Redirector
     */
    public function delete(int $id)
    {
        $store = Store::findOrFail($id);
        $this->authorize('store', $store); // ポリシーによる、個別に制御

        try {
            $store->delete();

            return response()->json(['result' => 'ok']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 店舗を公開する.
     */
    public function setPublish(StorePublishRequest $request, int $id)
    {
        $store = Store::find($id);
        $store->published = $request->published;
        $store->staff_id = (!empty((Auth::user())->id)) ? (Auth::user())->id : null;
        $store->save();

        return redirect($this->redirectToPrevious)
        ->with('message', sprintf('店舗「%s」を公開しました。', $store->name));
    }

    /**
     * 店舗を非公開にする.
     */
    public function setPrivate(StorePrivateRequest $request, int $id)
    {
        $store = Store::find($id);
        $store->published = $request->published;
        $store->staff_id = (!empty((Auth::user())->id)) ? (Auth::user())->id : null;
        $store->save();

        return redirect($this->redirectToPrevious)
        ->with('message', sprintf('店舗「%s」を非公開にしました。', $store->name));
    }

    /**
     * 画面からpostされた定休日リストをDB登録用に文字列の２進数にする.
     *
     * @param  $regularHolidays
     *
     * @return string
     */
    private function formatRegularHoliday($regularHolidays)
    {
//        \Log::debug($regularHolidays);
//        \Log::debug(print_r($regularHolidays, true));
        // 渡されるのは10のデータだが定休日無しは111111110とするので9桁になるように調整する
        $implode = '';
        foreach (config('const.store.regular_holiday') as $i => $regularHoliday) {
            if ($i == 8 && isset($regularHolidays[$i])) { // 定休日無しのときは別処理
                $implode = '111111110'; // 最後の0は不定休
                break;
            }

            if ($i == 9 && isset($regularHolidays[$i])) { // 不定休の時の処理
                $implode = '1111111110';
                break;
            }

            if ($i == 8) {
                continue;
            }

            if ($i == 9) { // 不定休が意味が違うのでそのまま1が不定休あり、0が不定休なし
                if (isset($regularHolidays[$i])) {
                    $implode .= '1';
                } else {
                    $implode .= '0';
                }
            }

            if (isset($regularHolidays[$i])) { // 定休日無しと不定休以外
                $implode .= '0';
            } else {
                $implode .= '1';
            }
        }

//        \Log::debug('regularHoliday:'.$implode);

        return $implode;
    }
}
