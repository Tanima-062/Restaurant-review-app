<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageBoard extends Model
{
    const UPDATED_AT = null;

    protected $guarded = ['id'];

    public function staff()
    {
        return $this->belongsTo('App\Models\Staff');
    }

    public function reservation()
    {
        return $this->belongsTo('App\Models\Reservation');
    }

    public static function scopeAdminSearchFilter($query, $reservationId)
    {
        return $query->where('reservation_id', '=', $reservationId);
    }

    public static function scopeOldestFirst($query)
    {
        return $query->orderBy('created_at', 'asc');
    }
}
