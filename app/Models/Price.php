<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Price extends Model
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

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function scopeMenuId($query, $menu_id)
    {
        return $query->where('menu_id', $menu_id);
    }

    public function scopeAvailable($query, int $menuId, string $pickUpdate)
    {
        return $query->where('menu_id', $menuId)
              ->where('start_date', '<=', $pickUpdate)
              ->where('end_date', '>=', $pickUpdate);
    }
}
