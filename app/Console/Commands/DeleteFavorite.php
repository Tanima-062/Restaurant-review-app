<?php

namespace App\Console\Commands;

use App\Libs\CommonLog;
use App\Models\Favorite;
use App\Models\Menu;
use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DeleteFavorite extends Command
{
    use BaseCommandTrait;

    private $className;
    private $paymentSkyticket;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:favorite';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'delete favorite ';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
    ) {
        parent::__construct();
        $this->className = $this->getClassName($this);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->start();

        $result = false;

        try {
            $dt = Carbon::now()->subDay();

            // 直近１日以内に削除されたメニューを取得
            $query = Menu::withTrashed();
            $query->where('deleted_at', '>=', $dt);
            $deletedMenuIds = $query->get()->pluck('id')->toArray();
            if (!empty($deletedMenuIds)) {
                $favorites = $this->_findFavorites($deletedMenuIds, key(config('code.appCd.to')));
                $this->_delete($favorites, $deletedMenuIds);
            }

            // 直近１日以内に削除された店舗を取得
            $query = Store::withTrashed();
            $query->where('deleted_at', '>=', $dt);
            $deletedStoreIds = $query->get()->pluck('id')->toArray();
            if (!empty($deletedStoreIds)) {
                $favorites = $this->_findFavorites($deletedStoreIds, key(config('code.appCd.rs')));
                $this->_delete($favorites, $deletedStoreIds);
            }
            $result = true;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            if (\App::environment('production')) {
                CommonLog::notifyToChat(
                    'お気に入り削除バッチで例外発生',
                    $e->getMessage()
                );
            } else {
                \Log::error($e->getMessage());
            }
        }

        $this->end();

        return $result;
    }

    private function _findFavorites($ids, $appCd)
    {
        $q = Favorite::where('app_cd', $appCd);
        $q->where(function ($q) use ($ids) {
            foreach ($ids as $key => $id) {
                if ($key === array_key_first($ids)) {
                    $q->where('list', 'like', '%{"id":'.$id.'}%');
                } else {
                    $q->orWhere('list', 'like', '%{"id":'.$id.'}%');
                }
            }
        });

        return $q->get();
    }

    private function _delete($favorites, $ids)
    {
        foreach ($favorites as $favorite) {
            $list = json_decode($favorite->list, true);
            $arrResult = [];
            foreach ($list as $value) {
                if (!in_array($value['id'], $ids)) {
                    $arrResult[] = $value;
                }
            }
            $favorite->list = json_encode($arrResult);
            $favorite->save();
        }
    }
}
