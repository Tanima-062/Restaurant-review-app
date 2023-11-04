<?php

namespace Tests\Unit\Models;

use App\Models\FaxSendJob;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FaxSendJobTest extends TestCase
{
    private $faxSendJob;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->faxSendJob = new FaxSendJob();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testScopeReady()
    {
        $result = $this->faxSendJob::Ready()->get();
        $this->assertIsObject($result);
    }
}
