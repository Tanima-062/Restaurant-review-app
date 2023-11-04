<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Log;

class CmThApplicationDetail extends Model
{
    const CREATED_AT = 'create_dt';
    const UPDATED_AT = 'update_dt';

    protected $table = 'skyticket.cm_th_application_detail';
    protected $primaryKey = 'detail_id';
    protected $keyType = 'int';
    protected $guarded = ['detail_id'];
    protected $fillable = ['cm_application_id', 'service_cd'];

    public function cmThApplication()
    {
        return $this->belongsTo('App\Models\CmThApplication', 'cm_application_id', 'cm_application_id');
    }

    public function reservation()
    {
        return $this->hasOne('App\Models\Reservation', 'id', 'application_id');
    }

    public static function getApplicationByReservationId($reservationId)
    {
        return CmThApplicationDetail::with('cmThApplication')
            ->where('application_id', '=', $reservationId)
            ->where('service_cd', '=', config('code.serviceCd'))
            ->first();
    }

    public static function getApplicationDetailByCmApplicationId($cmApplicationId)
    {
        return CmThApplicationDetail::where('cm_application_id', '=', $cmApplicationId)
            ->where('service_cd', '=', config('code.serviceCd'))
            ->first();
    }

    public static function createEmptyApplicationDetail($cmApplicationId)
    {
        $applicationDetail = new CmThApplicationDetail();
        $applicationDetail->fill([
            'cm_application_id' => $cmApplicationId,
            'service_cd' => config('code.serviceCd')
        ]);

        $applicationDetail->save();
    }
}
