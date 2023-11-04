<?php

namespace Tests\Unit\Commands;

use App\Console\Commands\BaseCommandTrait;
use Tests\TestCase;

class BaseCommandTraitTest extends TestCase
{
    use BaseCommandTrait;

    private $className;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testGetClassName()
    {
        $this->className = $this->getClassName($this);
        $className = $this->getClassName($this);
        $this->assertSame($this->className, $className);

        $this->assertSame('[BaseCommandTraitTest] ', $this->logPrefix());
    }
}
