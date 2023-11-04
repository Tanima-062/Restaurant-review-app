<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $stockClassName = 'App\Modules\\'.config('restaurant.module.stockModule');
        $this->app->bind('App\Modules\Reservation\IFStock', $stockClassName);
        $this->app->bind('RestaurantReservationService', 'App\Services\RestaurantReservationService');

        $reservationClassName = 'App\Modules\\'.config('restaurant.module.reservationModule');
        $this->app->bind('App\Modules\Reservation\IFReservation', $reservationClassName);
        $this->app->bind('RestaurantReservationService', 'App\Services\RestaurantReservationService');
    }
}
