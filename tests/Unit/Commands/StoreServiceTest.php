<?php

namespace Tests\Unit\Commands;

use App\Models\Favorite;
use App\Models\Menu;
use App\Models\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StoreServiceTest extends TestCase
{
    private $storeFirst;
    private $storeSecond;
    private $menuFirst;
    private $menuSecond;

    public function setUp(): void
    {
        parent::setUp();
        $this->storeService = $this->app->make('App\Services\StoreService');
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testGetBreadcrumb()
    {
        // お気に入り情報準備
        $this->setFavorite();

        // バッチ実行前にお気に入りが存在すること
        // to
        $favoriteFirst = Favorite::find($this->favoriteFirst->id);
        $list = json_decode($favoriteFirst->list, true);
        $result = [];
        foreach ($list as $arr) {
            foreach ($arr as $v) {
                $result[] = $v;
            }
        }
        //var_dump('favorite menu id');
        //var_dump($result);
        $this->assertContains($this->menuFirst->id, $result);
        $this->assertContains($this->menuSecond->id, $result);
        // rs
        $favoriteSecond = Favorite::find($this->favoriteSecond->id);
        $list = json_decode($favoriteSecond->list, true);
        $result = [];
        foreach ($list as $arr) {
            foreach ($arr as $v) {
                $result[] = $v;
            }
        }
        //var_dump('favorite store id');
        //var_dump($result);
        $this->assertContains($this->storeFirst->id, $result);
        $this->assertContains($this->storeSecond->id, $result);

        // バッチ実行
        $this->artisan('delete:favorite')->expectsOutput(true);

        // バッチ実行後は削除されていること
        // to
        $favoriteFirst = Favorite::find($this->favoriteFirst->id);
        $list = json_decode($favoriteFirst->list, true);
        $result = [];
        foreach ($list as $arr) {
            foreach ($arr as $v) {
                $result[] = $v;
            }
        }
        //var_dump('favorite menu id');
        //var_dump($result);
        $this->assertNotContains($this->menuFirst->id, $result);
        $this->assertNotContains($this->menuSecond->id, $result);
        // rs
        $favoriteSecond = Favorite::find($this->favoriteSecond->id);
        $list = json_decode($favoriteSecond->list, true);
        $result = [];
        foreach ($list as $arr) {
            foreach ($arr as $v) {
                $result[] = $v;
            }
        }
        //var_dump('favorite store id');
        //var_dump($result);
        $this->assertNotContains($this->storeFirst->id, $result);
        $this->assertNotContains($this->storeSecond->id, $result);
    }

    private function setFavorite()
    {
        $dt = new Carbon();
        $storeFirst = new Store();
        $storeFirst->deleted_at = $dt;
        $storeFirst->save();
        $this->storeFirst = $storeFirst;

        $storeSecond = new Store();
        $storeSecond->deleted_at = $dt;
        $storeSecond->save();
        $this->storeSecond = $storeSecond;

        $dt = new Carbon();
        $menuFirst = new Menu();
        $menuFirst->deleted_at = $dt;
        $menuFirst->save();
        $this->menuFirst = $menuFirst;

        $menuSecond = new Menu();
        $menuSecond->deleted_at = $dt;
        $menuSecond->save();
        $this->menuSecond = $menuSecond;
        //var_dump('target menu id');
        //var_dump($menuFirst->id);
        //var_dump($menuSecond->id);
        $favoriteFirst = new Favorite();
        $favoriteFirst->list = '[{"id":117117},{"id":'.$menuFirst->id.'},{"id":'.$menuSecond->id.'}]';
        $favoriteFirst->app_cd = key(config('code.appCd.to'));
        $favoriteFirst->save();
        $this->favoriteFirst = $favoriteFirst;
        //var_dump('target store id');
        //var_dump($storeFirst->id);
        //var_dump($storeSecond->id);
        $favoriteSecond = new Favorite();
        $favoriteSecond->list = '[{"id":117117},{"id":'.$storeFirst->id.'},{"id":'.$storeSecond->id.'}]';
        $favoriteSecond->app_cd = key(config('code.appCd.rs'));
        $favoriteSecond->save();
        $this->favoriteSecond = $favoriteSecond;
    }
}
