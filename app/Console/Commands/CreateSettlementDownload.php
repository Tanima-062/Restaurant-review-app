<?php

namespace App\Console\Commands;

use App\Models\Holiday;
use App\Models\HolidayAdventure;
use App\Models\Reservation;
use App\Models\SettlementCompany;
use App\Models\SettlementDownload;
use App\Modules\Settlement\ClientInvoice;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CreateSettlementDownload extends Command
{
    use BaseCommandTrait;

    private $className;

    const FIRST_HALF = 16; // 精算対象チェックスタート日 第1回
    const SECOND_HALF = 1; // 精算対象チェックスタート日 第2回

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:settle {today?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a settlement download record from contract data';

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

    private function searchSettlementDay($day, $holidayAll)
    {
        while(1) {
            // 土日なら次の日にする
            if ($day->dayOfWeek == 0 || $day->dayOfWeek == 6) {
                $day->addDay();
                continue;
            }

            // 祝日または休業日なら次の日にする
            $d = $holidayAll->where('date', $day->format('Y-m-d'))->all();
            if (count($d) != 0) {
                $day->addDay();;
                continue;
            }

            break;
        }

        return $day;
    }

    private function getCommissionInfo($settlementCompanyId, $month, $settlementDownload)
    {
        $clientInvoice = new ClientInvoice($settlementCompanyId, $month, $settlementDownload);
        $reservations = $clientInvoice->aggregateReservation();

        // RS手数料と計上単位取得
        $reservationRS = $reservations->firstWhere('app_cd', key(config('code.appCd.rs')));

        // TO手数料と計上単位取得
        $reservationTO = $reservations->firstWhere('app_cd', key(config('code.appCd.to')));

        return [
            'accountingConditionRS' => ($reservationRS) ? $reservationRS->accounting_condition : null,
            'commissionRateRS' => ($reservationRS) ? $reservationRS->commission_rate : null,
            'accountingConditionTO' => ($reservationTO) ? $reservationTO->accounting_condition : null,
            'commissionRateTO' => ($reservationTO) ? $reservationTO->commission_rate : null,
        ];
    }

    private function createSettlementDownload(Carbon $target, int $timing)
    {
        if ($timing == self::SECOND_HALF) { // 先月にする
            $target = $target->copy()->firstOfMonth()->subMonth(1);
        }

        $yyyymm = $target->format('Ym');

        $year = substr($yyyymm, 0, 4);
        $month = substr($yyyymm, 4, 2);

        $this->info("target:" . $target . " timing:" . $timing);

        // 期間に関係なく1ヶ月分取る
        $startDatetime = Carbon::create($year, $month, 1, 0, 0, 0);
        $endTerm = Carbon::create($target->year, $target->month, 1)->lastOfMonth()->day;
        $endDatetime = Carbon::create($year, $month, $endTerm, 23, 59, 59);

        $reservations = Reservation::with('reservationStore.store')
            ->whereBetween('pick_up_datetime', [$startDatetime, $endDatetime])
            //->where('is_close', 1)
            ->where('payment_status', '!=', config('code.paymentStatus.wait_payment.key'))->get();

        $settlementCompanyIds = array_unique($reservations->pluck('reservationStore.store.settlement_company_id')->all());
        $settlementCompanies = SettlementCompany::whereIn('id', $settlementCompanyIds)->get();

        foreach ($settlementCompanies as $settlementCompany) {
            if (is_null($settlementCompany->id)) {
                continue;
            }

            $twice = config('const.settlement.payment_cycle.0.value');
            $once = config('const.settlement.payment_cycle.1.value');
            if ($timing == self::FIRST_HALF && $settlementCompany->payment_cycle == $once) { // 前半締 月1回
                continue;
            } elseif ($timing == self::FIRST_HALF && $settlementCompany->payment_cycle == $twice) { // 前半締 月2回
                $startTerm = 1;
                $endTerm = 15;
                $paymentDeadline = $target->copy()->firstOfMonth()->addMonths(1)->addDays(14)->toDateString();  // 支払期限は翌月15日
            } elseif ($timing == self::SECOND_HALF && $settlementCompany->payment_cycle == $once) { // 後半締 月1回
                $startTerm = 1;
                $endTerm = Carbon::create($target->year, $target->month, 1)->lastOfMonth()->day;
                $paymentDeadline = $target->copy()->firstOfMonth()->addMonths(1)->lastOfMonth()->toDateString();
            } elseif ($timing == self::SECOND_HALF && $settlementCompany->payment_cycle == $twice)  { // 後半締 月2回
                $startTerm = 16;
                $endTerm = Carbon::create($target->year, $target->month, 1)->lastOfMonth()->day;
                $paymentDeadline = $target->copy()->firstOfMonth()->addMonths(1)->lastOfMonth()->toDateString();
            } else {
                continue; // ここは来ない
            }

            $pdfUrl = sprintf('settlement_confirm/pdf_download?month=%s&settlement_company_id=%d', $yyyymm, $settlementCompany->id);
            $settlementDownload = [
                'startTerm' => $startTerm,
                'endTerm' => $endTerm,
            ];

            $commissionInfo = $this->getCommissionInfo($settlementCompany->id, $yyyymm, $settlementDownload);
            if (is_null($commissionInfo['accountingConditionRS']) && is_null($commissionInfo['accountingConditionTO'])) {
                continue;
            }
            $settlementDownload = array_merge($settlementDownload, $commissionInfo);
            $clientInvoice = new ClientInvoice($settlementCompany->id, $yyyymm, $settlementDownload);
            $clientInvoice->agg();

            if ($clientInvoice->settlementAmount >= 0) {
                $type = config('const.settlement.settlement_type.0.value');
            } else {
                $type = config('const.settlement.settlement_type.1.value');
            }

            $s = SettlementDownload::firstOrNew([
                'settlement_company_id' => $settlementCompany->id,
                'month' => $yyyymm
            ]);

            if (!$s->exists) {
                $this->info("addSettlementDownload settlementCompanyId:".$settlementCompany->id);
                $s->start_term = $startTerm;
                $s->end_term = $endTerm;
                $s->type = $type;
                $s->commission_rate_rs = $settlementDownload['commissionRateRS'];
                $s->accounting_condition_rs = $settlementDownload['accountingConditionRS'];
                $s->commission_rate_to = $settlementDownload['commissionRateTO'];
                $s->accounting_condition_to = $settlementDownload['accountingConditionTO'];
                $s->pdf_url = $pdfUrl;
                $s->payment_deadline = $paymentDeadline;
                if ($timing == self::FIRST_HALF && abs($clientInvoice->settlementAmount) < $clientInvoice::DEFERRED_PRICE) {
                    $s->deferred_price = $clientInvoice->settlementAmount;
                    $s->payment_deadline = $target->copy()->firstOfMonth()->addMonths(1)->lastOfMonth()->toDateString(); // 指定金額より低かったら支払期限を月末に
                }
                $s->created_at = date('Y-m-d H:i:s');
                $s->save();
            }

            usleep(5);
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->start();
        $argToday = $this->argument('today');
        if (!is_null($argToday)) {
            $today = new Carbon($argToday);
        } else {
            $today = Carbon::now();
        }
        $this->info("today:".$today);

        // 祝日は指定日（＄today）の月の１日以降分を取得する
        // ※元々と指定日($today)以降分しか取得していなかったが、指定日の前日が土日や祝日の場合、うまく集計日を特定できなかったので変更
        // $todayFormat = $today->format('Y-m-d');
        $tomonth = Carbon::create($today->year, $today->month, 1);
        $todayFormat = $tomonth->format('Y-m-d');
        $holidayAdventure = HolidayAdventure::where('date', '>=', $todayFormat)->get();
        $holiday = Holiday::where('date', '>=', $todayFormat)->get();

        $holidayAll = $holidayAdventure->concat($holiday);

        // 第1回の精算集計日を出す
        $first = Carbon::create($today->year, $today->month, self::FIRST_HALF);

        // 営業日換算の精算日を探す
        $firstHalf = $this->searchSettlementDay($first, $holidayAll);

        // 今日は第1回精算集計日か
        if ($firstHalf->isSameDay($today)) {
            // 第1回精算集計レコード作成
            $this->createSettlementDownload($today, self::FIRST_HALF);
            $this->end();
            return;
        }

        // 第2回の精算集計日を出す
        $second = Carbon::create($today->year, $today->month, self::SECOND_HALF);

        // 営業日換算の精算日を探す
        $secondHalf = $this->searchSettlementDay($second, $holidayAll);

        // 今日は第2回精算集計日か
        if ($secondHalf->isSameDay($today)) {
            // 第2回精算集計レコード作成
            $this->createSettlementDownload($today, self::SECOND_HALF);
            $this->end();
            return;
        }

        $this->end();
        return;
    }
}
