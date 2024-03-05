<?php

namespace App\Providers;

use App\Services\BackMarket;
use App\Services\Click2Unlock;
use App\Services\ImeiCheck;
use App\Services\Orderhub;
use App\Services\PhoneCheck;
use App\Services\PhoneCheckOld;
use App\Services\Quickbooks;
use App\Services\TrgStock;
use App\Services\Txtlocal;
use App\Validation\Validator;
use Illuminate\Support\ServiceProvider;
use Validator as ValidatorFacade;
use Illuminate\Pagination\Paginator;


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
            'App\Contracts\ImeiCheck',
            function() {
                return new ImeiCheck(config('services.iunlocker.key'));
            }
        );
        $this->app->singleton(
            'currency_converter',
            'App\Currency\Converter'
        );

        $this->app->singleton(
            'App\Contracts\Txtlocal',
            function() {
                return new Txtlocal(config('services.txtlocal.key'));
            }
        );

        $this->app->singleton(
            'App\Contracts\Quickbooks',
            function() {
                return new Quickbooks(config('services.quickbooks.oauth2.client_id'), config('services.quickbooks.oauth2.client_secret'), config('services.quickbooks.oauth2.base_url'));
            });
        $this->app->singleton(
            'App\Contracts\PhoneCheck',
            function() {
                return new PhoneCheck(config('services.phonecheck.key'), config('services.phonecheck.username'));
            }
        );
        $this->app->singleton(
            'App\Contracts\Click2Unlock',
            function() {
                return new Click2Unlock(config('services.click2unlock.key'), config('services.click2unlock.url'));
            }
        );
        $this->app->singleton(
            'App\Contracts\PhoneCheckOld',
            function() {
                return new PhoneCheckOld(config('services.phonecheck_old.key'), config('services.phonecheck_old.username'));
            }
        );
        $this->app->singleton(
            'App\Contracts\Orderhub',
            function() {
                return new Orderhub(config('services.orderhub.client_id'), config('services.orderhub.client_secret'));
            }
        );
        $this->app->singleton(
            'App\Contracts\BackMarket',
            function() {
                return new BackMarket(config('services.backmarket.access_token'), config('services.backmarket.url'));
            }
        );

        $this->app->singleton(
            'App\Contracts\TrgStock',
            function() {
                return new TrgStock(config('services.trg_stock.api_key'), config('services.trg_stock.url'));
            }
        );

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        setlocale(LC_ALL, 'en_GB.UTF-8');

        ValidatorFacade::resolver(function ($translator, $data, $rules, $messages) {
            return new Validator($translator, $data, $rules, $messages);
        });

        Paginator::useBootstrapFive();
        Paginator::useBootstrapFour();
    }
}
