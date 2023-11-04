<?php

namespace App\Console\Commands;

use App\Libs\Fax\Fax;
use App\Models\FaxSendJob;
use Illuminate\Console\Command;

class SendFax extends Command
{
    use BaseCommandTrait;

    private $className;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:fax';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send fax from DB queue';

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
        $jobs = FaxSendJob::ready()->get();

        $this->line('Schedule:'.$jobs->count());

        foreach ($jobs as $job) {
            Fax::send($job);
        }

        $this->end();

        return true;
    }
}
