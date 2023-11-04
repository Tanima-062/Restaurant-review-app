<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\CancelFee;
use App\Models\SettlementCompany;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class StoreCancelFeeControllerTest extends TestCase
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
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callIndex($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.CancelFee.index');        // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'cancelFees',
        ]);                                                     // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $resultCancelFee = CancelFee::where('store_id', $store->id)->sortable()->get();
        $response->assertViewHas('cancelFees', $resultCancelFee);
        $response->assertSessionHas('storeCancelFeeRedirectTo', env('APP_URL', null) . '/admin/store?page=1');

        $this->logout();
    }

    public function testIndexWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callIndex($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.CancelFee.index');        // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'cancelFees',
        ]);                                                     // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $resultCancelFee = CancelFee::where('store_id', $store->id)->sortable()->get();
        $response->assertViewHas('cancelFees', $resultCancelFee);

        $this->logout();
    }

    public function testIndexWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常にアクセスできること
        {
            $response = $this->_callIndex($store);
            $response->assertStatus(200);
            $response->assertViewIs('admin.Store.CancelFee.index');        // 指定bladeを確認
            $response->assertViewHasAll([
                'store',
                'cancelFees',
            ]);                                                     // bladeに渡している変数を確認
            $response->assertViewHas('store', $store);
            $resultCancelFee = CancelFee::where('store_id', $store->id)->sortable()->get();
            $response->assertViewHas('cancelFees', $resultCancelFee);
            $response->assertSessionHas('storeCancelFeeRedirectTo', env('APP_URL', null) . '/admin/store?page=1');
        }

        // 担当外店舗の場合、アクセスできないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callIndex($store2);
            $response->assertStatus(403);
        }

        $this->logout();
    }

    public function testIndexWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callIndex($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.CancelFee.index');        // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'cancelFees',
        ]);                                                     // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $resultCancelFee = CancelFee::where('store_id', $store->id)->sortable()->get();
        $response->assertViewHas('cancelFees', $resultCancelFee);
        $response->assertSessionHas('storeCancelFeeRedirectTo', env('APP_URL', null) . '/admin/store?page=1');

        $this->logout();
    }

    public function testAddFormWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callAddForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.CancelFee.add');   // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'storeCancelFeeConst',
        ]);                                                     // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('storeCancelFeeConst', config('const.storeCancelFee'));

        $this->logout();
    }

    public function testAddFormWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callAddForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.CancelFee.add');   // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'storeCancelFeeConst',
        ]);                                                     // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('storeCancelFeeConst', config('const.storeCancelFee'));

        $this->logout();
    }

    public function testAddFormWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常にアクセスできること
        {
            $response = $this->_callAddForm($store);
            $response->assertStatus(200);
            $response->assertViewIs('admin.Store.CancelFee.add');   // 指定bladeを確認
            $response->assertViewHasAll([
                'store',
                'storeCancelFeeConst',
            ]);                                                     // bladeに渡している変数を確認
            $response->assertViewHas('store', $store);
            $response->assertViewHas('storeCancelFeeConst', config('const.storeCancelFee'));
        }

        // 担当外店舗の場合、アクセスできないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callAddForm($store2);
            $response->assertStatus(403);
        }

        $this->logout();
    }

    public function testAddFormWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callAddForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.CancelFee.add');   // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'storeCancelFeeConst',
        ]);                                                     // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('storeCancelFeeConst', config('const.storeCancelFee'));

        $this->logout();
    }

    public function testAddWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        // 来店前キャンセル
        {
            $response = $this->_callAdd($store, 'BEFORE');
            $response->assertStatus(302);                                             // リダイレクト
            $response->assertRedirect('/admin/store/' . $store->id . '/cancel_fee');  // リダイレクト先
            $response->assertSessionHas('message', 'キャンセル料を登録しました。');

            $result = CancelFee::where('store_id', $store->id)->where('visit', 'BEFORE')->get()->toArray();
            $this->assertCount(1, $result);
            $this->assertSame('RS', $result[0]['app_cd']);
            $this->assertSame('2022-01-01 00:00:00', $result[0]['apply_term_from']);
            $this->assertSame('2999-12-31 00:00:00', $result[0]['apply_term_to']);
            $this->assertSame(1, $result[0]['cancel_limit']);
            $this->assertSame('DAY', $result[0]['cancel_limit_unit']);
            $this->assertSame(1000, $result[0]['cancel_fee']);
            $this->assertSame('FLAT_RATE', $result[0]['cancel_fee_unit']);
            $this->assertSame('ROUND_UP', $result[0]['fraction_round']);
            $this->assertSame(100000, $result[0]['cancel_fee_max']);
            $this->assertSame(100, $result[0]['cancel_fee_min']);
            $this->assertSame(0, $result[0]['published']);
        }

        // 来店後キャンセル
        {
            $response = $this->_callAdd($store, 'AFTER');
            $response->assertStatus(302);                                             // リダイレクト
            $response->assertRedirect('/admin/store/' . $store->id . '/cancel_fee');  // リダイレクト先
            $response->assertSessionHas('message', 'キャンセル料を登録しました。');

            $result = CancelFee::where('store_id', $store->id)->where('visit', 'AFTER')->get()->toArray();
            $this->assertCount(1, $result);
            $this->assertSame('RS', $result[0]['app_cd']);
            $this->assertSame('2022-01-01 00:00:00', $result[0]['apply_term_from']);
            $this->assertSame('2999-12-31 00:00:00', $result[0]['apply_term_to']);
            $this->assertNull($result[0]['cancel_limit']);
            $this->assertNull($result[0]['cancel_limit_unit']);
            $this->assertSame(100, $result[0]['cancel_fee']);                   // 100で固定で登録される
            $this->assertSame('FIXED_RATE', $result[0]['cancel_fee_unit']);     // FIXED_RATEで固定で登録される
            $this->assertSame('ROUND_UP', $result[0]['fraction_round']);
            $this->assertSame(100000, $result[0]['cancel_fee_max']);
            $this->assertSame(100, $result[0]['cancel_fee_min']);
            $this->assertSame(0, $result[0]['published']);
        }

        $this->logout();
    }

    public function testAddWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        // 来店前キャンセル(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            $response = $this->_callAdd($store, 'BEFORE');
            $response->assertStatus(302);                                             // リダイレクト
            $response->assertRedirect('/admin/store/' . $store->id . '/cancel_fee');  // リダイレクト先
            $response->assertSessionHas('message', 'キャンセル料を登録しました。');

            $result = CancelFee::where('store_id', $store->id)->where('visit', 'BEFORE')->get()->toArray();
            $this->assertCount(1, $result);
            $this->assertSame('RS', $result[0]['app_cd']);
            $this->assertSame('2022-01-01 00:00:00', $result[0]['apply_term_from']);
            $this->assertSame('2999-12-31 00:00:00', $result[0]['apply_term_to']);
            $this->assertSame(1, $result[0]['cancel_limit']);
            $this->assertSame('DAY', $result[0]['cancel_limit_unit']);
            $this->assertSame(1000, $result[0]['cancel_fee']);
            $this->assertSame('FLAT_RATE', $result[0]['cancel_fee_unit']);
            $this->assertSame('ROUND_UP', $result[0]['fraction_round']);
            $this->assertSame(100000, $result[0]['cancel_fee_max']);
            $this->assertSame(100, $result[0]['cancel_fee_min']);
            $this->assertSame(0, $result[0]['published']);
        }

        $this->logout();
    }

    public function testAddWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常に処理できること
        {
            // 来店前キャンセル(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
            {
                $response = $this->_callAdd($store, 'BEFORE');
                $response->assertStatus(302);                                             // リダイレクト
                $response->assertRedirect('/admin/store/' . $store->id . '/cancel_fee');  // リダイレクト先
                $response->assertSessionHas('message', 'キャンセル料を登録しました。');

                $result = CancelFee::where('store_id', $store->id)->where('visit', 'BEFORE')->get()->toArray();
                $this->assertCount(1, $result);
                $this->assertSame('RS', $result[0]['app_cd']);
                $this->assertSame('2022-01-01 00:00:00', $result[0]['apply_term_from']);
                $this->assertSame('2999-12-31 00:00:00', $result[0]['apply_term_to']);
                $this->assertSame(1, $result[0]['cancel_limit']);
                $this->assertSame('DAY', $result[0]['cancel_limit_unit']);
                $this->assertSame(1000, $result[0]['cancel_fee']);
                $this->assertSame('FLAT_RATE', $result[0]['cancel_fee_unit']);
                $this->assertSame('ROUND_UP', $result[0]['fraction_round']);
                $this->assertSame(100000, $result[0]['cancel_fee_max']);
                $this->assertSame(100, $result[0]['cancel_fee_min']);
                $this->assertSame(0, $result[0]['published']);
            }
        }

        // 担当外店舗の場合、正常に処理できないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callAdd($store2, 'BEFORE');
            $response->assertStatus(302);                                             // リダイレクト
            $response->assertRedirect('/admin/store/' . $store2->id . '/cancel_fee');  // リダイレクト先
            $response->assertSessionHas('custom_error', '登録に失敗しました。');
        }

        $this->logout();
    }

    public function testAddWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        // 来店前キャンセル(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            $response = $this->_callAdd($store, 'BEFORE');
            $response->assertStatus(302);                                             // リダイレクト
            $response->assertRedirect('/admin/store/' . $store->id . '/cancel_fee');  // リダイレクト先
            $response->assertSessionHas('message', 'キャンセル料を登録しました。');

            $result = CancelFee::where('store_id', $store->id)->where('visit', 'BEFORE')->get()->toArray();
            $this->assertCount(1, $result);
            $this->assertSame('RS', $result[0]['app_cd']);
            $this->assertSame('2022-01-01 00:00:00', $result[0]['apply_term_from']);
            $this->assertSame('2999-12-31 00:00:00', $result[0]['apply_term_to']);
            $this->assertSame(1, $result[0]['cancel_limit']);
            $this->assertSame('DAY', $result[0]['cancel_limit_unit']);
            $this->assertSame(1000, $result[0]['cancel_fee']);
            $this->assertSame('FLAT_RATE', $result[0]['cancel_fee_unit']);
            $this->assertSame('ROUND_UP', $result[0]['fraction_round']);
            $this->assertSame(100000, $result[0]['cancel_fee_max']);
            $this->assertSame(100, $result[0]['cancel_fee_min']);
            $this->assertSame(0, $result[0]['published']);
        }

        $this->logout();
    }

    public function testEditFormWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditForm($store, $cancelFee);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.CancelFee.edit');   // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'cancelFee',
            'storeCancelFeeConst',
        ]);                                                     // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('cancelFee', $cancelFee);
        $response->assertViewHas('storeCancelFeeConst', config('const.storeCancelFee'));

        $this->logout();
    }

    public function testEditFormWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callEditForm($store, $cancelFee);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.CancelFee.edit');   // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'cancelFee',
            'storeCancelFeeConst',
        ]);                                                     // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('cancelFee', $cancelFee);
        $response->assertViewHas('storeCancelFeeConst', config('const.storeCancelFee'));

        $this->logout();
    }

    public function testEditFormWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常にアクセスできること
        {
            $response = $this->_callEditForm($store, $cancelFee);
            $response->assertStatus(200);
            $response->assertViewIs('admin.Store.CancelFee.edit');   // 指定bladeを確認
            $response->assertViewHasAll([
                'store',
                'cancelFee',
                'storeCancelFeeConst',
            ]);                                                     // bladeに渡している変数を確認
            $response->assertViewHas('store', $store);
            $response->assertViewHas('cancelFee', $cancelFee);
            $response->assertViewHas('storeCancelFeeConst', config('const.storeCancelFee'));
        }

        // 担当外店舗の場合、アクセスできないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callEditForm($store2, $cancelFee);
            $response->assertStatus(403);
        }

        $this->logout();
    }

    public function testEditFormWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callEditForm($store, $cancelFee);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.CancelFee.edit');   // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'cancelFee',
            'storeCancelFeeConst',
        ]);                                                     // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('cancelFee', $cancelFee);
        $response->assertViewHas('storeCancelFeeConst', config('const.storeCancelFee'));

        $this->logout();
    }

    public function testEditWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        // 来店前キャンセル
        {
            $response = $this->_callEdit($store, 'BEFORE', $cancelFee);
            $response->assertStatus(302);                                             // リダイレクト
            $response->assertRedirect('/admin/store/' . $store->id . '/cancel_fee');  // リダイレクト先
            $response->assertSessionHas('message', 'キャンセル料を登録しました。');

            $result = CancelFee::where('store_id', $store->id)->where('visit', 'BEFORE')->get()->toArray();
            $this->assertCount(1, $result);
            $this->assertSame('RS', $result[0]['app_cd']);
            $this->assertSame('2023-01-01 00:00:00', $result[0]['apply_term_from']);
            $this->assertSame('2998-10-01 00:00:00', $result[0]['apply_term_to']);
            $this->assertSame(1, $result[0]['cancel_limit']);
            $this->assertSame('TIME', $result[0]['cancel_limit_unit']);
            $this->assertSame(1000, $result[0]['cancel_fee']);
            $this->assertSame('FLAT_RATE', $result[0]['cancel_fee_unit']);
            $this->assertSame('ROUND_UP', $result[0]['fraction_round']);
            $this->assertSame(50000, $result[0]['cancel_fee_max']);
            $this->assertSame(200, $result[0]['cancel_fee_min']);
            $this->assertSame(1, $result[0]['published']);
        }

        // 来店後キャンセル
        {
            $response = $this->_callEdit($store, 'AFTER', $cancelFee);
            $response->assertStatus(302);                                             // リダイレクト
            $response->assertRedirect('/admin/store/' . $store->id . '/cancel_fee');  // リダイレクト先
            $response->assertSessionHas('message', 'キャンセル料を登録しました。');

            $result = CancelFee::where('store_id', $store->id)->where('visit', 'AFTER')->get()->toArray();
            $this->assertCount(1, $result);
            $this->assertSame('RS', $result[0]['app_cd']);
            $this->assertSame('2023-01-01 00:00:00', $result[0]['apply_term_from']);
            $this->assertSame('2998-10-01 00:00:00', $result[0]['apply_term_to']);
            $this->assertNull($result[0]['cancel_limit']);
            $this->assertNull($result[0]['cancel_limit_unit']);
            $this->assertSame(100, $result[0]['cancel_fee']);                   // 100で固定で登録される
            $this->assertSame('FIXED_RATE', $result[0]['cancel_fee_unit']);     // FIXED_RATEで固定で登録される
            $this->assertSame('ROUND_UP', $result[0]['fraction_round']);
            $this->assertSame(50000, $result[0]['cancel_fee_max']);
            $this->assertSame(200, $result[0]['cancel_fee_min']);
            $this->assertSame(1, $result[0]['published']);
        }

        $this->logout();
    }

    public function testEditWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        // 来店前キャンセル(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            $response = $this->_callEdit($store, 'BEFORE', $cancelFee);
            $response->assertStatus(302);                                             // リダイレクト
            $response->assertRedirect('/admin/store/' . $store->id . '/cancel_fee');  // リダイレクト先
            $response->assertSessionHas('message', 'キャンセル料を登録しました。');

            $result = CancelFee::where('store_id', $store->id)->where('visit', 'BEFORE')->get()->toArray();
            $this->assertCount(1, $result);
            $this->assertSame('RS', $result[0]['app_cd']);
            $this->assertSame('2023-01-01 00:00:00', $result[0]['apply_term_from']);
            $this->assertSame('2998-10-01 00:00:00', $result[0]['apply_term_to']);
            $this->assertSame(1, $result[0]['cancel_limit']);
            $this->assertSame('TIME', $result[0]['cancel_limit_unit']);
            $this->assertSame(1000, $result[0]['cancel_fee']);
            $this->assertSame('FLAT_RATE', $result[0]['cancel_fee_unit']);
            $this->assertSame('ROUND_UP', $result[0]['fraction_round']);
            $this->assertSame(50000, $result[0]['cancel_fee_max']);
            $this->assertSame(200, $result[0]['cancel_fee_min']);
            $this->assertSame(1, $result[0]['published']);
        }

        $this->logout();
    }

    public function testEditWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常に処理できること
        {
            // 来店前キャンセル(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
            {
                $response = $this->_callEdit($store, 'BEFORE', $cancelFee);
                $response->assertStatus(302);                                             // リダイレクト
                $response->assertRedirect('/admin/store/' . $store->id . '/cancel_fee');  // リダイレクト先
                $response->assertSessionHas('message', 'キャンセル料を登録しました。');

                $result = CancelFee::where('store_id', $store->id)->where('visit', 'BEFORE')->get()->toArray();
                $this->assertCount(1, $result);
                $this->assertSame('RS', $result[0]['app_cd']);
                $this->assertSame('2023-01-01 00:00:00', $result[0]['apply_term_from']);
                $this->assertSame('2998-10-01 00:00:00', $result[0]['apply_term_to']);
                $this->assertSame(1, $result[0]['cancel_limit']);
                $this->assertSame('TIME', $result[0]['cancel_limit_unit']);
                $this->assertSame(1000, $result[0]['cancel_fee']);
                $this->assertSame('FLAT_RATE', $result[0]['cancel_fee_unit']);
                $this->assertSame('ROUND_UP', $result[0]['fraction_round']);
                $this->assertSame(50000, $result[0]['cancel_fee_max']);
                $this->assertSame(200, $result[0]['cancel_fee_min']);
                $this->assertSame(1, $result[0]['published']);
            }
        }

        // 担当外店舗の場合、正常に処理できないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callEdit($store2, 'BEFORE', $cancelFee);
            $response->assertStatus(302);                                             // リダイレクト
            $response->assertRedirect('/admin/store/' . $store2->id . '/cancel_fee');  // リダイレクト先
            $response->assertSessionHas('custom_error', '更新に失敗しました。');
        }

        $this->logout();
    }

    public function testEditWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        // 来店前キャンセル(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            $response = $this->_callEdit($store, 'BEFORE', $cancelFee);
            $response->assertStatus(302);                                             // リダイレクト
            $response->assertRedirect('/admin/store/' . $store->id . '/cancel_fee');  // リダイレクト先
            $response->assertSessionHas('message', 'キャンセル料を登録しました。');

            $result = CancelFee::where('store_id', $store->id)->where('visit', 'BEFORE')->get()->toArray();
            $this->assertCount(1, $result);
            $this->assertSame('RS', $result[0]['app_cd']);
            $this->assertSame('2023-01-01 00:00:00', $result[0]['apply_term_from']);
            $this->assertSame('2998-10-01 00:00:00', $result[0]['apply_term_to']);
            $this->assertSame(1, $result[0]['cancel_limit']);
            $this->assertSame('TIME', $result[0]['cancel_limit_unit']);
            $this->assertSame(1000, $result[0]['cancel_fee']);
            $this->assertSame('FLAT_RATE', $result[0]['cancel_fee_unit']);
            $this->assertSame('ROUND_UP', $result[0]['fraction_round']);
            $this->assertSame(50000, $result[0]['cancel_fee_max']);
            $this->assertSame(200, $result[0]['cancel_fee_min']);
            $this->assertSame(1, $result[0]['published']);
        }

        $this->logout();
    }

    public function testDeleteWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callDelete($store);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 削除されていることを確認する
        $this->assertFalse(CancelFee::where('store_id', $store->id)->where('visit', 'BEFORE')->exists());

        $this->logout();
    }

    public function testDeleteWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callDelete($store);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 削除されていることを確認する
        $this->assertFalse(CancelFee::where('store_id', $store->id)->where('visit', 'BEFORE')->exists());

        $this->logout();
    }

    public function testDeleteWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常に処理できること
        {
            $response = $this->_callDelete($store);
            $response->assertStatus(200)->assertJson(['result' => 'ok']);

            // 削除されていることを確認する
            $this->assertFalse(CancelFee::where('store_id', $store->id)->where('visit', 'BEFORE')->exists());
        }

        // 担当外店舗の場合、正常に処理できないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callDelete($store2);
            $response->assertStatus(500);
        }

        $this->logout();
    }

    public function testDeleteWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callDelete($store);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 削除されていることを確認する
        $this->assertFalse(CancelFee::where('store_id', $store->id)->where('visit', 'BEFORE')->exists());

        $this->logout();
    }

    public function testStoreCancelFeeControllerWithClientGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithClientGeneral($store->id, $settlementCompanyId);      // クライアント一般としてログイン

        // target method index
        $response = $this->_callIndex($store);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm($store);
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($store, 'BEFORE');
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($store, $cancelFee);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($store, 'BEFORE', $cancelFee);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($store);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testStoreCancelFeeControllerWithSettlementAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithSettlementAdministrator($settlementCompany->id);    // 精算管理会社としてログイン

        // target method index
        $response = $this->_callIndex($store);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm($store);
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($store, 'BEFORE');
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($store, $cancelFee);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($store, 'BEFORE', $cancelFee);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($store);
        $response->assertStatus(404);

        $this->logout();
    }

    private function _createSettlementCompany()
    {
        $settlementCompany = new SettlementCompany();
        $settlementCompany->name = 'testテストtest精算会社';
        $settlementCompany->tel = '0698765432';
        $settlementCompany->postal_code = '1111123';
        $settlementCompany->published = 1;
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

    private function _createCancelFee($storeId, $visit, $applyTermFrom = '2022-01-01', $applyTermTo = '2099-12-31')
    {
        $cancelFee = new CancelFee();
        $cancelFee->store_id = $storeId;
        $cancelFee->app_cd = 'RS';
        $cancelFee->apply_term_from = $applyTermFrom;
        $cancelFee->apply_term_to = $applyTermTo;
        $cancelFee->visit = $visit;
        $cancelFee->fraction_round = 'ROUND_UP';
        $cancelFee->cancel_fee_max = 100000;
        $cancelFee->cancel_fee_min = 100;
        $cancelFee->published = 1;
        if ($visit == 'BEFORE') {
            $cancelFee->cancel_limit = 1;
            $cancelFee->cancel_limit_unit = 'DAY';
            $cancelFee->cancel_fee = 100;
            $cancelFee->cancel_fee_unit = 'FIXED_RATE';
        }
        $cancelFee->save();
        return $cancelFee;
    }

    private function _callIndex($store)
    {
        $cancelFee = $this->_createCancelFee($store->id, 'BEFORE');
        return $this->withHeaders([
            'HTTP_REFERER' =>  url('/admin/store?page=1'),
        ])->get('/admin/store/' . $store->id . '/cancel_fee');
    }

    private function _callAddForm($store)
    {
        return $this->get('/admin/store/' . $store->id . '/cancel_fee/add');
    }

    private function _callAdd($store, $visit)
    {
        return $this->post('/admin/store/cancel_fee/add', [
            'store_id' => $store->id,
            'app_cd' => 'RS',
            'apply_term_from' => '2022/01/01',
            'apply_term_to' => '2999/12/31',
            'visit' => $visit,
            'cancel_limit' => 1.0,
            'cancel_limit_unit' => 'DAY',
            'cancel_fee' => 1000.0,
            'cancel_fee_unit' => 'FLAT_RATE',
            'fraction_unit' => 1.0,
            'fraction_round' => 'ROUND_UP',
            'cancel_fee_max' => 100000.0,
            'cancel_fee_min' => 100.0,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditForm($store, &$cancelFee)
    {
        $cancelFee = $this->_createCancelFee($store->id, 'BEFORE');
        return $this->get("/admin/store/{$store->id}/cancel_fee/{$cancelFee->id}/edit");
    }

    private function _callEdit($store, $visit, &$cancelFee)
    {
        $cancelFee = $this->_createCancelFee($store->id, $visit);
        return $this->post("/admin/store/cancel_fee/{$cancelFee->id}/edit", [
            'store_id' => $store->id,
            'app_cd' => 'RS',
            'apply_term_from' => '2023/01/01',
            'apply_term_to' => '2998/10/01',
            'visit' => $visit,
            'cancel_limit' => 1.0,
            'cancel_limit_unit' => 'TIME',
            'cancel_fee' => 1000.0,
            'cancel_fee_unit' => 'FLAT_RATE',
            'fraction_unit' => 1.0,
            'fraction_round' => 'ROUND_UP',
            'cancel_fee_max' => 50000.0,
            'cancel_fee_min' => 200.0,
            'published' => 1,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callDelete($store)
    {
        $cancelFee = $this->_createCancelFee($store->id, 'BEFORE');
        return $this->post("/admin/store/{$store->id}/cancel_fee/{$cancelFee->id}/delete", [
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }
}
