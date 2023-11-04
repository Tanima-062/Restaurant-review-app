<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Log;

class CmThOtherPaymentLog extends Model
{
    const CREATED_AT = 'create_dt';
    const UPDATED_AT = 'update_dt';

    protected $table = 'common.cm_th_other_payment_log';
    protected $primaryKey = 'other_payment_log_id';
    protected $guarded = ['other_payment_log_id'];

    public $priceList = null;

    public function saveData()
    {
        foreach ($this->priceList as $priceDetail) {
            CmThOtherPaymentApplication::create([
                'other_payment_log_id' => $this->other_payment_log_id,
                'cm_application_id'    => $priceDetail['cm_application_id'],
                'price'                => $this->price,
                'fee'                  => $this->fee,
                'cancel_flg'           => 0,
                'cancel_dt'            => null,
            ]);
        }
    }
}
