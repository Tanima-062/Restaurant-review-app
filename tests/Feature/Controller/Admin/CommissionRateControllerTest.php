<?php

namespace Tests\Feature\Controller\Admin;

use App\Http\Requests\Admin\CommissionRateRequest;
use App\Models\CommissionRate;
use App\Models\SettlementCompany;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class CommissionRateControllerTest extends TestCase
{
    private $settlementCompany;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();

        $settlementCompany = new SettlementCompany();
        $settlementCompany->save();
        $this->settlementCompany = $settlementCompany;
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testIndexWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);                                                               // アクセス確認
        $response->assertViewIs('admin.CommissionRate.index');                                      // 指定bladeを確認
        $response->assertViewHasAll(['page', 'config', 'commissionRates', 'settlementCompanyId']);  // bladeに渡している変数を確認
        $response->assertViewHas('page', 1);
        $response->assertViewHas('config', config('const.commissionRate'));
        $response->assertViewHas('settlementCompanyId', $this->settlementCompany->id);

        $this->logout();
    }

    public function testIndexWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();        // 社内一般としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);                                                                // アクセス確認
        $response->assertViewIs('admin.CommissionRate.index');                                       // 指定bladeを確認
        $response->assertViewHasAll(['page', 'config', 'commissionRates', 'settlementCompanyId']);   // bladeに渡している変数を確認
        $response->assertViewHas('page', 1);
        $response->assertViewHas('config', config('const.commissionRate'));
        $response->assertViewHas('settlementCompanyId', $this->settlementCompany->id);

        $this->logout();
    }

    public function testIndexWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();        // 社外一般権限としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);                                                                // アクセス確認
        $response->assertViewIs('admin.CommissionRate.index');                                       // 指定bladeを確認
        $response->assertViewHasAll(['page', 'config', 'commissionRates', 'settlementCompanyId']);   // bladeに渡している変数を確認
        $response->assertViewHas('page', 1);
        $response->assertViewHas('config', config('const.commissionRate'));
        $response->assertViewHas('settlementCompanyId', $this->settlementCompany->id);

        $this->logout();
    }

    public function testAddFormWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $response = $this->_callAddForm();
        $response->assertStatus(200);                                       // アクセス確認
        $response->assertViewIs('admin.CommissionRate.add');                // 指定bladeを確認
        $response->assertViewHasAll(['settlementCompanyId', 'onlySeats']);  // bladeに渡している変数を確認
        $response->assertViewHas('settlementCompanyId', $this->settlementCompany->id);
        $response->assertViewHas('onlySeats', [1 => '有', 0 => '無']);

        $this->logout();
    }

    public function testAddFormWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();        // 社内一般としてログイン

        $response = $this->_callAddForm();
        $response->assertStatus(200);                                       // アクセス確認
        $response->assertViewIs('admin.CommissionRate.add');                // 指定bladeを確認
        $response->assertViewHasAll(['settlementCompanyId', 'onlySeats']);  // bladeに渡している変数を確認
        $response->assertViewHas('settlementCompanyId', $this->settlementCompany->id);
        $response->assertViewHas('onlySeats', [1 => '有', 0 => '無']);

        $this->logout();
    }

    public function testAddFormWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();        // 社外一般権限としてログイン

        $response = $this->_callAddForm();
        $response->assertStatus(200);                                       // アクセス確認
        $response->assertViewIs('admin.CommissionRate.add');                // 指定bladeを確認
        $response->assertViewHasAll(['settlementCompanyId', 'onlySeats']);  // bladeに渡している変数を確認
        $response->assertViewHas('settlementCompanyId', $this->settlementCompany->id);
        $response->assertViewHas('onlySeats', [1 => '有', 0 => '無']);

        $this->logout();
    }

    public function testAddWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        // データが未登録なことを確認
        $this->assertFalse(CommissionRate::where('Settlement_company_id', $this->settlementCompany->id)->exists());

        $response = $this->_callAdd();
        $response->assertStatus(302);                                                                                   // リダイレクト
        $response->assertRedirect('/admin/settlement_company/' . $this->settlementCompany->id . '/commission_rate');    // リダイレクト先
        $response->assertSessionHas('message', '販売手数料を作成しました');

        // データが登録されていることを確認
        $this->assertTrue(CommissionRate::where('Settlement_company_id', $this->settlementCompany->id)->exists());

        $this->logout();
    }

    public function testAddWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();        // 社内一般としてログイン

        // データが未登録なことを確認
        $this->assertFalse(CommissionRate::where('Settlement_company_id', $this->settlementCompany->id)->exists());

        $response = $this->_callAdd();
        $response->assertStatus(302);                                                                                   // リダイレクト
        $response->assertRedirect('/admin/settlement_company/' . $this->settlementCompany->id . '/commission_rate');    // リダイレクト先
        $response->assertSessionHas('message', '販売手数料を作成しました');

        // データが登録されていることを確認
        $this->assertTrue(CommissionRate::where('Settlement_company_id', $this->settlementCompany->id)->exists());

        $this->logout();
    }

    public function testAddWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();        // 社外一般権限としてログイン

        // データが未登録なことを確認
        $this->assertFalse(CommissionRate::where('Settlement_company_id', $this->settlementCompany->id)->exists());

        $response = $this->_callAdd();
        $response->assertStatus(302);                                                                                   // リダイレクト
        $response->assertRedirect('/admin/settlement_company/' . $this->settlementCompany->id . '/commission_rate');    // リダイレクト先
        $response->assertSessionHas('message', '販売手数料を作成しました');

        // データが登録されていることを確認
        $this->assertTrue(CommissionRate::where('Settlement_company_id', $this->settlementCompany->id)->exists());

        $this->logout();
    }

    public function testAddThrowable()
    {
        $CommissionRateRequest = \Mockery::mock(CommissionRateRequest::class)->makePartial();     // CommissionRateRequestのexcept()呼び出しで例外発生させるようにする
        $CommissionRateRequest->shouldReceive('input')->andReturn(1);
        $CommissionRateRequest->shouldReceive('all')->andReturn(['settlement_company_id' => $this->settlementCompany->id]);
        $CommissionRateRequest->shouldReceive('except')->andThrow(new \Exception());
        $this->app->instance(CommissionRateRequest::class, $CommissionRateRequest);

        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $response = $this->_callAdd();
        $response->assertStatus(302);                                                                                   // リダイレクト
        $response->assertRedirect('/admin/settlement_company/' . $this->settlementCompany->id . '/commission_rate');    // リダイレクト先
        $response->assertSessionHas('custom_error', '販売手数料を作成できませんでした');

        // データが登録されていないことを確認
        $this->assertFalse(CommissionRate::where('Settlement_company_id', $this->settlementCompany->id)->exists());

        $this->logout();
    }

    public function testEditFormWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $response = $this->_callEditForm();
        $response->assertStatus(200);                                                          // アクセス確認
        $response->assertViewIs('admin.CommissionRate.edit');                                  // 指定bladeを確認
        $response->assertViewHasAll(['settlementCompanyId', 'commissionRate', 'onlySeats']);   // bladeに渡している変数を確認
        $response->assertViewHas('settlementCompanyId', $this->settlementCompany->id);
        $response->assertViewHas('onlySeats', [1 => '有', 0 => '無']);

        $this->logout();
    }

    public function testEditFormWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();        // 社内一般としてログイン

        $response = $this->_callEditForm();
        $response->assertStatus(200);                                                          // アクセス確認
        $response->assertViewIs('admin.CommissionRate.edit');                                  // 指定bladeを確認
        $response->assertViewHasAll(['settlementCompanyId', 'commissionRate', 'onlySeats']);   // bladeに渡している変数を確認
        $response->assertViewHas('settlementCompanyId', $this->settlementCompany->id);
        $response->assertViewHas('onlySeats', [1 => '有', 0 => '無']);

        $this->logout();
    }

    public function testEditFormWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();        // 社外一般権限としてログイン

        $response = $this->_callEditForm();
        $response->assertStatus(200);                                                          // アクセス確認
        $response->assertViewIs('admin.CommissionRate.edit');                                  // 指定bladeを確認
        $response->assertViewHasAll(['settlementCompanyId', 'commissionRate', 'onlySeats']);   // bladeに渡している変数を確認
        $response->assertViewHas('settlementCompanyId', $this->settlementCompany->id);
        $response->assertViewHas('onlySeats', [1 => '有', 0 => '無']);

        $this->logout();
    }

    public function testEditWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $response = $this->_callEdit();
        $response->assertStatus(302);                                                                                   // リダイレクト
        $response->assertRedirect('/admin/settlement_company/' . $this->settlementCompany->id . '/commission_rate');    // リダイレクト先
        $response->assertSessionHas('message', '販売手数料を編集しました');

        // データが書き変わっていること
        $result = CommissionRate::where('settlement_company_id', $this->settlementCompany->id)->first();
        $this->assertSame('2022-10-01 00:00:00', $result->apply_term_from);
        $this->assertSame(1, $result->only_seat);

        $this->logout();
    }

    public function testEditWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();        // 社内一般としてログイン

        $response = $this->_callEdit();
        $response->assertStatus(302);                                                                                   // リダイレクト
        $response->assertRedirect('/admin/settlement_company/' . $this->settlementCompany->id . '/commission_rate');    // リダイレクト先
        $response->assertSessionHas('message', '販売手数料を編集しました');

        // データが書き変わっていること
        $result = CommissionRate::where('settlement_company_id', $this->settlementCompany->id)->first();
        $this->assertSame('2022-10-01 00:00:00', $result->apply_term_from);
        $this->assertSame(1, $result->only_seat);

        $this->logout();
    }

    public function testEditWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();        // 社外一般権限としてログイン

        $response = $this->_callEdit();
        $response->assertStatus(302);                                                                                   // リダイレクト
        $response->assertRedirect('/admin/settlement_company/' . $this->settlementCompany->id . '/commission_rate');    // リダイレクト先
        $response->assertSessionHas('message', '販売手数料を編集しました');

        // データが書き変わっていること
        $result = CommissionRate::where('settlement_company_id', $this->settlementCompany->id)->first();
        $this->assertSame('2022-10-01 00:00:00', $result->apply_term_from);
        $this->assertSame(1, $result->only_seat);

        $this->logout();
    }

    public function testEditThrowable()
    {
        $CommissionRateRequest = \Mockery::mock(CommissionRateRequest::class)->makePartial();     // CommissionRateRequestのexcept()呼び出しで例外発生させるようにする
        $CommissionRateRequest->shouldReceive('input')->andReturn(1);
        $CommissionRateRequest->shouldReceive('all')->andReturn(['settlement_company_id' => $this->settlementCompany->id]);
        $CommissionRateRequest->shouldReceive('except')->andThrow(new \Exception());
        $this->app->instance(CommissionRateRequest::class, $CommissionRateRequest);

        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $response = $this->_callEdit();
        $response->assertStatus(302);                                                                                   // リダイレクト
        $response->assertRedirect('/admin/settlement_company/' . $this->settlementCompany->id . '/commission_rate');    // リダイレクト先
        $response->assertSessionHas('custom_error', '販売手数料を更新できませんでした');

        // データが書き変わっていないこと
        $result = CommissionRate::where('settlement_company_id', $this->settlementCompany->id)->first();
        $this->assertSame('2021-10-01 00:00:00', $result->apply_term_from);
        $this->assertSame(0, $result->only_seat);

        $this->logout();
    }

    public function testDeleteWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $commissionRate = $this->_createCommissionRate('TO');

        // 削除機能の呼び出し前にデータがあることを確認
        $this->assertTrue(CommissionRate::where('id', $commissionRate->id)->exists());

        $response = $this->_callDelete($commissionRate);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 削除されていることを確認
        $this->assertFalse(CommissionRate::where('id', $commissionRate->id)->exists());

        $this->logout();
    }

    public function testDeleteWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();        // 社内一般としてログイン

        $commissionRate = $this->_createCommissionRate('TO');

        // 削除機能の呼び出し前にデータがあることを確認
        $this->assertTrue(CommissionRate::where('id', $commissionRate->id)->exists());

        $response = $this->_callDelete($commissionRate);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 削除されていることを確認
        $this->assertFalse(CommissionRate::where('id', $commissionRate->id)->exists());

        $this->logout();
    }

    public function testDeleteWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();        // 社外一般権限としてログイン

        $commissionRate = $this->_createCommissionRate('TO');

        // 削除機能の呼び出し前にデータがあることを確認
        $this->assertTrue(CommissionRate::where('id', $commissionRate->id)->exists());

        $response = $this->_callDelete($commissionRate);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 削除されていることを確認
        $this->assertFalse(CommissionRate::where('id', $commissionRate->id)->exists());

        $this->logout();
    }

    public function testCommissionRateControllerWithClientAdministrator()
    {
        $this->loginWithClientAdministrator();        // クライアント管理者としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm();
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit();
        $response->assertStatus(404);

        // target method delete
        $commissionRate = $this->_createCommissionRate('TO');
        $response = $this->_callDelete($commissionRate);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testCommissionRateControllerWithClientGeneral()
    {
        $this->loginWithClientGeneral();        // クライアント一般としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm();
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit();
        $response->assertStatus(404);

        // target method delete
        $commissionRate = $this->_createCommissionRate('TO');
        $response = $this->_callDelete($commissionRate);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testCommissionRateControllerWithSettlementAdministrator()
    {
        $this->loginWithSettlementAdministrator();        // 精算管理会社としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm();
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit();
        $response->assertStatus(404);

        // target method delete
        $commissionRate = $this->_createCommissionRate('TO');
        $response = $this->_callDelete($commissionRate);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testCommissionRateControllerAsNoLogin()
    {
        // Controller内の関数にアクセスできないこと（リダイレクトでログイン画面に遷移）を確認する

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(302);                   // アクセス確認
        $response->assertRedirect('/admin');            // 管理サイトトップへ

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(302);                   // アクセス確認
        $response->assertRedirect('/admin');            // 管理サイトトップへ

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(302);                   // アクセス確認
        $response->assertRedirect('/admin');            // 管理サイトトップへ

        // target method editForm
        $response = $this->_callEditForm();
        $response->assertStatus(302);                   // アクセス確認
        $response->assertRedirect('/admin');            // 管理サイトトップへ

        // target method edit
        $response = $this->_callEdit();
        $response->assertStatus(302);                   // アクセス確認
        $response->assertRedirect('/admin');            // 管理サイトトップへ

        // target method delete
        $commissionRate = $this->_createCommissionRate('TO');
        $response = $this->_callDelete($commissionRate);
        $response->assertStatus(302);                   // アクセス確認
        $response->assertRedirect('/admin');            // 管理サイトトップへ
    }

    private function _createCommissionRate($appCd, $published = 0)
    {
        $commissionRate = new CommissionRate();
        $commissionRate->settlement_company_id = $this->settlementCompany->id;
        $commissionRate->app_cd = $appCd;
        $commissionRate->apply_term_from = '2021-10-01 00:00:00';
        $commissionRate->apply_term_to = '2099-12-31 23:59:59';
        $commissionRate->accounting_condition = 'FIXED_RATE';
        $commissionRate->fee = '10.0';
        $commissionRate->only_seat = 0;
        $commissionRate->published = $published;
        $commissionRate->save();
        return $commissionRate;
    }

    private function _callIndex()
    {
        return $this->get('/admin/settlement_company/' . $this->settlementCompany->id . '/commission_rate');
    }

    private function _callAddForm()
    {
        return $this->get('/admin/settlement_company/' . $this->settlementCompany->id . '/commission_rate/add');
    }

    private function _callAdd()
    {
        return $this->post('/admin/settlement_company/' . $this->settlementCompany->id . '/commission_rate/add', [
            'settlement_company_id' => $this->settlementCompany->id,
            'app_cd' => 'TO',
            'apply_term_from_year' => '2022',
            'apply_term_from_month' => '10',
            'apply_term_to_year' => '2099',
            'apply_term_to_month' => '12',
            'accounting_condition' => 'FIXED_RATE',
            'fee' => '10.0',
            'only_seat' => 0,
            'published' => 0,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditForm()
    {
        $commissionRate = $this->_createCommissionRate('TO');
        return $this->get('/admin/settlement_company/' . $this->settlementCompany->id . '/commission_rate/edit/' . $commissionRate->id);
    }

    private function _callEdit()
    {
        $commissionRate = $this->_createCommissionRate('TO');
        return $this->post('/admin/settlement_company/' . $this->settlementCompany->id . '/commission_rate/edit/' . $commissionRate->id, [
            'id' => $commissionRate->id,
            'settlement_company_id' => $this->settlementCompany->id,
            'app_cd' => 'TO',
            'apply_term_from_year' => '2022',
            'apply_term_from_month' => '10',
            'apply_term_to_year' => '2099',
            'apply_term_to_month' => '12',
            'accounting_condition' => 'FIXED_RATE',
            'fee' => '10.0',
            'only_seat' => 1,
            'published' => 0,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callDelete($commissionRate)
    {
        return $this->post('/admin/settlement_company/' . $this->settlementCompany->id . '/commission_rate/delete/' . $commissionRate->id, [
            'id' => $commissionRate->id,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }
}
