<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
         'App\Models\Store' => 'App\Policies\Admin\StorePolicy',
         'App\Models\Menu' => 'App\Policies\Admin\MenuPolicy',
         'App\Models\Reservation' => 'App\Policies\Admin\ReservationPolicy',
         'App\Models\Staff' => 'App\Policies\Admin\StaffPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // 社内管理者のみ許可
        Gate::define('inHouseAdmin-only', function ($user) {
            return ($user->staff_authority_id === 1);
        });
        // 社内管理者、社内一般者(以上)のみ許可
        Gate::define('inHouseGeneral-higher', function ($user) {
            return ($user->staff_authority_id === 1 || $user->staff_authority_id === 2);
        });
        // クライアント管理者、クライアント一般者のみ許可
        Gate::define('client-only', function ($user) {
            return ($user->staff_authority_id === 3 || $user->staff_authority_id === 4);
        });
        // 社内管理者、社内一般者、クライアント管理者(以上)を許可
        Gate::define('clientAdmin-higher', function ($user) {
            return ($user->staff_authority_id <= 3);
        });
        // クライアント管理者のみ許可
        Gate::define('clientAdmin-only', function ($user) {
            return ($user->staff_authority_id === 3);
        });
        // 社内管理者、社内一般者、精算管理会社権限を許可
        Gate::define('settlementAdmin-higher', function ($user) {
            return ($user->staff_authority_id <= 2 || $user->staff_authority_id === 6);
        });

        // 社内管理者、社内一般者(以上)、外注許可
        Gate::define('inAndOutHouseGeneral-only', function ($user) {
            return ($user->staff_authority_id === 1 || $user->staff_authority_id === 2 || $user->staff_authority_id === 5);
        });

        // 外注先権限の場合は登録者のみを許可
        Gate::define('outHouseGeneral-onlySelf', function ($user, $id) {
            // 外注先権限以外には関係ないので許可
            if($user->staff_authority_id !== 5){
                return true;
            }
            return ($user->id === (int)$id);
        });
    }
}
