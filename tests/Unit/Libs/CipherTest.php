<?php

namespace Tests\Unit\Libs;

use App\Libs\Cipher;
use Tests\TestCase;

class CipherTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testEncrypt()
    {
        $this->assertSame('ETmGE9vFH0cM1VLveDlNfHut0n/kxtgY', Cipher::encrypt('test-gourmet_1234'));
        $this->assertSame('', Cipher::encrypt('')); // 空文字の場合はからっ文字が返ってくる
    }

    public function testDecrypt()
    {
        $this->assertSame('test-gourmet_1234', Cipher::decrypt('ETmGE9vFH0cM1VLveDlNfHut0n/kxtgY'));
    }
}
