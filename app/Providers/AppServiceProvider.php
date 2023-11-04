<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // 支払いモジュールを設定で変更できるようにする
        $className = 'App\Modules\Payment\\'.config('const.payment.module');
        $this->app->bind('App\Modules\Payment\IFPayment', $className);
        $this->app->bind('PaymentService', 'App\Services\PaymentService');
    }
}
