<?php

namespace Tests\Unit\Models;

use App\Models\Image;
use App\Models\Menu;
use App\Models\Review;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImageTest extends TestCase
{
    private $image;
    private $testStoreId;
    private $testMenuId;
    private $testImageId;
    private $testImageId2;
    private $testImageId3;
    private $testReviewId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->image = new Image();

        $this->_createGenre();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testMenu()
    {
        // メニューに紐づく画像情報を取得
        $testMenuId = $this->testMenuId;
        $result = $this->image::whereHas('menu', function ($query) use ($testMenuId) {
            $query->where('id', $testMenuId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame($this->testImageId2, $result[0]['id']);
    }

    public function testReviews()
    {
        // レビューに紐づく画像情報を取得
        $testReviewId = $this->testReviewId;
        $result = $this->image::whereHas('reviews', function ($query) use ($testReviewId) {
            $query->where('id', $testReviewId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame($this->testImageId3, $result[0]['id']);
    }

    public function testScopeStoreId()
    {
        $result = $this->image::StoreId($this->testStoreId)->get();
        $this->assertIsObject($result);
        $this->assertSame($this->testImageId, $result[0]['id']);
    }

    public function testScopeMenuId()
    {
        $result = $this->image::MenuId($this->testMenuId)->get();
        $this->assertIsObject($result);
        $this->assertSame($this->testImageId2, $result[0]['id']);
    }

    private function _createGenre()
    {
        $store = new Store();
        $store->save();
        $this->testStoreId = $store->id;

        $image = new Image();
        $image->store_id = $this->testStoreId;
        $image->weight = 100;
        $image->save();
        $this->testImageId = $image->id;

        $menu = new Menu();
        $menu->store_id = $this->testStoreId;
        $menu->save();
        $this->testMenuId = $menu->id;

        $image = new Image();
        $image->menu_id = $this->testMenuId;
        $image->weight = 100;
        $image->save();
        $this->testImageId2 = $image->id;

        $image = new Image();
        $image->store_id = $this->testStoreId;
        $image->menu_id = $this->testMenuId;
        $image->weight = 100;
        $image->save();
        $this->testImageId3 = $image->id;

        $review = new Review();
        $review->menu_id = $this->testMenuId;
        $review->image_id = $this->testImageId3;
        $review->evaluation_cd = 'GOOD_DEAL';
        $review->body = 'テストbody';
        $review->user_id = 1;
        $review->user_name = 'グルメ 太郎';
        $review->published = 1;
        $review->created_at = '2022-10-01 10:00:00';
        $review->save();
        $this->testReviewId = $review->id;
    }
}
