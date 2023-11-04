<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaxSendJob extends Model
{
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $guarded = ['id'];

    public static function scopeReady($query)
    {
        return $query->where('status', config('code.faxStatus.ready'))
            ->orWhere('status', config('code.faxStatus.retry'));
    }
}
