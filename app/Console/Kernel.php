<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // elasticsearch再構築
        $schedule->command('remake:elastic')
            ->appendOutputTo('/var/log/laravel-scheduler.log')
            ->withoutOverlapping()
            ->dailyAt('6:00');

        // 祝日取得
        $schedule->command('register:holiday')
            ->appendOutputTo('/var/log/laravel-scheduler.log')
            ->withoutOverlapping()
            ->monthly();

        // fax送信
        $schedule->command('send:fax')
            ->appendOutputTo('/var/log/laravel-scheduler.log')
            ->withoutOverlapping()
            ->everyMinute();

        // テイクアウトクレジット 与信->計上 + 予約->成約
        $schedule->command('close:capture')
            ->appendOutputTo('/var/log/laravel-scheduler.log')
            ->withoutOverlapping()
            ->everyMinute();

        // 受注確定漏れの可能性があるものに対してリマインドメールを送る
        $schedule->command('remind:order')
            ->appendOutputTo('/var/log/laravel-scheduler.log')
            ->withoutOverlapping()
            ->everyMinute();

        // (1ヶ月前の)在庫データ削除
        $schedule->command('stock:delete')
            ->appendOutputTo('/var/log/laravel-scheduler.log')
            ->withoutOverlapping()
            ->dailyAt('3:00');

        // 検索履歴ログからtakeout_search_historyデータ作成
        $schedule->command('import:takeoutSearchHistory')
            ->appendOutputTo('/var/log/laravel-scheduler.log')
            ->withoutOverlapping()
            ->dailyAt('2:00');

        // takeout_search_historyデータからリストを作成しredisへ保存
        $schedule->command('cache:recommend')
            ->appendOutputTo('/var/log/laravel-scheduler.log')
            ->withoutOverlapping()
            ->dailyAt('5:00');

        // 検索APIのキャッシュを作成
        //$schedule->command('cache:search')
        //    ->appendOutputTo('/var/log/laravel-scheduler.log')
        //    ->withoutOverlapping()
        //    ->hourly();

        // 在庫を自動登録(開発のみ)
        //$schedule->command('db:seed --class=DataSeeder')
        //    ->appendOutputTo('/var/log/laravel-scheduler.log')
        //    ->withoutOverlapping()
        //    ->environments('develop')
        //    ->hourlyAt('20');

        // 決済不整合バッチ
        $schedule->command('correct:payment')
            ->appendOutputTo('/var/log/laravel-scheduler.log')
            ->withoutOverlapping()
            ->hourly();

        // 決済API 与信->計上 + 予約->成約
        $schedule->command('fix:payment')
            ->appendOutputTo('/var/log/laravel-scheduler.log')
            ->withoutOverlapping()
            ->everyMinute();

        // 処理が重いためebicaからの取得は1日1回
        // 3ヶ月先までの情報を一気に取得すると重くなるので、1ヶ月ずつを3回に分けることで負担軽減
        // ebica空席情報取得&Vacanciesへ登録(1ヶ月目)
        $schedule->command('ebica:getStocks 1')
        ->appendOutputTo('/var/log/laravel-scheduler.log')
        ->withoutOverlapping()
        ->dailyAt('01:00');

        // ebica空席情報取得&Vacanciesへ登録(2ヶ月目)
        $schedule->command('ebica:getStocks 2')
        ->appendOutputTo('/var/log/laravel-scheduler.log')
        ->withoutOverlapping()
        ->dailyAt('01:00');

        // ebica空席情報取得&Vacanciesへ登録(3ヶ月目)
        $schedule->command('ebica:getStocks 3')
        ->appendOutputTo('/var/log/laravel-scheduler.log')
        ->withoutOverlapping()
        ->dailyAt('01:00');

        // ebica空席情報当日より3日より前データ削除
        $schedule->command('ebica:deleteStocks')
        ->appendOutputTo('/var/log/laravel-scheduler.log')
        ->withoutOverlapping()
        ->dailyAt('00:10');

        // レストランのアンケートメールを送信
        $schedule->command('send:questionnaireMail')
        ->appendOutputTo('/var/log/laravel-scheduler.log')
        ->withoutOverlapping()
        ->everyMinute();

        // ebicaの予約ステータスとグルメの予約ステータスをチェックする
        $schedule->command('check:ebica-status')
        ->appendOutputTo('/var/log/laravel-scheduler.log')
        ->withoutOverlapping()
        ->twiceDaily(2, 14);

        // お気に入りデータチェックで、存在しないメニューと店舗をチェックし削除
        $schedule->command('delete:favorite')
        ->appendOutputTo('/var/log/laravel-scheduler.log')
        ->withoutOverlapping()
        ->dailyAt('03:00');

        // 席のみ予約のステータスを変更する
        $schedule->command('ensure:reservation')
        ->appendOutputTo('/var/log/laravel-scheduler.log')
        ->withoutOverlapping()
        ->everyMinute();

        // 再決済期限切れのレコードを自動キャンセルする
        $schedule->command('cancel:reservation')
        ->appendOutputTo('/var/log/laravel-scheduler.log')
        ->withoutOverlapping()
        ->everyMinute();

        // 精算用クライアント向けPDF作成レコードを作る
        $schedule->command('create:settle')
            ->appendOutputTo('/var/log/laravel-scheduler.log')
            ->withoutOverlapping()
            ->dailyAt('4:00');

        // レストラン空席管理一括登録を行う
        $schedule->command('register:vacancy')
        ->appendOutputTo('/var/log/laravel-scheduler.log')
        ->withoutOverlapping()
        ->everyMinute();

        // 翌月分のレストラン空席管理一括登録を行う
        $schedule->command('register_all:vacancy 5')
            ->appendOutputTo('/var/log/laravel-scheduler.log')
            ->withoutOverlapping()
            ->wednesdays()
            ->when(function () {
                return (date('d') <= 7); // 第一水曜日（＝1〜7日のどれか）のみ実行
            })
            ->at('05:00');

        // 翌月分のテイクアウト在庫一括登録を行う
        $schedule->command('register_all:stock 5')
            ->appendOutputTo('/var/log/laravel-scheduler.log')
            ->withoutOverlapping()
            ->thursdays()
            ->when(function () {
                return (date('d') <= 7); // 第一木曜日（＝1〜7日のどれか）のみ実行
            })
            ->at('05:00');

        // callReachへ電話通知のリクエストを行う
        // -> 2023/02〜利用停止に伴いコメントアウト
        // $schedule->command('callReach:request')
        //     ->appendOutputTo('/var/log/laravel-scheduler.log')
        //     ->withoutOverlapping()
        //     ->everyMinute();

        // コールトラッカーログ取得
        // -> 2023/02〜利用停止に伴いコメントアウト
        // $schedule->command('import:call_log')
        //     ->appendOutputTo('/var/log/laravel-scheduler.log')
        //     ->withoutOverlapping()
        //     ->dailyAt('2:00');

        // Ebica連携店舗の予約確定・予約変更・予約キャンセル時にVacanciesテーブルの対象レコード更新
        $schedule->command('update:ebicaStocksAfterReserved')
            ->appendOutputTo('/var/log/laravel-scheduler.log')
            ->withoutOverlapping()
            ->everyTenMinutes();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
