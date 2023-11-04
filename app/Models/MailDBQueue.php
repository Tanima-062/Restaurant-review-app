<?php

namespace App\Models;

use App\Libs\Cipher;
use Illuminate\Database\Eloquent\Model;

class MailDBQueue extends Model
{
    const CREATED_AT = 'create_dt';
    const UPDATED_AT = 'update_dt';

    protected $table = 'common.cm_th_mail_log';
    protected $primaryKey = 'mail_log_id';
    protected $guarded = ['mail_log_id'];

    public function cmThReadMail()
    {
        return $this->hasOne('App\Models\CmThReadMail', 'mail_log_id', 'mail_log_id');
    }

    //*******************************************************
    // get時に復号化する
    //*******************************************************

    public function getFromAddressEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getSearchFromAddressEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getToAddressEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getSearchToAddressEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getMessageEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getAdditionalHeadersEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getAdditionalParametersEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    //*******************************************************
    // set時に暗号化する
    //*******************************************************

    public function setUserIdAttribute($value)
    {
        $this->attributes['user_id'] = $value;
    }

    public function setCmApplicationIdAttribute($value)
    {
        $this->attributes['cm_application_id'] = $value;
    }

    public function setToAddressEncAttribute($value)
    {
        $this->attributes['to_address_enc'] = Cipher::encrypt($value);
    }

    public function setSearchToAddressEncAttribute($value)
    {
        $this->attributes['search_to_address_enc'] = Cipher::encrypt($value);
    }

    public function setMessageEncAttribute($value)
    {
        $this->attributes['message_enc'] = Cipher::encrypt($value);
    }

    public function setAdditionalHeadersEncAttribute($value)
    {
        $this->attributes['additional_headers_enc'] = Cipher::encrypt($value);
    }

    public function setAdditionalParametersEncAttribute($value)
    {
        $this->attributes['additional_parameters_enc'] = Cipher::encrypt($value);
    }

    public function scopeUnreadMail($query, $userId)
    {
        return $query->where('user_id', $userId)
            ->where('site_cd', config('code.siteName'))
            ->where('user_send_flg', 0)
            ->where('non_show_user_flg', 0);
    }

    public function inQueue($reservationId, $addressFrom)
    {
        $this->attributes['application_id'] = $reservationId;
        $this->attributes['user_id'] = $this->attributes['user_id'];
        $this->attributes['cm_application_id'] = $this->attributes['cm_application_id'];
        $this->attributes['from_address_enc'] = Cipher::encrypt($addressFrom);
        $this->attributes['search_from_address_enc'] = Cipher::encrypt($addressFrom);
        $this->attributes['search_to_address_enc'] = $this->attributes['to_address_enc'];
        $this->attributes['site_cd'] = config('takeout.mail.siteCd');
        $this->attributes['service_cd'] = config('code.serviceCd');
        $this->attributes['status'] = config('takeout.mail.status.auto');
        $this->attributes['additional_headers_enc'] = Cipher::encrypt(serialize(['charset' => 'UTF-8', 'From' => $addressFrom]));

        return $this->save();
    }
}
