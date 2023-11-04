<?php

namespace Tests\Feature\Controller\Admin;

use App\Http\Requests\Admin\NoticeRequest;
use App\Models\Notice;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class NoticeControllerTest extends TestCase
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

    public function testIndex()
    {
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);                         // アクセス確認
        $response->assertViewIs('admin.Notice.index');        // 指定bladeを確認
        $response->assertViewHasAll(['notices', 'staffs']);   // bladeに渡している変数を確認

        $this->logout();
    }

    public function testEditForm()
    {
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        $response = $this->_callEditForm();
        $response->assertStatus(200);                       // アクセス確認
        $response->assertViewIs('admin.Notice.edit');       // 指定bladeを確認
        $response->assertViewHasAll(['notice']);   // bladeに渡している変数を確認

        $this->logout();
    }

    public function testEdit()
    {
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        $response = $this->_callEdit($notice);
        $response->assertStatus(302);                       // リダイレクト
        $response->assertRedirect('/admin/notice/');        // リダイレクト先
        $response->assertSessionHas('message', 'お知らせ「更新テストお知らせタイトル」を更新しました。');

        // 更新されていることを確認
        $result = Notice::find($notice->id);
        $this->assertNotNull($result);
        $this->assertSame('更新テストお知らせタイトル', $result->title);
        $this->assertSame('更新テストお知らせ内容', $result->message);
        $this->assertSame('2022-10-01 09:00:00', $result->datetime_from);
        $this->assertSame('2099-12-01 00:00:00', $result->datetime_to);
        $this->assertSame(1, $result->ui_website_flg);
        $this->assertSame(1, $result->ui_admin_flg);
        $this->assertSame(1, $result->published);
        $this->assertSame('2022-10-01 12:00:00', $result->published_at);

        $this->logout();
    }

    public function testEditThrowable()
    {
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        //NoticeRequestのinput('app_cd')呼び出しで例外発生させるようにする
        $noticeRequest = \Mockery::mock(NoticeRequest::class)->makePartial();
        $noticeRequest->shouldReceive('input')->once()->with('app_cd')->andThrow(new \Exception());
        $noticeRequest->shouldReceive('get')->andReturn('/admin/notice');
        $noticeRequest->shouldReceive('input')->andReturn('/admin/notice');
        $noticeRequest->shouldReceive('all')->andReturn(['redirect_to' => '/admin/notice', 'title' => '更新テストお知らせタイトル']);
        $this->app->instance(NoticeRequest::class, $noticeRequest);

        $response = $this->_callEdit($notice);
        $response->assertStatus(302);                       // リダイレクト
        $response->assertRedirect('/admin/notice/');        // リダイレクト先
        $response->assertSessionHas('custom_error', 'お知らせ「更新テストお知らせタイトル」を更新できませんでした。');

        // 更新されていないことを確認
        $result = Notice::find($notice->id);
        $this->assertNotNull($result);
        $this->assertSame('テストお知らせタイトル', $result->title);
        $this->assertSame('テストお知らせ内容', $result->message);
        $this->assertSame('2020-01-01 12:34:56', $result->datetime_from);
        $this->assertSame('2020-01-01 12:34:56', $result->datetime_to);
        $this->assertSame(0, $result->ui_website_flg);
        $this->assertSame(0, $result->ui_admin_flg);
        $this->assertSame(0, $result->published);
        $this->assertSame('2020-01-01 12:34:56', $result->published_at);

        $this->logout();
    }

    public function testAddForm()
    {
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        $response = $this->_callAddForm();
        $response->assertStatus(200);                       // アクセス確認
        $response->assertViewIs('admin.Notice.add');       // 指定bladeを確認

        $this->logout();
    }

    public function testAdd()
    {
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        $this->assertFalse(Notice::where('title', '追加テストお知らせタイトル')->exists());

        $response = $this->_callAdd();
        $response->assertStatus(302);                       // リダイレクト
        $response->assertRedirect('/admin/notice/');        // リダイレクト先
        $response->assertSessionHas('message', 'お知らせ「追加テストお知らせタイトル」を作成しました');

        // 追加されていることを確認
        $result = Notice::where('title', '追加テストお知らせタイトル')->get();
        $this->assertCount(1, $result);
        $this->assertSame('追加テストお知らせ内容', $result[0]['message']);
        $this->assertSame('2022-10-01 09:00:00', $result[0]['datetime_from']);
        $this->assertSame('2099-12-01 00:00:00', $result[0]['datetime_to']);
        $this->assertSame(1, $result[0]['ui_website_flg']);
        $this->assertSame(1, $result[0]['ui_admin_flg']);
        $this->assertSame(1, $result[0]['published']);
        $this->assertSame('2022-10-01 12:00:00', $result[0]['published_at']);

        $this->logout();
    }

    public function testAddThrowable()
    {
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        $this->assertFalse(Notice::where('title', '追加テストお知らせタイトル')->exists());

        //NoticeRequestのinput('app_cd')呼び出しで例外発生させるようにする
        $noticeRequest = \Mockery::mock(NoticeRequest::class)->makePartial();
        $noticeRequest->shouldReceive('input')->once()->with('app_cd')->andThrow(new \Exception());
        $noticeRequest->shouldReceive('get')->andReturn('/admin/notice');
        $noticeRequest->shouldReceive('input')->andReturn('/admin/notice');
        $noticeRequest->shouldReceive('validated')->andReturn('');
        $noticeRequest->shouldReceive('all')->andReturn(['redirect_to' => '/admin/notice', 'title' => '追加テストお知らせタイトル']);
        $this->app->instance(NoticeRequest::class, $noticeRequest);

        $response = $this->_callAdd();
        $response->assertStatus(302);                       // リダイレクト
        $response->assertRedirect('/admin/notice/');        // リダイレクト先
        $response->assertSessionHas('custom_error', 'お知らせ「追加テストお知らせタイトル」を作成できませんでした');

        // 追加されていないことを確認
        $this->assertFalse(Notice::where('title', '追加テストお知らせタイトル')->exists());

        $this->logout();
    }

    public function testNoticeControllerWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm();
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($notice);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(404);

        $this->logout();
    }

    public function testNoticeControllerWithClientAdministrator()
    {
        $store = $this->_createStore();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm();
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($notice);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(404);

        $this->logout();
    }

    public function testNoticeControllerWithClientGeneral()
    {
        $store = $this->_createStore();
        $this->loginWithClientGeneral($store->id);            // クライアント一般としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm();
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($notice);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(404);

        $this->logout();
    }

    public function testNoticeControllerWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm();
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($notice);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(404);

        $this->logout();
    }

    public function testNoticeControllerWithSettlementAdministrator()
    {
        $this->loginWithSettlementAdministrator();            // 精算管理会社としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm();
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($notice);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(404);

        $this->logout();
    }

    private function _createStore($published = 1)
    {
        $store = new Store();
        $store->app_cd = 'TORS';
        $store->name = 'テスト店舗';
        $store->published = $published;
        $store->save();
        return $store;
    }

    private function _createNotice()
    {
        $notice = new Notice();
        $notice->app_cd = 'RS';
        $notice->title = 'テストお知らせタイトル';
        $notice->message = 'テストお知らせ内容';
        $notice->datetime_from = '2020-01-01 12:34:56';
        $notice->datetime_to = '2020-01-01 12:34:56';
        $notice->published = 0;
        $notice->published_at = '2020-01-01 12:34:56';
        $notice->ui_website_flg = 0;
        $notice->ui_admin_flg = 0;
        $notice->save();

        return $notice;
    }

    private function _callIndex()
    {
        return $this->get('/admin/notice/');
    }

    private function _callEditForm()
    {
        $notice = $this->_createNotice();
        return $this->get('/admin/notice/edit/' . $notice->id);
    }

    private function _callEdit(&$notice)
    {
        $notice = $this->_createNotice();
        return $this->post('/admin/notice/edit/' . $notice->id, [
            'app_cd' => 'RS',
            'title' => '更新テストお知らせタイトル',
            'message' => '更新テストお知らせ内容',
            'datetime_from' => '2022-10-01 09:00:00',
            'datetime_to' => '2099-12-01 00:00:00',
            'ui_website_flg' => '1',
            'ui_admin_flg' => '1',
            'published' => '1',
            'published_at' => '2022-10-01 12:00:00',
            'redirect_to' => '/admin/notice',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddForm()
    {
        return $this->get('/admin/notice/add');
    }

    private function _callAdd()
    {
        return $this->post('/admin/notice/add', [
            'app_cd' => 'RS',
            'title' => '追加テストお知らせタイトル',
            'message' => '追加テストお知らせ内容',
            'datetime_from' => '2022-10-01 09:00:00',
            'datetime_to' => '2099-12-01 00:00:00',
            'ui_website_flg' => '1',
            'ui_admin_flg' => '1',
            'published' => '1',
            'published_at' => '2022-10-01 12:00:00',
            'redirect_to' => '/admin/notice',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }
}
