<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Ebica\EbicaStockSave;
use App\Models\UpdateStockQueue;
use App\Models\Vacancy;
use App\Models\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateEbicaStocksAfterReserved extends Command
{
    use BaseCommandTrait;
    
    private $className;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:ebicaStocksAfterReserved';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update ebica stocks on vacancies table after changed stocks on Ebica';

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

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->start();

        $this->process();

        $this->end();

        return;
    }

    public function process()
    {
        $now = Carbon::now();
        $updateStockQueues = UpdateStockQueue::where('exec_at', '<=', $now)->get();

        foreach ($updateStockQueues as $updateStockQueue) {
            DB::beginTransaction();
            try {
                $store = Store::find($updateStockQueue->store_id);

                $ebicaStockSave = new EbicaStockSave();
                $stockData = $ebicaStockSave->getStock($store->external_api->api_store_id, $updateStockQueue->date);

                foreach ($stockData->stocks as $stocks) {
                    foreach ($stocks->stock as $stock) {
                        $data['api_store_id'] = $store->external_api->api_store_id;
                        $data['date'] = $updateStockQueue->date;
                        $data['time'] = $stock->reservation_time.':00';
                        $data['headcount'] = $stocks->headcount;
                        $data['stock'] = $stock->sets;
                        $data['store_id'] = $store->id;
                        $data['created_at'] = $now->format('Y-m-d H:i:s');
                        $insert[] = $data;
                    }
                }

                //店舗設定？によって予約時間前後も在庫が変わっている場合があるので予約日のデータをdelete->insert
                Vacancy::where('store_id', $updateStockQueue->store_id)->whereDate('date', $updateStockQueue->date)->delete();
                Vacancy::insert($insert);
                UpdateStockQueue::where('id', $updateStockQueue->id)->delete();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                \Log::error(
                    sprintf(
                        '::error=%s',
                        $e->getMessage()
                    )
                );
            }
        }
    }
}
