<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Log;

class CmThApplication extends Model
{
    const CREATED_AT = 'create_dt';
    const UPDATED_AT = 'update_dt';

    protected $table = 'skyticket.cm_th_application';
    protected $primaryKey = 'cm_application_id';
    protected $guarded = ['cm_application_id'];

    public static function createEmptyApplication()
    {
        $userId = CmTmUser::createUserForPayment();
        Log::debug('user_id:'.$userId);
        session()->put('payment.user_id', $userId);

        $application = new CmThApplication();
        $application->user_id = $userId;
        $application->lang_id = 1;
        $application->ip = !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 0;
        $application->user_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $application->save();

        Log::debug('cm_application_id:'.$application->cm_application_id);
        CmThApplicationDetail::createEmptyApplicationDetail($application->cm_application_id);

        return [$application->cm_application_id, $userId];
    }
}
