<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HolidayAdventure extends Model
{
    protected $table = 'holiday_adventure';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
