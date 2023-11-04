<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StaffDetailRequest;
use App\Http\Requests\Admin\StaffPasswordRequest;
use App\Http\Requests\Admin\StaffRequest;
use App\Http\Requests\Admin\StaffSearchRequest;
use App\Models\SettlementCompany;
use App\Models\Staff;
use App\Models\StaffAuthority;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class StaffController extends AdminController
{
    protected $redirectTo = 'admin/staff/';
    private $authority;

    public function __construct()
    {
        $this->authority = config('const.staff.authority');
    }

    /**
     * 管理画面 - スタッフ一覧
     *
     * @param StaffSearchRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(StaffSearchRequest $request){
        // パフォーマンスのためEager Loading
        $staffs = Staff::with('staffAuthority')
            ->list()->adminSearchFilter($request->validated(),(Auth::user())->store_id)
            ->sortable()->paginate(30);

        if (Gate::check('inHouseGeneral-higher')) {
            $staffAuthorities = StaffAuthority::all();
        } else {
            $staffAuthorities = StaffAuthority::where('id', '>=', $this->authority['CLIENT_ADMINISTRATOR'])->get();
        }

        $data = [
            'staffs' => $staffs,
            'staffAuthorities' => $staffAuthorities,
            'staffAuthorityCount' => StaffAuthority::count(),
        ];

        return view('admin.Staff.index', $data);
    }

    private function getAuthListEdit()
    {
        if (Gate::check('inHouseAdmin-only')) { // 社内管理者でログインしたとき
            return StaffAuthority::all(); // なんにでも変えられる
        } elseif (Gate::check('clientAdmin-only')) { // クライアント管理者でログインしたとき
            return null; // 権限変更不可
        } else {
            abort(404); // このページにはこれない
        }
    }

    private function getAuthListAdd()
    {
        if (Gate::check('inHouseAdmin-only')) {
            return StaffAuthority::all();
        } elseif (Gate::check('clientAdmin-only')) {
            return null;
        } else {
            abort(404);
        }
    }

    /**
     * 管理画面 - スタッフ情報(編集)
     *
     * @param int $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function editForm(int $id)
    {
        $staff = Staff::with(['store.settlementCompany'])->find($id);
        $this->authorize('staff', $staff); //  ポリシーによる、個別に制御

        $settlementCompanies = SettlementCompany::where('published', 1)->get();

        $stores = Store::where('settlement_company_id', $staff->settlement_company_id)->get();

        return view('admin.Staff.edit', [
            'staff' => $staff,
            'staffAuthorities' => $this->getAuthListEdit(),
            'settlementCompanies' => $settlementCompanies,
            'stores' => $stores,
        ]);
    }

    /**
     * 管理画面 - スタッフ情報(更新)
     *
     * @param StaffDetailRequest $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function edit(StaffDetailRequest $request, int $id)
    {
        try {
            \DB::beginTransaction();
            $staff = Staff::find($id);
            $staff->name = $request->input('name');
            if (Gate::check('inHouseGeneral-higher')) {
                $staff->staff_authority_id = $request->input('staff_authority_id');
                $staff->settlement_company_id = $request->input('settlement_company_id', 0);
                $staff->store_id = $request->input('store_id', 0);
            }
            $staff->published = (!empty($request->input('published'))) ? 1 : 0;

            $staff->save();
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollBack();

            return redirect($request->input('redirect_to'))->with('custom_error', sprintf('スタッフ「%s」を更新できませんでした。', $request->name));
        }

        return redirect($request->input('redirect_to'))->with('message', sprintf('スタッフ「%s」を更新しました。', $staff->name));
    }

    /**
     * 管理画面 - スタッフアカウント(追加)
     *
     * @param StaffRequest $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function addForm()
    {
        $settlementCompanies = SettlementCompany::where('published', 1)->get();

        return view(
            'admin.Staff.add',
            [
                'staffAuthorities' => $this->getAuthListAdd(),
                'settlementCompanies' => $settlementCompanies,
            ]
        );
    }

    /**
     * 管理画面 - スタッフアカウント(追加)
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function add(StaffRequest $request)
    {
        $valid = $request->validated();

        try {
            \DB::beginTransaction();
            $authId = (Auth::user())->staff_authority_id;

            $authority = config('const.staff.authority');

            $storeId = (!empty($valid['store_id'])) ? $valid['store_id'] : 0;
            $settlementCompanyId = (!empty($valid['settlement_company_id'])) ? $valid['settlement_company_id'] : 0;
            if ($authId == $authority['CLIENT_ADMINISTRATOR'] || $authId == $authority['CLIENT_GENERAL']) {
                $staff = Staff::find((Auth::user())->id);
                $storeId = $staff->store_id;
                $settlementCompanyId = $staff->settlement_company_id;
            }

            if (!empty($valid['staff_authority_id'])) {
                $staffAuthorityId = $valid['staff_authority_id'];
            } else {
                switch ($authId) {
                case $authority['IN_HOUSE_GENERAL']: $staffAuthorityId = $authority['IN_HOUSE_GENERAL'];
                    break;
                case $authority['CLIENT_ADMINISTRATOR']:
                case $authority['CLIENT_GENERAL']: $staffAuthorityId = $authority['CLIENT_GENERAL'];
                    break;
                default:
                    $staffAuthorityId = 0;
                    break;
            }
            }

            Staff::firstOrCreate([
            'name' => $valid['name'],
            'username' => $valid['username'],
            'staff_authority_id' => $staffAuthorityId,
            'published' => (!empty($request->input('published'))) ? 1 : 0,
            'password' => bcrypt($valid['val-password2']),
            'settlement_company_id' => $settlementCompanyId,
            'store_id' => $storeId,
        ]);
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollBack();

            return redirect(route('admin.staff'))->with('custom_error', sprintf('スタッフ「%s」を作成できませんでした', $request->name));
        }

        return redirect(route('admin.staff'))->with('message', sprintf("スタッフ「%s」を作成しました", $valid['name']));
    }

    /**
     * 管理画面 - スタッフ情報(パスワード更新)
     *
     * @param StaffPasswordRequest $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function editPassword(StaffPasswordRequest $request, int $id)
    {
        try {
            \DB::beginTransaction();
            $staff = Staff::find($id);
            $staff->password = bcrypt($request->validated()['val-password2']);
            if ($id == $request->user()->id) {
                $staff->password_modified = Carbon::now();
            }
            $staff->save();
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollBack();

            return redirect($request->input('redirect_to'))->with('custom_error', 'パスワードを更新できませんでした。');
        }

        return redirect($request->input('redirect_to'))->with('message', 'パスワードを更新しました。');
    }

    /**
     * 管理画面 - スタッフ情報(パスワード変更)
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function editPasswordForm()
    {
        return view('admin.Staff.edit_password', ['staff' => Auth::user() , 'firstLogin' => false]);
    }

    /**
     * 管理画面 - スタッフ情報(初回ログイン時のパスワード変更)
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function editPasswordFirstLoginForm()
    {
        return view('admin.Staff.edit_password', ['staff' => Auth::user(), 'firstLogin' => true]);
    }

    public function storeList(int $id)
    {
        if ($id == 0) {
            $stores = collect();
            $authId = (Auth::user())->staff_authority_id;
            if($authId == config('const.staff.authority.CLIENT_GENERAL')) {
                $staff = Staff::with(['store.settlementCompany'])->find($authId);
                $settlementCompanyId = $staff->store->settlementCompany->id;
                $stores = Store::where('settlement_company_id', $settlementCompanyId)->get();
            }
        } else {
            $stores = Store::where('settlement_company_id', $id)->get();
        }

        return json_encode(['ret' => $stores->toArray()]);
    }
}
