<?php

namespace App\Console\Commands;

use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Log;

class OldStockDelete extends Command
{
    use BaseCommandTrait;

    private $className;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "delete stock-data older than 1 month";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->className = $this->getClassName($this);
    }


    public function handle()
    {
        $this->start();

        $this->process();

        $this->end();

        return;
    }

    /**
     * Execute the console command.
     * @return int 0:正常終了 1:異常終了 2:対象データなし
     */
    private function process()
    {
        try {
            $dt = new Carbon();
            $lastMonth = $dt->subMonth()->format('Y-m-d');
            $list = Stock::query()
                ->select('id', 'date')
                ->whereDate('date', '<=', $lastMonth)
                ->get();

            // 対象データなし
            if (count($list) === 0) {
                return 2;
            }

            // 1ヶ月前のデータを削除
            DB::beginTransaction();
            foreach ($list as $stock) {
                $stock->delete();
            }
            DB::commit();

            return 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());
        }

        return 1;
    }
}
