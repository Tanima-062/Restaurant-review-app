<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CancelDetail extends Model
{
    const UPDATED_AT = null;

    protected $guarded = ['id'];

    public function getAccountCodeStrAttribute()
    {
        if (isset(config('const.payment.account_code')[$this->account_code])) {
            return config('const.payment.account_code')[$this->account_code];
        } else {
            return '';
        }
    }

    public function getSumPriceAttribute()
    {
        return $this->price * $this->count;
    }
}
