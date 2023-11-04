<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffAuthorityPage extends Model
{
    public static function isValidStaff($path, $staff)
    {
        $paths = explode('/', $path);
        $staffAuthorityPages = self::where('url', 'like', $paths[0].'%')
            ->where('staff_authority_id', $staff->staff_authority_id)
            ->get();

        if (count($staffAuthorityPages) != config('const.staff.authority.IN_HOUSE_ADMINISTRATOR')) {
            return null;
        }

        $staffAuthorityPage = $staffAuthorityPages[0];

        if (!$staffAuthorityPage || !$staffAuthorityPage->is_valid) {
            return null;
        } else {
            return true;
        }
    }

    public static function display($path, $staff)
    {
        if (self::isValidStaff($path, $staff)) {
            return 'block';
        } else {
            return 'none';
        }
    }
}
