<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function changeToRefunding($reservationId)
    {
        $data = Refund::where('reservation_id', $reservationId)
                ->where('status',config('code.refundStatus.scheduled.key'))
                ->get();
        foreach($data as $rec){
            $rec->status = config('code.refundStatus.refunding.key');
            if(!$rec->save()){
                return false;
            }
        }
        return true;
    }

    public function changeTorefunded($reservationId)
    {
        $data = Refund::where('reservation_id', $reservationId)
                ->where('status',config('code.refundStatus.refunding.key'))
                ->get();
        foreach($data as $rec){
            $rec->status = config('code.refundStatus.refunded.key');
            if(!$rec->save()){
                return false;
            }
        }
        return true;
    }

    public function createRefundOnlyIfEmpty($reservationId, $price, $status){

        try{
        
            $refunds = Refund::where('reservation_id',$reservationId)->get();
            if(empty($refunds === 0)){
                Refund::create([
                    'reservation_id' => $reservationId,
                    'price' => $price,
                    'status' => $status,
                ]);
            }
            return true;
        }catch(\Exception $e){
                return false;
        }

    }
}
