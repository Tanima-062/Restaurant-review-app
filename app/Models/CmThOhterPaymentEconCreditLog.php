<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

class CmThOhterPaymentEconCreditLog extends Model
{
    use Notifiable;
    use Sortable;

    const STAT_TEMPORARY = 99999; // 仮入金
    const CREATED_AT = 'create_dt';
    const UPDATED_AT = 'update_dt';

    protected $table = 'common.cm_th_other_payment_econ_credit_log';
    protected $primaryKey = 'other_payment_econ_credit_log_id';
    protected $guarded = ['other_payment_econ_credit_log_id'];
    protected $fillable = [
        'status',
        'info_code',
        'info',
        'econ_no',
        'econ_cardno4',
        'shonin_cd',
        'shimuke_cd',
        'keijou',
        'cancel_dt',
        'action',
        'price',
        'other_payment_log_id',
        'user_id',
        'fee',
        'cm_application_id',
        'session_token',
        'value',
        'order_id'
    ];

    public $sortable = ['cm_application_id', 'create_dt'];

    public function cmThApplicationDetail()
    {
        return $this->hasOne('App\Models\CmThApplicationDetail', 'cm_application_id', 'cm_application_id');
    }

    public function getValueAttribute($value)
    {
        return json_decode($value, true);
    }

    public static function beforeSave(array $params)
    {
        $econCreditLog = new CmThOhterPaymentEconCreditLog();
        $econCreditLog->fill([
            'user_id'               => session('payment.user_id'),
            'price'                 => $params['amount'],
            'fee'                   => $params['fee'],
            'other_payment_type_id' => config('const.payment.payment_type.econ.CREDIT'),
            'cm_application_id'     => $params['cm_application_id'],
            'order_id'              => $params['order_id'],
            'session_token'         => $params['session_token'],
            'keijou'                => $params['keijou'],
            'value'                 => json_encode([
                                        'price_list' => [
                                            [
                                            'application_id' => 0,
                                            'cm_application_id' => $params['cm_application_id'],
                                            'service_cd' => config('code.serviceCd'),
                                            ]
                                        ],
                                        'session_id' => session()->getId()
                                       ])
        ]);
        $econCreditLog->save();
    }

    public function saveResultData()
    {
        $otherPaymentLog = CmThOtherPaymentLog::create([
            'user_id'               => $this->getAttribute('user_id'),
            'other_payment_type_id' => $this->getAttribute('other_payment_type_id'),
            'transaction_id'        => $this->getAttribute('session_token'),
            'status'                => $this->getAttribute('status'),
            'price'                 => $this->getAttribute('price'),
            'fee'                   => $this->getAttribute('fee'),
            'currency_id'           => 0
        ]);

        if ($otherPaymentLog) {
            $otherPaymentLog->priceList = $this->getAttribute('value')['price_list'];
            $otherPaymentLog->saveData();
            $this::fill(['other_payment_log_id' => $otherPaymentLog->other_payment_log_id])->save();
            return true;
        }

        return false;
    }

    public static function getByReservationId($reservationId, $orderId = '')
    {
        if (!empty($orderId)) {
            $econCreditLog = self::where('order_id', $orderId)->get();
        } else {
            $application = CmThApplicationDetail::getApplicationByReservationId($reservationId);
            /** @noinspection PhpUndefinedMethodInspection */
            $econCreditLog = CmThOhterPaymentEconCreditLog::where(
                'cm_application_id',
                $application->cm_application_id
            )->orderBy('create_dt', 'desc')->get();
        }

        return $econCreditLog;
    }

    public function getStatusStrAttribute()
    {
        if ($this->getAttribute('cancel_dt') != '0000-00-00 00:00:00') {
            return config('const.payment.log_status.cancel');
        } elseif ($this->getAttribute('status') == 1 && $this->getAttribute('info_code') == '00000') {
            return config('const.payment.log_status.success');
        } elseif ($this->getAttribute('status') == 0 && $this->getAttribute('info_code') == '') {
            return config('const.payment.log_status.hold');
        } elseif ($this->getAttribute('status') == self::STAT_TEMPORARY) {
            return config('const.payment.log_status.temp');
        } else {
            return config('const.payment.log_status.error');
        }
    }

    public function getKeijouStrAttribute()
    {
        if ($this->getAttribute('keijou') == 1) {
            return '計上';
        } else {
            return '与信';
        }
    }

    public function scopeLogStatus($query, $statusCode)
    {
        switch ($statusCode) {
            case 'success':
                /** @noinspection PhpUndefinedMethodInspection */
                return $query->where('status', 1)
                             ->where('info_code', '00000');
            case 'hold':
                /** @noinspection PhpUndefinedMethodInspection */
                return $query->where('status', 0)
                             ->where('info_code', '');
            case 'error':
                /** @noinspection PhpUndefinedMethodInspection */
                return $query->whereNotBetween('status', [0,3]);
            case 'cancel':
                /** @noinspection PhpUndefinedMethodInspection */
                return $query->where('cancel_dt', '!=', '0000-00-00 00:00:00');
            default:
                return $query;
        }
    }

    public function getIsYoshinCancelAttribute()
    {
        if ($this->keijou == 0 &&
            //$this->CmThApplicationDetail->application_id == 0 &&
            $this->cancel_dt == '0000-00-00 00:00:00'
        ) {
            return true;
        } else {
            return false;
        }
    }

    public function getCanCardCaptureAttribute()
    {
        if ($this->keijou == 0 &&
            $this->CmThApplicationDetail->application_id != 0 &&
            $this->cancel_dt == '0000-00-00 00:00:00' &&
            $this->status_str == '決済正常完了'
        ) {
            return true;
        } else {
            return false;
        }
    }
}
