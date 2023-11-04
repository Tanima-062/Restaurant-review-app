<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\hasMany;
use App\Models\Staff;

class StaffAuthority extends Model
{
    /**
     * @var string
     */
    protected $table = 'staff_authorities';

    public function staff(): hasMany
    {
        return $this->hasMany(Staff::class, 'staff_authority_id', 'id');
    }

}
