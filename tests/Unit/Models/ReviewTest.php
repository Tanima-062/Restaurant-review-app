<?php

namespace Tests\Unit\Models;

use App\Libs\Cipher;
use App\Models\Image;
use App\Models\Menu;
use App\Models\Review;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    private $review;
    private $testStoreId;
    private $testMenuId;
    private $testReviewId;
    private $testImageId;
    private $testUserId = 1000; //仮(何番でもいい)

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->review = new Review();

        $this->_createReview();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testUser()
    {
        $testUserId = $this->testUserId;
        $result = $this->review::whereHas('user', function ($query) use ($testUserId) {
            $query->where('user_id', $testUserId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReviewId, $result[0]['id']);
    }

    public function testImage()
    {
        $testImageId = $this->testImageId;
        $result = $this->review::whereHas('image', function ($query) use ($testImageId) {
            $query->where('id', $testImageId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReviewId, $result[0]['id']);
    }

    public function testMenu()
    {
        $testMenuId = $this->testMenuId;
        $result = $this->review::whereHas('menu', function ($query) use ($testMenuId) {
            $query->where('id', $testMenuId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReviewId, $result[0]['id']);
    }

    public function testGetUserNameAttribute()
    {
        // 暗号化文字列を用意し、復号結果と同じか比較
        $str = Cipher::encrypt('グルメ 太郎');
        $this->assertSame('グルメ 太郎', $this->review->getUserNameAttribute($str));
    }

    public function testGetCreatedAtAttribute()
    {
        $this->assertSame('2022/10/01/ 10:00:00', $this->review::find($this->testReviewId)->getCreatedAtAttribute());
    }

    public function testGetCountGroupedByEvaluationCd()
    {
        $result = $this->review->getCountGroupedByEvaluationCd($this->testStoreId);
        $this->assertCount(2, $result);
        $this->assertIsObject($result[0]);                              // evaluation_cd毎の合計
        $this->assertObjectHasAttribute('evaluation_cd', $result[0]);
        $this->assertSame('GOOD_DEAL', $result[0]->evaluation_cd);
        $this->assertObjectHasAttribute('count', $result[0]);
        $this->assertSame(1, $result[0]->count);
        $this->assertObjectHasAttribute('evaluation_cd', $result[1]);   // 店舗合計
        $this->assertSame('total', $result[1]->evaluation_cd);
        $this->assertObjectHasAttribute('count', $result[1]);
        $this->assertSame(1, $result[1]->count);
    }

    public function testScopeGetCountByEvaluationCd()
    {
        $result = $this->review::GetCountByEvaluationCd($this->testMenuId)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
    }

    public function testScopeGetReviews()
    {
        $result = $this->review::GetReviews($this->testMenuId)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
    }

    public function testGetReviewsByStoreId()
    {
        $result = $this->review->getReviewsByStoreId($this->testStoreId);
        $this->assertCount(1, $result);
        $this->assertIsObject($result[0]);
        $this->assertObjectHasAttribute('id', $result[0]);
        $this->assertSame($this->testReviewId, $result[0]->id);
        $this->assertObjectHasAttribute('user_id', $result[0]);
        $this->assertSame($this->testUserId, $result[0]->user_id);
        $this->assertObjectHasAttribute('user_name', $result[0]);
        $this->assertSame('グルメ 太郎', $result[0]->user_name);
        $this->assertObjectHasAttribute('body', $result[0]);
        $this->assertSame('テストbody', $result[0]->body);
        $this->assertObjectHasAttribute('evaluation_cd', $result[0]);
        $this->assertSame('GOOD_DEAL', $result[0]->evaluation_cd);
        $this->assertObjectHasAttribute('created_at', $result[0]);
        $this->assertSame('2022-10-01 10:00:00', $result[0]->created_at);
        $this->assertObjectHasAttribute('images_id', $result[0]);
        $this->assertSame($this->testImageId, $result[0]->images_id);
        $this->assertObjectHasAttribute('image_cd', $result[0]);
        $this->assertSame('test', $result[0]->image_cd);
        $this->assertObjectHasAttribute('url', $result[0]);
        $this->assertSame('https://test.hogehoge.jp', $result[0]->url);
    }

    public function testGetReviewsByMenuId()
    {
        $result = $this->review->getReviewsByMenuId($this->testMenuId);
        $this->assertCount(1, $result);
        $this->assertIsObject($result[0]);
        $this->assertObjectHasAttribute('id', $result[0]);
        $this->assertSame($this->testReviewId, $result[0]->id);
        $this->assertObjectHasAttribute('user_id', $result[0]);
        $this->assertSame($this->testUserId, $result[0]->user_id);
        $this->assertObjectHasAttribute('user_name', $result[0]);
        $this->assertSame('グルメ 太郎', $result[0]->user_name);
        $this->assertObjectHasAttribute('body', $result[0]);
        $this->assertSame('テストbody', $result[0]->body);
        $this->assertObjectHasAttribute('evaluation_cd', $result[0]);
        $this->assertSame('GOOD_DEAL', $result[0]->evaluation_cd);
        $this->assertObjectHasAttribute('created_at', $result[0]);
        $this->assertSame('2022-10-01 10:00:00', $result[0]->created_at);
        $this->assertObjectHasAttribute('images_id', $result[0]);
        $this->assertSame($this->testImageId, $result[0]->images_id);
        $this->assertObjectHasAttribute('image_cd', $result[0]);
        $this->assertSame('test', $result[0]->image_cd);
        $this->assertObjectHasAttribute('url', $result[0]);
        $this->assertSame('https://test.hogehoge.jp', $result[0]->url);
    }

    private function _createReview()
    {
        $store = new Store();
        $store->save();
        $this->testStoreId = $store->id;

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->save();
        $this->testMenuId = $menu->id;

        $image = new Image();
        $image->store_id = $this->testStoreId;
        $image->menu_id = $this->testMenuId;
        $image->image_cd = 'test';
        $image->url = 'https://test.hogehoge.jp';
        $image->weight = '1.00';
        $image->save();
        $this->testImageId = $image->id;

        $review = new Review();
        $review->store_id = $this->testStoreId;
        $review->menu_id = $this->testMenuId;
        $review->image_id = $this->testImageId;
        $review->evaluation_cd = 'GOOD_DEAL';
        $review->body = 'テストbody';
        $review->user_id = $this->testUserId;
        $review->user_name = 'グルメ 太郎';
        $review->published = 1;
        $review->created_at = '2022-10-01 10:00:00';
        $review->save();
        $this->testReviewId = $review->id;
    }
}
