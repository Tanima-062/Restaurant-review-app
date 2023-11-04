<?php

namespace App\Http\Controllers\Admin;

use App\Http\Middleware\ActionLog;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends AdminController
{
    use AuthenticatesUsers;


    /**
     * Where to redirect users after login.
     *
     * @var string
     */
//    protected $redirectTo = 'admin/system_notifications/';
    protected $redirectTo = 'admin/reservation';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * 管理画面 - ログイン
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('admin.Auth.login');
    }

    /**
     * 管理画面 - ログイン処理
     *
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
//        $actionLog = new ActionLog;
//        $actionLog->actionLog($request, 999);

        $this->guard()->logout();

        return redirect()->route('admin.index');
    }

    /**
     * ログインに使用するユーザ名
     * @return string
     */
    public function username()
    {
        // email (デフォルト) → Staff.username
        return 'username';
    }

    /**
     * ログイン認証の条件
     * @param Request $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        // 公開スタッフのみログイン可
        return array_merge(
            $request->only($this->username(), 'password'),
            ['published' => 1]
        );
    }

    /**
     * ログイン認証成功後の処理
     * @param Request $request
     * @param $user
     */
    protected function authenticated(Request $request, $user)
    {
        // 最終ログイン日時を更新
        $user->last_login_at = now();
        $user->save();

        // 外注は予約一覧閲覧権限ないのでそれ以外のページに設定する必要がある
        if($user->staff_authority_id === config('const.staff.authority.OUT_HOUSE_GENERAL')){
            $this->redirectTo = 'admin/settlement_company';
        }

        // 精算管理会社は予約一覧閲覧権限ないのでそれ以外のページに設定する必要がある
        if ($user->staff_authority_id === config('const.staff.authority.SETTLEMENT_ADMINISTRATOR')) {
            $this->redirectTo = 'admin/settlement_confirm';
        }
    }
}
