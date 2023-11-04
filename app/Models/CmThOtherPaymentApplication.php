<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmThOtherPaymentApplication extends Model
{
    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $table = 'common.cm_th_other_payment_application';
    protected $primaryKey = 'other_payment_application_id';
    protected $guarded = ['other_payment_application_id'];

    public $timestamps = false;
}
