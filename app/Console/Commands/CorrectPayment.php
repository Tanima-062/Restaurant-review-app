<?php

namespace App\Console\Commands;

use App\Models\PaymentToken;
use App\Modules\Payment\Skyticket\PaymentSkyticket;
use App\Services\PaymentService;
use App\Services\ReservationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CorrectPayment extends Command
{
    use BaseCommandTrait;

    private $className;
    private $paymentSkyticket;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'correct:payment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'correct payment depending on what the status currently is';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        PaymentSkyticket $paymentSkyticket
    ) {
        parent::__construct();
        $this->className = $this->getClassName($this);
        $this->paymentSkyticket = $paymentSkyticket;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(PaymentService $paymentService, ReservationService $reservationService)
    {
        $this->start();
        try {
            // チェック対象のデータを取得
            $paymentTokens = PaymentToken::where('created_at', '<=', Carbon::now()->subHour())
            ->whereNull('reservation_id')
            ->where('is_checked', 0)
            ->get();
        } catch (\throwable $e) {
            \Log::error(sprintf('::error=%s', $e->getTraceAsString()));

            return false;
        }

        foreach ($paymentTokens as $paymentToken) {
            try {
                if (empty($paymentToken->call_back_values)) {
                    continue;
                }

                $callBackValue = json_decode($paymentToken->call_back_values, true);
                if (empty($callBackValue['cartId'])) {
                    continue;
                }
                // 入金情報取得
                $inquirePayment = $this->paymentSkyticket->getPayment(['cartId' => $callBackValue['cartId']]);
                // 与信が成功かどうか
                if (!$inquirePayment['isPaid']) {
                    // チェックが終わったらチェック済みに更新
                    $paymentToken->is_checked = 1;
                    $paymentToken->save();
                    continue;
                }

                if (empty($callBackValue['orderCode'])) {
                    continue;
                }

                // 与信成功してしまっている場合は、与信のキャンセル処理を行う
                $result = null;
                $this->paymentSkyticket->cancelPayment($callBackValue['orderCode'], $result);

                // チェックが終わったらチェック済みに更新
                $paymentToken->is_checked = 1;
                $paymentToken->save();
            } catch (\Throwable $e) {
                // ログに出して続行
                \Log::error(sprintf('::error=%s', $e));
            }

        }

        $this->end();

        return true;
    }
}
