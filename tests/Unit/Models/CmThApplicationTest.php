<?php

namespace Tests\Unit\Models;

use App\Models\CmThApplication;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CmThApplicationTest extends TestCase
{
    private $cmThApplication;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->cmThApplication = new CmThApplication();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testCreateEmptyApplication()
    {
        // 該当データあり
        list($cmApplicationId, $userId) = $this->cmThApplication->createEmptyApplication();

        // データが作られているか確認
        $cmThApplication = $this->cmThApplication::where('cm_application_id', $cmApplicationId)->get();
        $this->assertIsObject($cmThApplication);
        $this->assertSame(1, $cmThApplication->count());
        $this->assertSame($userId, $cmThApplication[0]['user_id']);
    }
}
