<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
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

    public function menu()
    {
        return $this->hasOne('App\Models\Menu', 'id', 'menu_id');
    }

    public function reviews()
    {
        return $this->hasMany('App\Models\Review', 'image_id', 'id');
    }

    public static function scopeStoreId($query, $store_id)
    {
        return $query->where('store_id', $store_id);
    }

    public static function scopeMenuId($query, $menu_id)
    {
        return $query->where('menu_id', $menu_id);
    }

    public static function weightImage($storeId)
    {
        $storeImages = Image::where('store_id', $storeId)->where('weight', '>', 0)->get();

        $menuIds = Menu::where('store_id', $storeId)->get(['id'])->toArray();

        $menuImages = Image::whereIn('menu_id', $menuIds)->where('weight', '>', 0)->get();

        $weightImages = $storeImages->concat($menuImages)->sortByDesc('weight')->take(5);

        return $weightImages;
    }
}
