<?php

namespace Tests\Unit\Libs;

use App\Libs\HttpHeader;
use Tests\TestCase;

class HttpHeaderTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testGetPublicCacheConfig()
    {
        // キャッシュ期間、最終更新日時の指定なし
        $result = HttpHeader::getPublicCacheConfig();
        $this->assertIsArray($result);
        $this->assertCount(4, $result);
        $this->assertArrayHasKey('Expires', $result);
        $this->assertSame(-1, $result['Expires']);
        $this->assertArrayHasKey('Pragma', $result);
        $this->assertSame('', $result['Pragma']);
        $this->assertArrayHasKey('Cache-Control', $result);
        $this->assertSame('public, max-age=1800', $result['Cache-Control']);                    // キャッシュ期間は1800であること
        $this->assertArrayHasKey('Last-Modified', $result);
        $this->assertSame(gmdate('D, d M Y H:i:s', time()).' GMT', $result['Last-Modified']);   // 最終更新日は現在日時と一致すること

        // キャッシュ期間の指定あり、最終更新日時の指定なし
        $result = HttpHeader::getPublicCacheConfig(900);                                        // キャッシュ期間を900に指定
        $this->assertIsArray($result);
        $this->assertCount(4, $result);
        $this->assertArrayHasKey('Expires', $result);
        $this->assertSame(-1, $result['Expires']);
        $this->assertArrayHasKey('Pragma', $result);
        $this->assertSame('', $result['Pragma']);
        $this->assertArrayHasKey('Cache-Control', $result);
        $this->assertSame('public, max-age=900', $result['Cache-Control']);                     // キャッシュ期間が指定通りであること
        $this->assertArrayHasKey('Last-Modified', $result);
        $this->assertSame(gmdate('D, d M Y H:i:s', time()).' GMT', $result['Last-Modified']);   // 最終更新日は現在日時と一致すること

        // キャッシュ期間の指定なし、最終更新日時の指定あり
        $result = HttpHeader::getPublicCacheConfig(null, 1800000000);                           // 最終更新日を2027/01/15 Fri 08:00：00に指定
        $this->assertIsArray($result);
        $this->assertCount(4, $result);
        $this->assertArrayHasKey('Expires', $result);
        $this->assertSame(-1, $result['Expires']);
        $this->assertArrayHasKey('Pragma', $result);
        $this->assertSame('', $result['Pragma']);
        $this->assertArrayHasKey('Cache-Control', $result);
        $this->assertSame('public, max-age=1800', $result['Cache-Control']);                    // キャッシュ期間は1800であること
        $this->assertArrayHasKey('Last-Modified', $result);
        $this->assertSame('Fri, 15 Jan 2027 08:00:00 GMT', $result['Last-Modified']);           // 最終更新日は指定した日時であること
    }
}
