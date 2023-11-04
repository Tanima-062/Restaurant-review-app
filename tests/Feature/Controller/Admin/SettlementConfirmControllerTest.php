<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\SettlementCompany;
use App\Models\SettlementDownload;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class SettlementConfirmControllerTest extends TestCase
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

        $response = $this->_callIndex($settlementCompany);
        $response->assertStatus(200);
        $response->assertViewIs('admin.SettlementConfirm.index'); // 指定bladeを確認
        $response->assertViewHasAll([
            'settlementCompanies',
            'settlementDownloads',
            'settlementYYYYmm',
            'settlementType',
        ]);                                                     // bladeに渡している変数を確認
        $response->assertViewHas('settlementType', [
            'INVOICE' => ['value' => 'INVOICE', 'label' => '支払'],
            'RECEIPT' => ['value' => 'RECEIPT', 'label' => '請求'],
        ]);
        $settlementDownloads = SettlementDownload::with('settlementCompany')->get()
            ->filter(function ($settlementCompany) {
                return strpos($settlementCompany->name, 'testテストtest精算会社') !== false;
            });
        $response->assertViewHas('settlementDownloads', $settlementDownloads);

        $this->logout();
    }

    public function testIndexWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();                        // 社内一般としてログイン

        $response = $this->_callIndex($settlementCompany);
        $response->assertStatus(200);
        $response->assertViewIs('admin.SettlementConfirm.index'); // 指定bladeを確認
        $response->assertViewHasAll([
            'settlementCompanies',
            'settlementDownloads',
            'settlementYYYYmm',
            'settlementType',
        ]);                                                     // bladeに渡している変数を確認
        $response->assertViewHas('settlementType', [
            'INVOICE' => ['value' => 'INVOICE', 'label' => '支払'],
            'RECEIPT' => ['value' => 'RECEIPT', 'label' => '請求'],
        ]);
        $settlementDownloads = SettlementDownload::with('settlementCompany')->get()
            ->filter(function ($settlementCompany) {
                return strpos($settlementCompany->name, 'testテストtest精算会社') !== false;
            });
        $response->assertViewHas('settlementDownloads', $settlementDownloads);

        $this->logout();
    }

    public function testIndexWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        $response = $this->_callIndex($settlementCompany);
        $response->assertStatus(200);
        $response->assertViewIs('admin.SettlementConfirm.index'); // 指定bladeを確認
        $response->assertViewHasAll([
            'settlementCompanies',
            'settlementDownloads',
            'settlementYYYYmm',
            'settlementType',
        ]);                                                     // bladeに渡している変数を確認
        $response->assertViewHas('settlementType', [
            'INVOICE' => ['value' => 'INVOICE', 'label' => '支払'],
            'RECEIPT' => ['value' => 'RECEIPT', 'label' => '請求'],
        ]);
        $settlementDownloads = SettlementDownload::with('settlementCompany')->get()
            ->filter(function ($settlementCompany) {
                return strpos($settlementCompany->name, 'testテストtest精算会社') !== false;
            });
        $response->assertViewHas('settlementDownloads', $settlementDownloads);

        $this->logout();
    }

    public function testIndexWithSettlementAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $this->loginWithSettlementAdministrator($settlementCompany->id);    // 精算管理会社としてログイン

        $response = $this->_callIndex($settlementCompany);
        $response->assertStatus(200);
        $response->assertViewIs('admin.SettlementConfirm.index'); // 指定bladeを確認
        $response->assertViewHasAll([
            'settlementCompanies',
            'settlementDownloads',
            'settlementYYYYmm',
            'settlementType',
        ]);                                                     // bladeに渡している変数を確認
        $response->assertViewHas('settlementType', [
            'INVOICE' => ['value' => 'INVOICE', 'label' => '支払'],
            'RECEIPT' => ['value' => 'RECEIPT', 'label' => '請求'],
        ]);
        $settlementDownloads = SettlementDownload::with('settlementCompany')->get()
            ->filter(function ($settlementCompany) {
                return strpos($settlementCompany->name, 'testテストtest精算会社') !== false;
            });
        $response->assertViewHas('settlementDownloads', $settlementDownloads);

        $this->logout();
    }

    public function testPdfDownloadWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        $response = $this->_callPdfDownload('209910', $settlementCompany);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', 'attachment; filename="' . $settlementCompany->id . '_209910_invoice.pdf"');

        $this->logout();
    }

    public function testPdfDownloadWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();                        // 社内一般としてログイン

        $response = $this->_callPdfDownload('209910', $settlementCompany);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', 'attachment; filename="' . $settlementCompany->id . '_209910_invoice.pdf"');

        $this->logout();
    }

    public function testPdfDownloadWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        $response = $this->_callPdfDownload('209910', $settlementCompany);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', 'attachment; filename="' . $settlementCompany->id . '_209910_invoice.pdf"');

        $this->logout();
    }

    public function testPdfDownloadSettlementAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $this->loginWithSettlementAdministrator($settlementCompany->id);    // 精算管理会社としてログイン

        $response = $this->_callPdfDownload('209910', $settlementCompany);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', 'attachment; filename="' . $settlementCompany->id . '_209910_invoice.pdf"');

        $this->logout();
    }

    public function testPdfDownloadAuthError()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id);         // クライアント管理者としてログイン(精算会社情報と紐付けしない)

        $response = $this->_callPdfDownload('209910', $settlementCompany);
        $response->assertStatus(403);

        $this->logout();
    }

    public function testPdfDownloadNotSettlementDownload()
    {
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        $response = $this->_callPdfDownload('209910', $settlementCompany, false);   // 精算ダウンロードデータを作成しない
        $response->assertStatus(404);

        $this->logout();
    }

    public function testSettlementConfirmControllerWithClientGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientGeneral($store->id, $settlementCompany->id);              // クライアント一般としてログイン

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method pdfDownload
        $response = $this->_callPdfDownload('209910', $settlementCompany);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testSettlementConfirmControllerWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        // target method index
        $response = $this->_callIndex($settlementCompany);
        $response->assertStatus(404);

        // target method pdfDownload
        $response = $this->_callPdfDownload('209910', $settlementCompany);
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

    private function _createSettlementDownload($settlementCompanyId, $month = '209910')
    {
        $settlementDownload = new SettlementDownload();
        $settlementDownload->settlement_company_id = $settlementCompanyId;
        $settlementDownload->month = $month;
        $settlementDownload->start_term = '1';
        $settlementDownload->end_term = '30';
        $settlementDownload->type = 'INVOICE';
        $settlementDownload->commission_rate_rs = 10.0;
        $settlementDownload->accounting_condition_rs = 'FIXED_RATE';
        $settlementDownload->commission_rate_to = 10.0;
        $settlementDownload->accounting_condition_to = 'FIXED_RATE';
        $settlementDownload->pdf_url = 'settlement_confirm/pdf_download?month=209910&settlement_company_id=' . $settlementCompanyId;
        $settlementDownload->save();
        return $settlementDownload;
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

    private function _callIndex(&$settlementCompany = null)
    {
        if (is_null($settlementCompany)) {
            $settlementCompany = $this->_createSettlementCompany();
        }
        $this->_createSettlementDownload($settlementCompany->id);
        return $this->get('/admin/settlement_confirm?settlementCompanyName=testテストtest精算会社');
    }

    private function _callPdfDownload($month, &$settlementCompany = null, $addSettlementDownload = true)
    {
        if (is_null($settlementCompany)) {
            $settlementCompany = $this->_createSettlementCompany();
        }
        if ($addSettlementDownload) {
            $this->_createSettlementDownload($settlementCompany->id, $month);
        }
        return $this->get('/admin/settlement_confirm/pdf_download?settlement_company_id=' . $settlementCompany->id . '&month=' . $month);
    }
}
