<?php

namespace App\Providers;

use App\Services\Quickbooks;
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
        $this->app->singleton(
            'App\Contracts\Invoicing',
            'App\Invoicing\Quickbooks'
        );
        $this->app->bind(
            'bsform',
            'App\Support\BootstrapForm'
        );

        $this->app->bind(
            'DNS1D',
            'App\Support\DNS1D'
        );
        $this->app->singleton(
            'App\Contracts\Quickbooks',
            function() {
                return new Quickbooks(config('services.quickbooks.oauth2.client_id'), config('services.quickbooks.oauth2.client_secret'), config('services.quickbooks.oauth2.base_url'));
            });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
