<?php

namespace Tests\Unit\Models;

use App\Models\Image;
use App\Models\Story;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StoryTest extends TestCase
{
    private $story;
    private $testStoryId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->story = new Story();

        $this->_createStory();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testImage()
    {
        $testStoryId = $this->testStoryId;
        $result = $this->story::has('image')->where('id', $this->testStoryId)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoryId, $result[0]['id']);
    }

    public function testStory()
    {
        //　ページ指定なし
        $result = $this->story->getStory('RS', 1);
        $this->assertIsObject($result);
        $this->assertTrue(($result->count() > 0));  //1件以上

        // ページ指定あり
        $result = $this->story->getStory('RS', 1);
        $this->assertIsObject($result);
        $this->assertTrue(($result->count() > 0));  //1件以上
    }

    public function testScopeAdminSearchFilter()
    {
        // ID検索
        $valid = [
            'id' => $this->testStoryId,
        ];
        $result = $this->story::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoryId, $result[0]['id']);

        // name検索
        $valid = [
            'name' => 'テストstory',
        ];
        $result = $this->story::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoryId, $result[0]['id']);

        // url検索
        $valid = [
            'url' => 'https://teststory',
        ];
        $result = $this->story::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoryId, $result[0]['id']);

        // app_cd検索
        $valid = [
            'app_cd' => 'RS',
        ];
        $result = $this->story::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertTrue(($result->count() > 0));  //1件以上
    }

    private function _createStory()
    {
        $image = new Image();
        $image->image_cd = 'test';
        $image->save();

        $story = new Story();
        $story->title = 'テストstory';
        $story->guide_url = 'https://teststory';
        $story->app_cd = 'RS';
        $story->published = 1;
        $story->image_id = $image->id;
        $story->save();
        $this->testStoryId = $story->id;
    }
}
