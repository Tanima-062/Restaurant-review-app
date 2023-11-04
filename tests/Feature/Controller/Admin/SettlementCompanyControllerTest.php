<?php

namespace Tests\Feature\Controller\Admin;

use App\Http\Requests\Admin\SettlementCompanyRequest;
use App\Models\SettlementCompany;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class SettlementCompanyControllerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testIndexWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);
        $response->assertViewIs('admin.SettlementCompany.index'); // 指定bladeを確認
        $response->assertViewHasAll([
            'page',
            'settlementCompanies',
            'cycle',
            'baseAmount',
            'tax',
        ]);     // bladeに渡している変数を確認
        $response->assertViewHas('page', 1);
        $response->assertViewHas('cycle', ['TWICE_A_MONTH' => '月2回', 'ONCE_A_MONTH' => '月1回']);
        $response->assertViewHas('baseAmount', ['TAX_INCLUDED' => '成約金額（税込）', 'TAX_EXCLUDED' => '成約金額（税抜）']);
        $response->assertViewHas('tax', ['EXCLUSIVE' => '外税', 'INCLUSIVE' => '内税']);

        $this->logout();
    }

    public function testIndexWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();                        // 社内一般としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);
        $response->assertViewIs('admin.SettlementCompany.index'); // 指定bladeを確認
        $response->assertViewHasAll([
            'page',
            'settlementCompanies',
            'cycle',
            'baseAmount',
            'tax',
        ]);     // bladeに渡している変数を確認
        $response->assertViewHas('page', 1);
        $response->assertViewHas('cycle', ['TWICE_A_MONTH' => '月2回', 'ONCE_A_MONTH' => '月1回']);
        $response->assertViewHas('baseAmount', ['TAX_INCLUDED' => '成約金額（税込）', 'TAX_EXCLUDED' => '成約金額（税抜）']);
        $response->assertViewHas('tax', ['EXCLUSIVE' => '外税', 'INCLUSIVE' => '内税']);

        $this->logout();
    }

    public function testIndexWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);
        $response->assertViewIs('admin.SettlementCompany.index'); // 指定bladeを確認
        $response->assertViewHasAll([
            'page',
            'settlementCompanies',
            'cycle',
            'baseAmount',
            'tax',
        ]);     // bladeに渡している変数を確認
        $response->assertViewHas('page', 1);
        $response->assertViewHas('cycle', ['TWICE_A_MONTH' => '月2回', 'ONCE_A_MONTH' => '月1回']);
        $response->assertViewHas('baseAmount', ['TAX_INCLUDED' => '成約金額（税込）', 'TAX_EXCLUDED' => '成約金額（税抜）']);
        $response->assertViewHas('tax', ['EXCLUSIVE' => '外税', 'INCLUSIVE' => '内税']);

        $this->logout();
    }

    public function testEditFormWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        $response = $this->_callEditForm($settlementCompany);
        $response->assertStatus(200);
        $response->assertViewIs('admin.SettlementCompany.edit'); // 指定bladeを確認
        $response->assertViewHasAll([
            'settlementCompany',
            'cycle',
            'baseAmount',
            'taxCalculation',
            'accountType',
        ]);     // bladeに渡している変数を確認
        $response->assertViewHas('settlementCompany', $settlementCompany);
        $response->assertViewHas('cycle', config('const.settlement.payment_cycle'));
        $response->assertViewHas('baseAmount', config('const.settlement.result_base_amount'));
        $response->assertViewHas('taxCalculation', config('const.settlement.tax_calculation'));
        $response->assertViewHas('accountType', config('const.settlement.account_type'));

        $this->logout();
    }

    public function testEditFormWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();                        // 社内一般としてログイン

        $response = $this->_callEditForm($settlementCompany);
        $response->assertStatus(200);
        $response->assertViewIs('admin.SettlementCompany.edit'); // 指定bladeを確認
        $response->assertViewHasAll([
            'settlementCompany',
            'cycle',
            'baseAmount',
            'taxCalculation',
            'accountType',
        ]);     // bladeに渡している変数を確認
        $response->assertViewHas('settlementCompany', $settlementCompany);
        $response->assertViewHas('cycle', config('const.settlement.payment_cycle'));
        $response->assertViewHas('baseAmount', config('const.settlement.result_base_amount'));
        $response->assertViewHas('taxCalculation', config('const.settlement.tax_calculation'));
        $response->assertViewHas('accountType', config('const.settlement.account_type'));

        $this->logout();
    }

    public function testEditFormWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callEditForm($settlementCompany);
        $response->assertStatus(200);
        $response->assertViewIs('admin.SettlementCompany.edit'); // 指定bladeを確認
        $response->assertViewHasAll([
            'settlementCompany',
            'cycle',
            'baseAmount',
            'taxCalculation',
            'accountType',
        ]);     // bladeに渡している変数を確認
        $response->assertViewHas('settlementCompany', $settlementCompany);
        $response->assertViewHas('cycle', config('const.settlement.payment_cycle'));
        $response->assertViewHas('baseAmount', config('const.settlement.result_base_amount'));
        $response->assertViewHas('taxCalculation', config('const.settlement.tax_calculation'));
        $response->assertViewHas('accountType', config('const.settlement.account_type'));

        $this->logout();
    }

    public function testEditWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        $response = $this->_callEdit($settlementCompany);
        $response->assertStatus(302);                   // リダイレクト
        $response->assertRedirect('/admin/settlement_company');       // リダイレクト先
        $response->assertSessionHas('message', '精算会社「testテストtest精算会社更新」を更新しました');

        $result = SettlementCompany::find($settlementCompany->id);
        $this->assertSame('testテストtest精算会社更新', $result->name);
        $this->assertSame('1234567', $result->postal_code);
        $this->assertSame('0311112222', $result->tel);
        $this->assertSame('テスト住所', $result->address);
        $this->assertSame('TWICE_A_MONTH', $result->payment_cycle);
        $this->assertSame('TAX_INCLUDED', $result->result_base_amount);
        $this->assertSame('EXCLUSIVE', $result->tax_calculation);
        $this->assertSame('テスト銀行', $result->bank_name);
        $this->assertSame('テスト支店', $result->branch_name);
        $this->assertSame('012', $result->branch_number);
        $this->assertSame('SAVINGS', $result->account_type);
        $this->assertSame('01234567', $result->account_number);
        $this->assertSame('テストタロウ', $result->account_name_kana);
        $this->assertSame('gourmet-test1@adventure-inc.co.jp', $result->billing_email_1);
        $this->assertSame('gourmet-test2@adventure-inc.co.jp', $result->billing_email_2);
        $this->assertSame(1, $result->published);

        $this->logout();
    }

    public function testEditWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();                        // 社内一般としてログイン

        $response = $this->_callEdit($settlementCompany);
        $response->assertStatus(302);                   // リダイレクト
        $response->assertRedirect('/admin/settlement_company');       // リダイレクト先
        $response->assertSessionHas('message', '精算会社「testテストtest精算会社更新」を更新しました');

        $result = SettlementCompany::find($settlementCompany->id);
        $this->assertSame('testテストtest精算会社更新', $result->name);
        $this->assertSame('1234567', $result->postal_code);
        $this->assertSame('0311112222', $result->tel);
        $this->assertSame('テスト住所', $result->address);
        $this->assertSame('TWICE_A_MONTH', $result->payment_cycle);
        $this->assertSame('TAX_INCLUDED', $result->result_base_amount);
        $this->assertSame('EXCLUSIVE', $result->tax_calculation);
        $this->assertSame('テスト銀行', $result->bank_name);
        $this->assertSame('テスト支店', $result->branch_name);
        $this->assertSame('012', $result->branch_number);
        $this->assertSame('SAVINGS', $result->account_type);
        $this->assertSame('01234567', $result->account_number);
        $this->assertSame('テストタロウ', $result->account_name_kana);
        $this->assertSame('gourmet-test1@adventure-inc.co.jp', $result->billing_email_1);
        $this->assertSame('gourmet-test2@adventure-inc.co.jp', $result->billing_email_2);
        $this->assertSame(1, $result->published);

        $this->logout();
    }

    public function testEditWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callEdit($settlementCompany);
        $response->assertStatus(302);                   // リダイレクト
        $response->assertRedirect('/admin/settlement_company');       // リダイレクト先
        $response->assertSessionHas('message', '精算会社「testテストtest精算会社更新」を更新しました');

        // 更新されていることを確認する
        $result = SettlementCompany::find($settlementCompany->id);
        $this->assertSame('testテストtest精算会社更新', $result->name);
        $this->assertSame('1234567', $result->postal_code);
        $this->assertSame('0311112222', $result->tel);
        $this->assertSame('テスト住所', $result->address);
        $this->assertSame('TWICE_A_MONTH', $result->payment_cycle);
        $this->assertSame('TAX_INCLUDED', $result->result_base_amount);
        $this->assertSame('EXCLUSIVE', $result->tax_calculation);
        $this->assertSame('テスト銀行', $result->bank_name);
        $this->assertSame('テスト支店', $result->branch_name);
        $this->assertSame('012', $result->branch_number);
        $this->assertSame('SAVINGS', $result->account_type);
        $this->assertSame('01234567', $result->account_number);
        $this->assertSame('テストタロウ', $result->account_name_kana);
        $this->assertSame('gourmet-test1@adventure-inc.co.jp', $result->billing_email_1);
        $this->assertSame('gourmet-test2@adventure-inc.co.jp', $result->billing_email_2);
        $this->assertSame(1, $result->published);

        $this->logout();
    }

    public function testEditThrowable()
    {
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        // SettlementCompanyRequestのexcept呼び出しで例外発生させるようにする
        $settlementCompanyRequest = \Mockery::mock(SettlementCompanyRequest::class)->makePartial();
        $settlementCompanyRequest->shouldReceive('except')->andThrow(new \Exception());
        $settlementCompanyRequest->shouldReceive('all')->andReturn('testテストtest精算会社');   // all関数呼び出しの時は固定文字列を渡しておく
        $this->app->instance(SettlementCompanyRequest::class, $settlementCompanyRequest);

        $response = $this->_callEdit($settlementCompany);
        $response->assertStatus(302);                                 // リダイレクト
        $response->assertRedirect('/admin/settlement_company');       // リダイレクト先
        $response->assertSessionHas('custom_error', '精算会社「」を更新できませんでした');  // モックで精算会社名を渡せていないため空になっている

        // 更新されていないことを確認
        $result = SettlementCompany::find($settlementCompany->id);
        $this->assertSame('testテストtest精算会社', $result->name);

        $this->logout();
    }

    public function testAddFormWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        $response = $this->_callAddForm();
        $response->assertStatus(200);
        $response->assertViewIs('admin.SettlementCompany.add'); // 指定bladeを確認
        $response->assertViewHas([
            'cycle',
            'baseAmount',
            'taxCalculation',
            'accountType',
        ]);     // bladeに渡している変数を確認
        $response->assertViewHas('cycle', config('const.settlement.payment_cycle'));
        $response->assertViewHas('baseAmount', config('const.settlement.result_base_amount'));
        $response->assertViewHas('taxCalculation', config('const.settlement.tax_calculation'));
        $response->assertViewHas('accountType', config('const.settlement.account_type'));

        $this->logout();
    }

    public function testAddFormWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();                        // 社内一般としてログイン

        $response = $this->_callAddForm();
        $response->assertStatus(200);
        $response->assertViewIs('admin.SettlementCompany.add'); // 指定bladeを確認
        $response->assertViewHas([
            'cycle',
            'baseAmount',
            'taxCalculation',
            'accountType',
        ]);     // bladeに渡している変数を確認
        $response->assertViewHas('cycle', config('const.settlement.payment_cycle'));
        $response->assertViewHas('baseAmount', config('const.settlement.result_base_amount'));
        $response->assertViewHas('taxCalculation', config('const.settlement.tax_calculation'));
        $response->assertViewHas('accountType', config('const.settlement.account_type'));

        $this->logout();
    }

    public function testAddFormWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callAddForm();
        $response->assertStatus(200);
        $response->assertViewIs('admin.SettlementCompany.add'); // 指定bladeを確認
        $response->assertViewHas([
            'cycle',
            'baseAmount',
            'taxCalculation',
            'accountType',
        ]);     // bladeに渡している変数を確認
        $response->assertViewHas('cycle', config('const.settlement.payment_cycle'));
        $response->assertViewHas('baseAmount', config('const.settlement.result_base_amount'));
        $response->assertViewHas('taxCalculation', config('const.settlement.tax_calculation'));
        $response->assertViewHas('accountType', config('const.settlement.account_type'));

        $this->logout();
    }

    public function testAddWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン
        $user = $this->getLoginUserInfo();

        $response = $this->_callAdd();
        $response->assertStatus(302);                               // リダイレクト
        $response->assertRedirect('/admin/settlement_company');     // リダイレクト先
        $response->assertSessionHas('message', '精算会社「testテストtest精算会社」を作成しました');

        // 登録されていることを確認する
        $result = SettlementCompany::where('staff_id', $user->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('testテストtest精算会社', $result[0]['name']);
        $this->assertSame('1234567', $result[0]['postal_code']);
        $this->assertSame('0311112222', $result[0]['tel']);
        $this->assertSame('テスト住所', $result[0]['address']);
        $this->assertSame('TWICE_A_MONTH', $result[0]['payment_cycle']);
        $this->assertSame('TAX_INCLUDED', $result[0]['result_base_amount']);
        $this->assertSame('EXCLUSIVE', $result[0]['tax_calculation']);
        $this->assertSame('テスト銀行', $result[0]['bank_name']);
        $this->assertSame('テスト支店', $result[0]['branch_name']);
        $this->assertSame('012', $result[0]['branch_number']);
        $this->assertSame('SAVINGS', $result[0]['account_type']);
        $this->assertSame('01234567', $result[0]['account_number']);
        $this->assertSame('テストタロウ', $result[0]['account_name_kana']);
        $this->assertSame('gourmet-test1@adventure-inc.co.jp', $result[0]['billing_email_1']);
        $this->assertSame('gourmet-test2@adventure-inc.co.jp', $result[0]['billing_email_2']);
        $this->assertSame(1, $result[0]['published']);

        $this->logout();
    }

    public function testAddWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();                        // 社内一般としてログイン
        $user = $this->getLoginUserInfo();

        $response = $this->_callAdd();
        $response->assertStatus(302);                               // リダイレクト
        $response->assertRedirect('/admin/settlement_company');     // リダイレクト先
        $response->assertSessionHas('message', '精算会社「testテストtest精算会社」を作成しました');

        // 登録されていることを確認する
        $result = SettlementCompany::where('staff_id', $user->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('testテストtest精算会社', $result[0]['name']);
        $this->assertSame('1234567', $result[0]['postal_code']);
        $this->assertSame('0311112222', $result[0]['tel']);
        $this->assertSame('テスト住所', $result[0]['address']);
        $this->assertSame('TWICE_A_MONTH', $result[0]['payment_cycle']);
        $this->assertSame('TAX_INCLUDED', $result[0]['result_base_amount']);
        $this->assertSame('EXCLUSIVE', $result[0]['tax_calculation']);
        $this->assertSame('テスト銀行', $result[0]['bank_name']);
        $this->assertSame('テスト支店', $result[0]['branch_name']);
        $this->assertSame('012', $result[0]['branch_number']);
        $this->assertSame('SAVINGS', $result[0]['account_type']);
        $this->assertSame('01234567', $result[0]['account_number']);
        $this->assertSame('テストタロウ', $result[0]['account_name_kana']);
        $this->assertSame('gourmet-test1@adventure-inc.co.jp', $result[0]['billing_email_1']);
        $this->assertSame('gourmet-test2@adventure-inc.co.jp', $result[0]['billing_email_2']);
        $this->assertSame(1, $result[0]['published']);

        $this->logout();
    }

    public function testAddWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン
        $user = $this->getLoginUserInfo();

        $response = $this->_callAdd();
        $response->assertStatus(302);                               // リダイレクト
        $response->assertRedirect('/admin/settlement_company');     // リダイレクト先
        $response->assertSessionHas('message', '精算会社「testテストtest精算会社」を作成しました');

        // 登録されていることを確認する
        $result = SettlementCompany::where('staff_id', $user->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('testテストtest精算会社', $result[0]['name']);
        $this->assertSame('1234567', $result[0]['postal_code']);
        $this->assertSame('0311112222', $result[0]['tel']);
        $this->assertSame('テスト住所', $result[0]['address']);
        $this->assertSame('TWICE_A_MONTH', $result[0]['payment_cycle']);
        $this->assertSame('TAX_INCLUDED', $result[0]['result_base_amount']);
        $this->assertSame('EXCLUSIVE', $result[0]['tax_calculation']);
        $this->assertSame('テスト銀行', $result[0]['bank_name']);
        $this->assertSame('テスト支店', $result[0]['branch_name']);
        $this->assertSame('012', $result[0]['branch_number']);
        $this->assertSame('SAVINGS', $result[0]['account_type']);
        $this->assertSame('01234567', $result[0]['account_number']);
        $this->assertSame('テストタロウ', $result[0]['account_name_kana']);
        $this->assertSame('gourmet-test1@adventure-inc.co.jp', $result[0]['billing_email_1']);
        $this->assertSame('gourmet-test2@adventure-inc.co.jp', $result[0]['billing_email_2']);
        $this->assertSame(1, $result[0]['published']);

        $this->logout();
    }

    public function testAddThrowable()
    {
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン
        $user = $this->getLoginUserInfo();

        // SettlementCompanyRequestのexcept呼び出しで例外発生させるようにする
        $settlementCompanyRequest = \Mockery::mock(SettlementCompanyRequest::class)->makePartial();
        $settlementCompanyRequest->shouldReceive('except')->andThrow(new \Exception());
        $settlementCompanyRequest->shouldReceive('all')->andReturn('testテストtest精算会社');   // all関数呼び出しの時は固定文字列を渡しておく
        $this->app->instance(SettlementCompanyRequest::class, $settlementCompanyRequest);

        $response = $this->_callAdd();
        $response->assertStatus(302);                                 // リダイレクト
        $response->assertRedirect('/admin/settlement_company');       // リダイレクト先
        $response->assertSessionHas('custom_error', '精算会社「」を作成できませんでした');  // モックで精算会社名を渡せていないため空になっている

        //  登録されていないことを確認する
        $this->assertFalse(SettlementCompany::where('staff_id', $user->id)->exists());

        $this->logout();
    }

    public function testSettlementCompanyControllerWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($settlementCompany);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($settlementCompany);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(404);

        $this->logout();
    }

    public function testSettlementCompanyControllerWithClientGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientGeneral($store->id, $settlementCompany->id);               // クライアント一般としてログイン

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($settlementCompany);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($settlementCompany);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(404);

        $this->logout();
    }

    public function testSettlementCompanyControllerWithSettlementAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $this->loginWithSettlementAdministrator($settlementCompany->id);    // 精算管理会社としてログイン

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($settlementCompany);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($settlementCompany);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(404);

        $this->logout();
    }

    private function _createSettlementCompany()
    {
        $settlementCompany = new SettlementCompany();
        $settlementCompany->name = 'testテストtest精算会社';
        $settlementCompany->tel = '0698765432';
        $settlementCompany->postal_code = '1111123';
        $settlementCompany->save();

        return $settlementCompany;
    }

    private function _createStore($settlementCompanyId)
    {
        $store = new Store();
        $store->app_cd = 'TORS';
        $store->name = 'テスト店舗';
        $store->regular_holiday = '110111111';
        $store->published = 1;
        $store->settlement_company_id = $settlementCompanyId;
        $store->save();

        return $store;
    }

    private function _callIndex()
    {
        return $this->get('/admin/settlement_company');
    }

    private function _callEditForm(&$settlementCompany = null)
    {
        if (is_null($settlementCompany)) {
            $settlementCompany = $this->_createSettlementCompany();
        }
        return $this->get('/admin/settlement_company/edit/' . $settlementCompany->id);
    }

    private function _callEdit(&$settlementCompany = null)
    {
        if (is_null($settlementCompany)) {
            $settlementCompany = $this->_createSettlementCompany();
        }
        return $this->post('/admin/settlement_company/edit/' . $settlementCompany->id, [
            'name' => 'testテストtest精算会社更新',
            'postal_code' => '1234567',
            'tel' => '0311112222',
            'address' => 'テスト住所',
            'payment_cycle' => 'TWICE_A_MONTH',
            'result_base_amount' => 'TAX_INCLUDED',
            'tax_calculation' => 'EXCLUSIVE',
            'bank_name' => 'テスト銀行',
            'branch_name' => 'テスト支店',
            'branch_number' => '012',
            'account_type' => 'SAVINGS',
            'account_number' => '01234567',
            'account_name_kana' => 'テストタロウ',
            'billing_email_1' => 'gourmet-test1@adventure-inc.co.jp',
            'billing_email_2' => 'gourmet-test2@adventure-inc.co.jp',
            'published' => 1,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddForm()
    {
        return $this->get('/admin/settlement_company/add');
    }

    private function _callAdd()
    {
        return $this->post('/admin/settlement_company/add', [
            'name' => 'testテストtest精算会社',
            'postal_code' => '1234567',
            'tel' => '0311112222',
            'address' => 'テスト住所',
            'payment_cycle' => 'TWICE_A_MONTH',
            'result_base_amount' => 'TAX_INCLUDED',
            'tax_calculation' => 'EXCLUSIVE',
            'bank_name' => 'テスト銀行',
            'branch_name' => 'テスト支店',
            'branch_number' => '012',
            'account_type' => 'SAVINGS',
            'account_number' => '01234567',
            'account_name_kana' => 'テストタロウ',
            'billing_email_1' => 'gourmet-test1@adventure-inc.co.jp',
            'billing_email_2' => 'gourmet-test2@adventure-inc.co.jp',
            'published' => 1,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }
}
