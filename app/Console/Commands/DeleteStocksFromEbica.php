<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Vacancy;

class DeleteStocksFromEbica extends Command
{
    use BaseCommandTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ebica:deleteStocks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete vacancy info from vacancies';

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
        DB::beginTransaction();
        try {
            $threeDaysAgo = Carbon::today();
            $threeDaysAgo->addDays(-3);
            Vacancy::where('date', '<=', $threeDaysAgo)->delete();

            DB::commit();
        } catch (\Exception $e) {
            \Log::debug('failed');
            DB::rollback();
        }
    }
}
