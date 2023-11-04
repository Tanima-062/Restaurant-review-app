<?php

namespace Tests\Unit\Models;

use App\Libs\Cipher;
use App\Models\MailDBQueue;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MailDBQueueTest extends TestCase
{
    private $mailDBQueue;
    private $testMailDBQueueId = 275415;    // 開発環境に登録されているレコード
    private $testUserId = 10638910;         // 開発環境に登録されているレコード

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->mailDBQueue = new MailDBQueue();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testGetEncAttribute()
    {
        // getFromAddressEncAttribute
        $str = Cipher::encrypt('gourmet-test@adventure-inc.co.jp');
        $this->assertSame('gourmet-test@adventure-inc.co.jp', $this->mailDBQueue->getFromAddressEncAttribute($str));

        // getSearchFromAddressEncAttribute
        $str = Cipher::encrypt('gourmet-test@adventure-inc.co.jp');
        $this->assertSame('gourmet-test@adventure-inc.co.jp', $this->mailDBQueue->getSearchFromAddressEncAttribute($str));

        // getToAddressEncAttribute
        $str = Cipher::encrypt('gourmet-test@adventure-inc.co.jp');
        $this->assertSame('gourmet-test@adventure-inc.co.jp', $this->mailDBQueue->getToAddressEncAttribute($str));

        // getSearchToAddressEncAttribute
        $str = Cipher::encrypt('gourmet-test@adventure-inc.co.jp');
        $this->assertSame('gourmet-test@adventure-inc.co.jp', $this->mailDBQueue->getSearchToAddressEncAttribute($str));

        // getMessageEncAttribute
        $str = Cipher::encrypt('テストメッセージ');
        $this->assertSame('テストメッセージ', $this->mailDBQueue->getMessageEncAttribute($str));

        // getAdditionalHeadersEncAttribute
        $str = Cipher::encrypt('a:1:{s:7:"charset";s:11:"ISO-2022-JP";}');
        $this->assertSame('a:1:{s:7:"charset";s:11:"ISO-2022-JP";}', $this->mailDBQueue->getAdditionalHeadersEncAttribute($str));

        // getAdditionalParametersEncAttribute
        $str = Cipher::encrypt('a:1:{s:7:"charset";s:11:"ISO-2022-JP";}');
        $this->assertSame('a:1:{s:7:"charset";s:11:"ISO-2022-JP";}', $this->mailDBQueue->getAdditionalParametersEncAttribute($str));
    }

    public function testSetAttribute()
    {
        // user_idを変更
        $mailDBQueue = $this->mailDBQueue::find($this->testMailDBQueueId);
        $mailDBQueue->setUserIdAttribute(1000);
        $this->assertSame(1000, $mailDBQueue->user_id);

        // cm_application_idを変更
        $mailDBQueue = $this->mailDBQueue::find($this->testMailDBQueueId);
        $mailDBQueue->setCmApplicationIdAttribute(500);
        $this->assertSame(500, $mailDBQueue->cm_application_id);

        // to_address_encを変更
        $mailDBQueue = $this->mailDBQueue::find($this->testMailDBQueueId);
        $mailDBQueue->setToAddressEncAttribute('y-iwane+test@adventure-inc.co.jp');
        $this->assertSame('y-iwane+test@adventure-inc.co.jp', $mailDBQueue->to_address_enc);

        // search_to_address_encを変更
        $mailDBQueue = $this->mailDBQueue::find($this->testMailDBQueueId);
        $mailDBQueue->setSearchToAddressEncAttribute('y-iwane+test@adventure-inc.co.jp');
        $this->assertSame('y-iwane+test@adventure-inc.co.jp', $mailDBQueue->search_to_address_enc);

        // message_encを変更
        $mailDBQueue = $this->mailDBQueue::find($this->testMailDBQueueId);
        $mailDBQueue->setMessageEncAttribute('テストメッセージ');
        $this->assertSame('テストメッセージ', $mailDBQueue->message_enc);

        // additional_headers_encを変更
        $mailDBQueue = $this->mailDBQueue::find($this->testMailDBQueueId);
        $mailDBQueue->setAdditionalHeadersEncAttribute('a:2:{s:7:"charset";s:5:"UTF-8";s:4:"From";s:21:"gourmet+test@skyticket.com";}');
        $this->assertSame('a:2:{s:7:"charset";s:5:"UTF-8";s:4:"From";s:21:"gourmet+test@skyticket.com";}', $mailDBQueue->additional_headers_enc);

        // additional_parameters_encを変更
        $mailDBQueue = $this->mailDBQueue::find($this->testMailDBQueueId);
        $mailDBQueue->setAdditionalParametersEncAttribute('a:2:{s:7:"charset";s:5:"UTF-8";s:4:"From";s:21:"gourmet+test@skyticket.com";}');
        $this->assertSame('a:2:{s:7:"charset";s:5:"UTF-8";s:4:"From";s:21:"gourmet+test@skyticket.com";}', $mailDBQueue->additional_parameters_enc);
    }

    public function testScopeUnreadMail()
    {
        $result = $this->mailDBQueue::UnreadMail($this->testUserId)->get();
        $this->assertIsObject($result);
        $this->assertSame($this->testUserId, $result[0]['user_id']);
    }

    public function testInQueue()
    {
        $reservationId = 3079;  // 開発環境に登録されているレコード
        $addressFrom = 'y-iwane+test@adventure-inc.co.jp';
        $mailDBQueue = $this->mailDBQueue::find($this->testMailDBQueueId);
        $result = $mailDBQueue->inQueue($reservationId, $addressFrom);
        $this->assertTrue($result);

        // 更新されているか確認
        $mailDBQueue = $this->mailDBQueue::find($this->testMailDBQueueId);
        $this->assertSame($reservationId, $mailDBQueue->application_id);
        $this->assertSame($addressFrom, $mailDBQueue->from_address_enc);
        $this->assertSame($addressFrom, $mailDBQueue->search_from_address_enc);
    }
}
