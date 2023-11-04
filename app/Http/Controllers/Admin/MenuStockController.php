<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\ReservationMenu;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Validator;
use View;

class MenuStockController extends Controller
{
    /**
     * 管理画面 - メニュー在庫(一覧画面)
     *
     * @param Request $request
     * @param int $id
     * @return View
     */
    public function index(Request $request, int $id)
    {
        if (isset(parse_url(url()->previous())['query']) && preg_match('/^page=[0-9]*/', parse_url(url()->previous())['query'])) {
            session(['menuStockRedirectTo' => url()->previous()]);
        }

        $menu = Menu::find($id);
        $this->authorize('menu', $menu); //  ポリシーによる、個別に制御

        // レストランメニューのURLを直叩きされた場合にリダイレクトさせる（レストランメニューには在庫を登録させない）
        if ($this->isRestaurantMenu($menu->app_cd) === true){
            return redirect(url()->previous())->with('custom_error', sprintf('レストランメニューである「%s」は、在庫を設定することはできません。　メニューID：%s', $menu->name, $id));
        }

        $getStock = Stock::menuId($id)->select([
                    'id',
                    'stock_number as title', // Fullcalendar用にカラム名を変更
                    'date as start', // Fullcalendar用にカラム名を変更
                    'menu_id'
                ])->get();

        if ($request->ajax()) {
            // Stock
            $arrayStock = $getStock->toArray();
            $stockLabel = '在庫：';
            $stockColor = ['color' => '#e3f4fc'];
            $stock = array_map(function ($getStock) use ($stockLabel, $stockColor) {
                $title = $stockLabel . $getStock['title'];
                $stockTitle = ['title' => $title];

                return array_merge($getStock, $stockTitle, $stockColor);
            }, $arrayStock);
            // Reservation
            $reservationMenus = ReservationMenu::with('reservation')
                ->where('menu_id', $id)->get();
            if (! $reservationMenus->isEmpty()) {
                // pick_up_date 取得 && 日付フォーマット
                $pickUpDate = [];
                foreach ($reservationMenus as $reservationMenu) {
                    $day = Carbon::createFromFormat('Y-m-d H:i:s',
                        optional($reservationMenu->reservation)->pick_up_datetime)->format('Y-m-d');
                    if (isset($pickUpDate[$day])) {
                        $pickUpDate[$day] += $reservationMenu->count;
                    } else {
                        $pickUpDate[$day] = $reservationMenu->count;
                    }
                }
                $arrayReservation = [];
                // fullCaledar用に配列を加工
                foreach ($pickUpDate as $date => $numberOfReservation) {
                    // 個数を算出
                    $arrayReservation[] = [
                        'title' => $numberOfReservation,
                        'start' => $date
                    ];
                }
                $reservationLabel = '予約：';
                $reservationAddColumn = ['color' => '#fae9e8', 'menu_id' => $id];
                $Reservation = array_map(function ($getReservation) use ($reservationLabel, $reservationAddColumn) {
                    $title = $reservationLabel . $getReservation['title'];
                    $reservationTitle = ['title' => $title];

                    return array_merge($getReservation, $reservationTitle, $reservationAddColumn);
                }, $arrayReservation);
                // Stock + Reservation
                $data = collect(array_merge($stock, $Reservation));
            } else {
                // Stock
                $data = collect(array_merge($stock));
            }

            return response()->json($data);
        }

        return view('admin.Menu.Stock.index', compact('getStock'), ['menu' => $menu]);
    }

    /**
     * 管理画面 - メニュー在庫(追加)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function add(Request $request)
    {
        if ($request->ajax()) {
            try {
                \DB::beginTransaction();
                $rules = ['add_stock_number' => 'required|integer|digits_between:1, 4'];
                $error = Validator::make($request->all(), $rules);
                if ($error->fails()) {
                    return response()->json([
                    'error' => $error->errors()->all(),
                ]);
                }
                $createArr = [
                'stock_number' => $request->add_stock_number,
                'date' => $request->date,
                'menu_id' => $request->menu_id,
            ];
                Stock::create($createArr);
                \DB::commit();
            } catch (\Throwable $e) {
                report($e);
                \DB::rollBack();

                return response()->json([
                    'error' => [sprintf('「%s」の在庫を追加できませんでした。', $request->menu_name)],
                ]);
            }

            return response()->json([
                'success' => sprintf('「%s」の在庫を追加しました。', $request->menu_name)
            ]);
        }
    }

    /**
     * 管理画面 - メニュー在庫(更新)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function edit(Request $request)
    {
        if ($request->ajax()) {
            try {
                \DB::beginTransaction();
                $rules = ['stock_number' => 'required|integer|digits_between:1, 4'];
                $error = Validator::make($request->all(), $rules);
                if ($error->fails()) {
                    return response()->json([
                    'error' => $error->errors()->all(),
                ]);
                }
                $updateArr = [
                'stock_number' => $request->stock_number,
                'date' => $request->stock_date,
            ];
                Stock::where('id', $request->stock_id)->update($updateArr);
                \DB::commit();
            } catch (\Throwable $e) {
                report($e);
                \DB::rollBack();

                return response()->json([
                    'error' => [sprintf('「%s」の在庫を更新できませんでした。', $request->menu_name)],
                ]);
            }

            return response()->json([
                'success'  => sprintf('「%s」の在庫を更新しました。', $request->menu_name)
            ]);
        }
    }

    /**
     * 管理画面 - メニュー在庫(一括更新)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request)
    {
        $error = Validator::make($request->all(), [
            'stock_number_all' => 'required|integer|digits_between:1, 4',
        ]);
        if ($error->fails()) {
            return response()->json([
                'error' => $error->errors()->all()
            ]);
        }
        if ($request->ajax()) {
            try {
                \DB::beginTransaction();
                $date = Carbon::parse($request->year.'-'.$request->month);
                $start = $date->firstOfMonth()->toDateString();
                $end = $date->lastOfMonth()->toDateString();
                for ($i = $start; $i <= $end; $i = date('Y-m-d', strtotime($i.'+1 day'))) {
                    Stock::where('menu_id', $request->menu_id)->updateOrCreate(
                    ['menu_id' => $request->menu_id, 'date' => $i],
                    [
                        'stock_number' => $request->stock_number_all,
                        'date' => $i,
                        'menu_id' => $request->menu_id,
                    ]);
                }
                \DB::commit();
            } catch (\Throwable $e) {
                report($e);
                \DB::rollBack();

                return response()->json([
                    'error' => [sprintf('「%s」の在庫を更新できませんでした。', $request->menu_name)],
                ]);
            }

            return response()->json([
                'success'  => sprintf('「%s」の在庫を更新しました。', $request->menu_name)
            ]);
        }
    }

    /**
     * 管理画面 - メニュー在庫(データ取得用)
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getData(int $id)
    {
        $data = Stock::find($id);

        return response()->json($data);
    }

    /**
     * 管理画面 - メニュー在庫(削除)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Request $request)
    {
        try {
            $event = Stock::where('id', $request->id)->delete();

            return response()->json($event);
        } catch (\Exception $e) {
            return response()->json([
                'error' => ['削除できませんでした。'],
            ]);
        }
    }

    /**
     * レストランメニューかの判定
     * 
     * @param String $appCd
     * 
     * @return Boolean
     */
    public function isRestaurantMenu($appCd)
    {
        if ($appCd === key(config('code.appCd.rs'))) {
            return true;
        } else {
            return false;
        }
    }
}
