<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmThReadMail extends Model
{
    const CREATED_AT = 'create_dt';
    const UPDATED_AT = null;

    protected $table = 'common.cm_th_read_mail';
    protected $primaryKey = 'read_mail_id';
    protected $guarded = ['read_mail_id'];
}
