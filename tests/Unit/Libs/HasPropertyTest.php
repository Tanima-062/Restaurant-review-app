<?php

namespace Tests\Unit\Libs;

use App\Libs\HasProperty;
use Tests\TestCase;

class HasPropertyTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testImplodedString()
    {
        // カンマ区切りの文字列に指定文字（JCB）含まれている
        $this->assertTrue(HasProperty::implodedString('JCB', 'VISA,MASTER,JCB'));
        // カンマ区切りの文字列に指定文字（JCB）含まれていない
        $this->assertFalse(HasProperty::implodedString('JCB', 'VISA,MASTER,OTHER'));
        //  配列に指定文字（JCB）含まれている
        $this->assertTrue(HasProperty::implodedString('JCB', ['VISA','MASTER','JCB']));
        //  配列に指定文字（JCB）含まれていない
        $this->assertFalse(HasProperty::implodedString('JCB', ['VISA','MASTER','OTHER']));
    }
}
