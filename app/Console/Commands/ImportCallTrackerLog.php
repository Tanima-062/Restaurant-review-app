<?php

namespace App\Console\Commands;

use App\Models\CallTrackerLogs;
use App\Modules\CallTracker\CallTrackerClient;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportCallTrackerLog extends Command
{
    use BaseCommandTrait;

    private $className;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:call_log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This import table call_tracker logs by API';

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

        return true;
    }

    /**
     * @return bool
     */
    private function process(): bool
    {
        $callTrackerClient = new CallTrackerClient();
        $token = $callTrackerClient->getToken();
        if (empty($token)) {
            \Log::error('CallTracker API token is empty');
            return false;
        }

        $start = Carbon::yesterday('Asia/Tokyo');
        $end = Carbon::today('Asia/Tokyo')->subSecond();
        $contents = $callTrackerClient->getLogs($token, $start->toISOString(true), $end->toISOString(true));

        $csvLines = explode("\r\n", $contents);

        $inserts = [];
        foreach ($csvLines as $key => $csvLine) {
            if ($key == 0 || empty($csvLine)) {
                continue;
            }

            $tmpColumns = explode(',', $csvLine);
            $columns = [];
            foreach ($tmpColumns as $i => $tmpColumn) {
                $columns[$i] = trim($tmpColumn, '""');
            }

            $tmp = [
                'log_no' => (int)$columns[0],                                      // ログ番号
                'process_id' => $columns[1],                                       // 処理ID
                'din_id' => $columns[2],                                           // トラッキング番号
                'client_id' => $columns[3],                                        // 広告主ID
                'client_name' => $columns[4],                                      // 広告主名
                'tracking_no_id' => $columns[5],                                   // トラッキング番号ID
                'no_name' => $columns[6],                                          // 番号名
                'fwd_id' => $columns[7],                                           // 転送先番号
                'caller_id' => $columns[8],                                        // 発信者番号
                'incoming_call_at' => $columns[9],                                 // 着信日時
                'start_call_at' => $columns[10],                                   // 通話開始日時
                'end_call_at' => $columns[11],                                     // 終了日時
                'ring_secs' => (int)$columns[12],                                  // 呼出秒数
                'call_secs' => (int)$columns[13],                                  // 通話秒数
                'connect_secs' => (int)$columns[14],                               // 接続秒数
                'disconnect_status' => $columns[15],                               // 切断状況
                'caller_device_type' => $columns[16],                              // 発信者端末種別
                'fwd_device_type' => $columns[17],                                 // 転送端末種別
                'last_disconnect' => (int)$columns[18],                            // 最終切断者
                'valid_status' => ($columns[19]) ? (int)$columns[19] : null,       // 有効判定
                'mail_notice' => ($columns[20] === 'true') ? 1 : 0,                // メール通知
                'web_push_notice' => ($columns[21] === 'true') ? 1 : 0,            // Webプッシュ通知
                'call_charge' => (int)$columns[22],                                // 通話料
                'achievement' => $columns[23],                                     // 成果判定
                'created_at' => date('Y-m-d H:i:s'),                        // 作成日時
            ];

            $inserts[] = $tmp;
        }

        try {
            CallTrackerLogs::insert($inserts);
        } catch (\Exception $e) {
            \Log::critical('ImportCallTrackerLog',['body' => $e->getMessage()]);
        }

        return true;
    }
}
