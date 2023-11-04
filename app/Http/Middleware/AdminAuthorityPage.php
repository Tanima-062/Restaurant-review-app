<?php

namespace App\Http\Middleware;

use App\Models\StaffAuthorityPage;
use Closure;
use Illuminate\Support\Str;
use Illuminate\View\Factory;

class AdminAuthorityPage
{
    public function __construct(Factory $viewFactory)
    {
        $this->viewFactory = $viewFactory;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $path = $request->path();
        $staff = $request->user();

        if (Str::startsWith($path, 'admin/')) {
            $replaced = str_replace('admin/', '', $path);
            if (!StaffAuthorityPage::isValidStaff($replaced, $staff)) {
                abort(404);
            }
        } else {
            abort(404);
        }

        $isPublishable = $staff->staff_authority_id === config('const.staff.authority.OUT_HOUSE_GENERAL') ? 0 : 1;
        $common = [
            'isPublishable' => $isPublishable,
        ];

        $this->viewFactory->share('common', $common);

        return $next($request);
    }
}
