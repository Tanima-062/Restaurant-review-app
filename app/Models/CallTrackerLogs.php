<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallTrackerLogs extends Model
{
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
    ];
}
